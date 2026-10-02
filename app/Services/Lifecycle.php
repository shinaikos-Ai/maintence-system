<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentHistory;
use App\Models\EquipmentHistory;
use App\Models\Tire;
use App\Models\TireHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Lifecycle
{
    public function record(User $user, Component|Tire $asset, array $data): void
    {
        abort_unless($user->manages(), 403);
        $data = Validator::make($data, [
            'action' => 'required|in:Inspeção,Reforma,Substituição,Descarte',
            'reading' => 'required|numeric|min:0', 'cost' => 'required|numeric|min:0', 'description' => 'required|string',
        ])->validate();
        DB::transaction(function () use ($asset, $data) {
            $asset = $asset->newQuery()->lockForUpdate()->findOrFail($asset->id);
            $isTire = $asset instanceof Tire;
            $class = $isTire ? TireHistory::class : ComponentHistory::class;
            $class::create($data + [($isTire ? 'tire_id' : 'component_id') => $asset->id, 'equipment_id' => $asset->equipment_id]);
            if ($isTire && in_array($data['action'], ['Reforma', 'Descarte'])) {
                $asset->update(['status' => $data['action'] === 'Reforma' ? 'Reforma' : 'Sucata']);
            }
            if (! $isTire && $data['action'] === 'Reforma') {
                $asset->update(['overhaul_hours' => $data['reading']]);
            }
            if ($asset->equipment_id) {
                EquipmentHistory::create(['equipment_id' => $asset->equipment_id, 'event' => $data['action'].' de '.($isTire ? 'pneu' : 'componente'), 'hour_meter' => $data['reading'], 'cost' => $data['cost'], 'details' => $data['description']]);
            }
        });
    }
}
