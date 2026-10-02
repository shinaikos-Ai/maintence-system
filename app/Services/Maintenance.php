<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Equipment;
use App\Models\EquipmentHistory;
use App\Models\LaborEntry;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\Part;
use App\Models\PartUsage;
use App\Models\PreventiveExecution;
use App\Models\PreventivePlan;
use App\Models\Signature;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Maintenance
{
    private function check(bool $valid, string $message): void
    {
        if (! $valid) {
            throw ValidationException::withMessages(['operation' => $message]);
        }
    }

    private function manager(User $u): void
    {
        abort_unless($u->manages(), 403);
    }

    private function worker(User $u, WorkOrder $w): void
    {
        abort_unless($u->active && ($u->manages() || ($u->role === 'Mecânico' && $w->assigned_to === $u->id)), 403);
        $this->check($w->status !== 'Encerrada', 'A OS já está encerrada.');
    }

    public function request(User $u, array $data): MaintenanceRequest
    {
        abort_unless(Access::create($u, 'MaintenanceRequest'), 403);
        $data = Validator::make($data, ['equipment_id' => 'required|exists:equipamentos,id', 'hour_meter' => 'required|numeric|min:0', 'priority' => 'required|in:Baixa,Média,Alta,Crítica', 'description' => 'required|string|max:10000'])->validate();

        return DB::transaction(function () use ($u, $data) {
            $e = Equipment::lockForUpdate()->findOrFail($data['equipment_id']);
            $this->check($data['hour_meter'] >= $e->hour_meter, 'O horímetro não pode retroceder.');
            $r = MaintenanceRequest::create($data + ['requested_by' => $u->id, 'status' => 'Aberta']);
            $e->update(['hour_meter' => $data['hour_meter']]);
            $w = WorkOrder::create(['equipment_id' => $e->id, 'maintenance_request_id' => $r->id, 'status' => 'Aberta', 'type' => 'Corretiva', 'priority' => $r->priority, 'failure_description' => $r->description]);
            EquipmentHistory::create(['equipment_id' => $e->id, 'work_order_id' => $w->id, 'event' => 'Solicitação e OS abertas', 'hour_meter' => $e->hour_meter, 'details' => $r->description]);

            return $r;
        });
    }

    public function review(User $u, MaintenanceRequest $request, string $status): void
    {
        $this->manager($u);
        $this->check(in_array($status, ['Em Análise', 'Aprovada', 'Rejeitada']), 'Status inválido.');
        DB::transaction(function () use ($request, $status) {
            $r = MaintenanceRequest::lockForUpdate()->findOrFail($request->id);
            $w = WorkOrder::where('maintenance_request_id', $r->id)->lockForUpdate()->firstOrFail();
            $this->check($w->status === 'Aberta', 'A solicitação não pode ser alterada após início da OS.');
            $r->update(['status' => $status]);
        });
    }

    public function assign(User $u, WorkOrder $order, array $data): void
    {
        $this->manager($u);
        $data = Validator::make($data, ['assigned_to' => 'required|exists:users,id', 'priority' => 'required|in:Baixa,Média,Alta,Crítica', 'due_at' => 'required|date'])->validate();
        $this->check(User::whereKey($data['assigned_to'])->where('role', 'Mecânico')->where('active', true)->exists(), 'Selecione um mecânico ativo.');
        DB::transaction(function () use ($u, $order, $data) {
            $w = WorkOrder::lockForUpdate()->findOrFail($order->id);
            $this->worker($u, $w);
            $w->update($data);
        });
    }

    public function start(User $u, WorkOrder $order): void
    {
        DB::transaction(function () use ($u, $order) {
            $w = WorkOrder::lockForUpdate()->findOrFail($order->id);
            $this->worker($u, $w);
            $this->check(! PartUsage::where('work_order_id', $w->id)->where('status', 'Solicitada')->exists(), 'Há peças pendentes de liberação.');
            $this->check($w->assigned_to !== null, 'Designe um mecânico antes de iniciar.');
            $this->check(in_array($w->status, ['Aberta', 'Aguardando Peças']), 'OS já iniciada.');
            if ($w->maintenance_request_id) {
                $this->check(MaintenanceRequest::findOrFail($w->maintenance_request_id)->status === 'Aprovada', 'A solicitação precisa ser aprovada pelo controlador.');
            }
            $w->update(['status' => 'Em Andamento', 'started_at' => $w->started_at ?? now()]);
            Equipment::findOrFail($w->equipment_id)->update(['status' => 'Em Manutenção']);
            EquipmentHistory::create(['equipment_id' => $w->equipment_id, 'work_order_id' => $w->id, 'event' => 'OS iniciada']);
        });
    }

    public function diagnose(User $u, WorkOrder $order, array $data): void
    {
        $data = Validator::make($data, ['diagnosis' => 'required|string', 'root_cause' => 'required|string', 'service_performed' => 'required|string'])->validate();
        DB::transaction(function () use ($u, $order, $data) {
            $w = WorkOrder::lockForUpdate()->findOrFail($order->id);
            $this->worker($u, $w);
            $w->update($data);
        });
    }

    public function requestPart(User $u, WorkOrder $order, array $data): PartUsage
    {
        $data = Validator::make($data, ['part_id' => 'required|exists:pecas,id', 'quantity' => 'required|numeric|gt:0'])->validate();

        return DB::transaction(function () use ($u, $order, $data) {
            $w = WorkOrder::lockForUpdate()->findOrFail($order->id);
            $this->worker($u, $w);
            $this->check($w->started_at !== null, 'Inicie a OS antes de solicitar peças.');
            $p = PartUsage::create($data + ['work_order_id' => $w->id, 'requested_by' => $u->id, 'status' => 'Solicitada']);
            $w->update(['status' => 'Aguardando Peças']);

            return $p;
        });
    }

    public function release(User $u, PartUsage $usage, bool $approve): void
    {
        $this->manager($u);
        DB::transaction(function () use ($u, $usage, $approve) {
            $w = WorkOrder::lockForUpdate()->findOrFail($usage->work_order_id);
            $this->check($w->status !== 'Encerrada', 'OS encerrada.');
            $usage = PartUsage::lockForUpdate()->findOrFail($usage->id);
            $this->check($usage->status === 'Solicitada', 'Peça já analisada.');
            $p = Part::lockForUpdate()->findOrFail($usage->part_id);
            if ($approve) {
                $this->check($p->stock >= $usage->quantity, 'Estoque insuficiente.');
                app(Inventory::class)->consume($p, (float) $usage->quantity, $w->id, $u);
            }
            $usage->update(['status' => $approve ? 'Liberada' : 'Rejeitada', 'approved_by' => $u->id, 'unit_cost' => $p->unit_cost]);
            if (! PartUsage::where('work_order_id', $w->id)->where('status', 'Solicitada')->exists()) {
                $w->update(['status' => 'Em Andamento']);
            }
            $this->recalculate($w);
        });
    }

    public function stock(User $u, Part $part, array $data): void
    {
        app(Inventory::class)->move($u, $part, $data);
    }

    public function labor(User $u, WorkOrder $order, array $data): void
    {
        $data = Validator::make($data, ['hours' => 'required|numeric|gt:0|max:24', 'hourly_rate' => 'required|numeric|min:0', 'description' => 'required|string', 'worked_on' => 'required|date|before_or_equal:today'])->validate();
        DB::transaction(function () use ($u, $order, $data) {
            $w = WorkOrder::lockForUpdate()->findOrFail($order->id);
            $this->worker($u, $w);
            $this->check($w->started_at !== null, 'Inicie a OS antes de registrar mão de obra.');
            $this->check(substr($data['worked_on'], 0, 10) >= $w->started_at->toDateString(), 'Data anterior ao início da OS.');
            User::whereKey($u->id)->lockForUpdate()->firstOrFail();
            $hours = LaborEntry::where('user_id', $u->id)->whereDate('worked_on', $data['worked_on'])->sum('hours');
            $this->check($hours + $data['hours'] <= 24, 'O total diário não pode exceder 24 horas.');
            LaborEntry::create($data + ['work_order_id' => $w->id, 'user_id' => $u->id]);
            $this->recalculate($w);
        });
    }

    private function recalculate(WorkOrder $w): void
    {
        $labor = LaborEntry::where('work_order_id', $w->id)->selectRaw('COALESCE(SUM(hours * hourly_rate),0) AS total')->value('total');
        $parts = PartUsage::where('work_order_id', $w->id)->where('status', 'Liberada')->selectRaw('COALESCE(SUM(quantity * unit_cost),0) AS total')->value('total');
        $w->update(['total_cost' => round($labor + $parts, 2)]);
    }

    public function close(User $u, WorkOrder $order, array $data): void
    {
        $data = Validator::make($data, ['closing_hour_meter' => 'required|numeric|min:0', 'closing_mileage' => 'required|numeric|min:0'])->validate();
        DB::transaction(function () use ($u, $order, $data) {
            $w = WorkOrder::lockForUpdate()->findOrFail($order->id);
            $this->worker($u, $w);
            $this->check($w->status === 'Em Andamento', 'A OS precisa estar em andamento e sem peças pendentes.');
            $this->check($w->diagnosis && $w->root_cause && $w->service_performed, 'Registre diagnóstico, causa raiz e serviço executado.');
            $this->check(! PartUsage::where('work_order_id', $w->id)->where('status', 'Solicitada')->exists(), 'Há peças pendentes.');
            $this->check(LaborEntry::where('work_order_id', $w->id)->exists(), 'Registre a mão de obra.');
            foreach (['Antes', 'Depois'] as $kind) {
                $this->check(Attachment::where('work_order_id', $w->id)->where('kind', $kind)->exists(), 'Anexe foto: '.$kind);
            }
            $e = Equipment::lockForUpdate()->findOrFail($w->equipment_id);
            $this->check($data['closing_hour_meter'] >= $e->hour_meter && $data['closing_mileage'] >= $e->mileage, 'As leituras não podem retroceder.');
            $this->recalculate($w);
            $w->update($data + ['status' => 'Encerrada', 'finished_at' => now(), 'downtime_hours' => round($w->started_at->diffInSeconds(now()) / 3600, 3)]);
            $other = WorkOrder::where('equipment_id', $e->id)->whereKeyNot($w->id)->whereIn('status', ['Em Andamento', 'Aguardando Peças'])->exists();
            $e->update(['hour_meter' => $data['closing_hour_meter'], 'mileage' => $data['closing_mileage'], 'status' => $other ? 'Em Manutenção' : 'Disponível']);
            EquipmentHistory::create(['equipment_id' => $e->id, 'work_order_id' => $w->id, 'event' => 'OS encerrada', 'hour_meter' => $e->hour_meter, 'mileage' => $e->mileage, 'cost' => $w->total_cost, 'details' => $w->service_performed]);
            if ($execution = PreventiveExecution::where('work_order_id', $w->id)->first()) {
                $plan = PreventivePlan::lockForUpdate()->findOrFail($execution->preventive_plan_id);
                $reading = $plan->basis === 'Quilometragem' ? $e->mileage : $e->hour_meter;
                $execution->update(['completed_at' => now(), 'reading' => $reading]);
                $plan->update($plan->basis === 'Calendário' ? ['next_date' => today()->addDays((int) $plan->interval_value)] : ['next_value' => $reading + $plan->interval_value]);
            }
        });
    }

    public function preventive(): int
    {
        $count = 0;
        foreach (PreventivePlan::where('active', true)->pluck('id') as $id) {
            $count += DB::transaction(function () use ($id) {
                $p = PreventivePlan::lockForUpdate()->findOrFail($id);
                if (PreventiveExecution::where('preventive_plan_id', $id)->whereNull('completed_at')->exists()) {
                    return 0;
                }
                $e = Equipment::findOrFail($p->equipment_id);
                if ($e->status === 'Inativo') {
                    return 0;
                }
                $remaining = $p->basis === 'Calendário' ? today()->diffInDays($p->next_date, false) : $p->next_value - ($p->basis === 'Horímetro' ? $e->hour_meter : $e->mileage);
                if ($remaining > $p->warning_threshold) {
                    return 0;
                }
                $w = WorkOrder::create(['equipment_id' => $e->id, 'type' => 'Preventiva', 'priority' => $remaining <= 0 ? 'Alta' : 'Média', 'status' => 'Aberta', 'failure_description' => $p->name, 'due_at' => $p->basis === 'Calendário' ? $p->next_date : null]);
                PreventiveExecution::create(['preventive_plan_id' => $p->id, 'work_order_id' => $w->id]);
                Notification::create(['equipment_id' => $e->id, 'title' => 'Preventiva programada', 'body' => $p->name.' — OS #'.$w->id]);

                return 1;
            });
        }

        return $count;
    }

    public function sign(User $u, WorkOrder $order, string $path): void
    {
        abort_unless($u->active && in_array($u->role, ['Mecânico', 'Controlador', 'Gerente', 'Administrador']), 403);
        abort_unless($u->role !== 'Mecânico' || $order->assigned_to === $u->id, 403);
        $this->check(str_starts_with($path, 'signatures/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 'Imagem inválida.');
        $mime = Storage::disk('local')->mimeType($path);
        $this->check(in_array($mime, ['image/png', 'image/jpeg']), 'Use PNG ou JPEG.');
        DB::transaction(function () use ($u, $order, $path) {
            $w = WorkOrder::lockForUpdate()->findOrFail($order->id);
            $this->check($w->status === 'Encerrada','As assinaturas são coletadas após o encerramento da OS.');
            $this->check(! Signature::where('work_order_id',$w->id)->where('role',$u->role)->exists(),'Este perfil já assinou a OS.');
            Signature::create(['work_order_id' => $w->id, 'user_id' => $u->id, 'role' => $u->role, 'image_path' => $path, 'signed_at' => now(), 'document_hash' => hash('sha256',json_encode($w->getRawOriginal()))]);
        });
    }
}
