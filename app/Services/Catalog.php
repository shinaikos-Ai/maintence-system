<?php

namespace App\Services;

use App\Models\ComponentHistory;
use App\Models\Equipment;
use App\Models\EquipmentHistory;
use App\Models\TireHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Catalog
{
    public function delete(User $u, Model $record): bool
    {
        abort_unless(Access::delete($u, class_basename($record)), 403);
        try {
            return DB::transaction(fn () => $record->delete());
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23503'])) {
                throw ValidationException::withMessages(['operation' => 'Este cadastro possui histórico ou vínculos e deve ser mantido. Use status inativo quando disponível.']);
            }
            throw $e;
        }
    }

    public function save(User $u, string $kind, array $data, ?Model $record = null): Model
    {
        abort_unless($record ? Access::edit($u, $kind) : Access::create($u, $kind), 403);
        if ($kind === 'MaintenanceRequest') {
            return app(Maintenance::class)->request($u, $data);
        }
        if ($kind === 'User') {
            $data = Validator::make($data, ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email,'.($record?->id ?? 'NULL'), 'password' => ($record ? 'nullable' : 'required').'|string|min:12', 'role' => 'required|in:Operador,Mecânico,Controlador,Gerente,Administrador', 'active' => 'boolean'])->validate();
            if (empty($data['password'])) {
                unset($data['password']);
            }
            if ($record?->id === $u->id && (($data['role'] ?? '') !== 'Administrador' || ! ($data['active'] ?? true))) {
                throw ValidationException::withMessages(['role' => 'Não remova seu próprio acesso administrativo.']);
            }
        } else {
            $rules = [];
            foreach (config("maintenance.$kind.fields") as $f => $info) {
                if (in_array($f, ['requested_by', 'user_id', 'stock'])) {
                    continue;
                }
                $required = in_array($f, config("maintenance.$kind.required"));
                $rules[$f] = [$required ? 'required' : 'nullable'];
                $rules[$f][] = match ($info['type']) {
                    'decimal','money' => 'numeric','int' => 'integer','bool' => 'boolean','date','datetime' => 'date',default => 'string'
                };
                if ($info['type'] === 'ref') {
                    $class = 'App\\Models\\'.$info['target'];
                    $rules[$f] = [$required ? 'required' : 'nullable', 'integer', 'exists:'.(new $class)->getTable().',id'];
                }
                if (in_array($info['type'], ['decimal', 'money', 'int'])) {
                    $rules[$f][] = 'min:0';
                }
                if ($info['type'] === 'select') {
                    $rules[$f][] = Rule::in($info['options']);
                }
            }
            $data = Validator::make($data, $rules)->validate();
        }

        return DB::transaction(function () use ($u, $kind, $data, $record) {
            if ($record) {
                $record = $record->newQuery()->lockForUpdate()->findOrFail($record->id);
            }
            if ($kind === 'Equipment' && $record && (($data['hour_meter'] ?? 0) < $record->hour_meter || ($data['mileage'] ?? 0) < $record->mileage)) {
                throw ValidationException::withMessages(['hour_meter' => 'As leituras não podem retroceder.']);
            }
            if ($kind === 'PreventivePlan') {
                if (($data['interval_value'] ?? 0) <= 0 || ($data['basis'] === 'Calendário' && empty($data['next_date']))) {
                    throw ValidationException::withMessages(['interval_value' => 'Informe intervalo positivo e próxima data para calendário.']);
                }
            }
            if ($kind === 'Attachment') {
                $count = 0;
                foreach (['work_order_id' => 'WorkOrder', 'maintenance_request_id' => 'MaintenanceRequest', 'oil_analysis_id' => 'OilAnalysis', 'equipment_id' => 'Equipment'] as $key => $target) {
                    if (empty($data[$key])) {
                        continue;
                    }$count++;
                    $class = 'App\\Models\\'.$target;
                    $related = $class::findOrFail($data[$key]);
                    abort_unless(Access::record($u, $related), 403);
                    if ($target === 'WorkOrder' && $related->status === 'Encerrada') {
                        throw ValidationException::withMessages(['work_order_id' => 'OS encerrada não permite novos anexos.']);
                    }
                }
                if ($count !== 1) {
                    throw ValidationException::withMessages(['path' => 'Vincule o arquivo a exatamente um registro.']);
                }
                $path = $data['path'];
                if (! str_starts_with($path, 'attachments/') || str_contains($path, '..') || ! Storage::disk('local')->exists($path)) {
                    throw ValidationException::withMessages(['path' => 'Arquivo inválido.']);
                }
                $mime = Storage::disk('local')->mimeType($path);
                if (! in_array($mime, ['image/jpeg', 'image/png', 'application/pdf']) || Storage::disk('local')->size($path) > 10 * 1024 * 1024) {
                    throw ValidationException::withMessages(['path' => 'Use PNG, JPG ou PDF até 10 MB.']);
                }
                if (in_array($data['kind'], ['Antes', 'Depois']) && (! str_starts_with($mime, 'image/') || empty($data['work_order_id']))) {
                    throw ValidationException::withMessages(['kind' => 'Fotos antes/depois devem ser imagens vinculadas à OS.']);
                }
                $data['user_id'] = $u->id;
            }
            $old = $record?->getOriginal() ?? [];
            if (in_array($kind, ['Component', 'Tire']) && ! empty($data['equipment_id'])) {
                $equipment = Equipment::findOrFail($data['equipment_id']);
                if (($data['installation_meter'] ?? 0) > $equipment->hour_meter) {
                    throw ValidationException::withMessages(['installation_meter' => 'Leitura da instalação superior ao horímetro atual.']);
                }
                if ($record && $record->equipment_id && $record->equipment_id != $data['equipment_id']) {
                    $previous = Equipment::findOrFail($record->equipment_id);
                    $data['worked_hours'] = $record->worked_hours + max(0, $previous->hour_meter - $record->installation_meter);
                }
            }
            $class = 'App\\Models\\'.$kind;
            if ($record) {
                $record->update($data);
            } else {
                $record = $class::create($data);
            }
            if (in_array($kind, ['Component', 'Tire'])) {
                $history = $kind === 'Component' ? ComponentHistory::class : TireHistory::class;
                $history::create([($kind === 'Component' ? 'component_id' : 'tire_id') => $record->id, 'equipment_id' => $record->equipment_id, 'action' => empty($old) ? 'Cadastro' : 'Atualização', 'reading' => $record->installation_meter, 'cost' => empty($old) ? ($record->cost ?? 0) : 0, 'description' => json_encode(['antes' => $old, 'depois' => $record->getAttributes()], JSON_UNESCAPED_UNICODE)]);
                if ($record->equipment_id) {
                    EquipmentHistory::create(['equipment_id' => $record->equipment_id, 'event' => $kind === 'Component' ? 'Componente atualizado' : 'Pneu atualizado', 'details' => ($record->name ?? $record->fire_number)]);
                }
            }

            return $record;
        });
    }
}
