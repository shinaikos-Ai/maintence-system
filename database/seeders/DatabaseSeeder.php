<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Contract;
use App\Models\CostCenter;
use App\Models\Equipment;
use App\Models\Location;
use App\Models\OilAnalysis;
use App\Models\Part;
use App\Models\PreventivePlan;
use App\Models\Tire;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Dados de demonstração não devem ser carregados em produção.');
        }
        $password = env('DEMO_PASSWORD');
        if (! $password || strlen($password) < 12) {
            throw new \RuntimeException('Defina DEMO_PASSWORD com pelo menos 12 caracteres.');
        }
        foreach (['Administrador', 'Operador', 'Mecânico', 'Controlador', 'Gerente'] as $i => $role) {
            User::firstOrCreate(['email' => ['admin', 'operador', 'mecanico', 'controlador', 'gerente'][$i].'@cmf.example'], ['name' => $role.' Demo', 'role' => $role, 'password' => $password, 'active' => true]);
        }
        $contract = Contract::firstOrCreate(['code' => 'VALE-2026'], ['name' => 'Operação Mina Norte']);
        $center = CostCenter::firstOrCreate(['code' => 'MIN-01'], ['name' => 'Mineração']);
        $location = Location::firstOrCreate(['name' => 'Oficina Central']);
        $types = [['CAT', '793F', 'CAM'], ['Komatsu', 'PC2000', 'ESC'], ['Volvo', 'A40G', 'ART'], ['CAT', 'D8T', 'TRA'], ['Toyota', 'Hilux', 'APO']];
        for ($i = 1; $i <= 25; $i++) {
            [$maker,$model,$prefix] = $types[($i - 1) % 5];
            $e = Equipment::firstOrCreate(['tag' => sprintf('%s-%03d', $prefix, $i)], ['asset_number' => 'PAT-'.(1000 + $i), 'manufacturer' => $maker, 'model' => $model, 'serial_number' => 'SN2026'.str_pad($i, 6, '0', STR_PAD_LEFT), 'year' => 2020 + $i % 5, 'hour_meter' => 4000 + $i * 137, 'mileage' => $i * 1200, 'contract_id' => $contract->id, 'cost_center_id' => $center->id, 'location_id' => $location->id, 'criticality' => $i % 3 === 0 ? 'Crítica' : 'Alta', 'status' => 'Operando']);
            PreventivePlan::firstOrCreate(['equipment_id' => $e->id, 'name' => 'Revisão 500h'], ['basis' => 'Horímetro', 'interval_value' => 500, 'next_value' => ceil($e->hour_meter / 500) * 500, 'warning_threshold' => 100, 'active' => true]);
            Component::firstOrCreate(['serial_number' => 'MOT-'.$i], ['name' => 'Motor', 'equipment_id' => $e->id, 'installed_on' => today()->subMonths(12), 'installation_meter' => 3000, 'useful_life' => 16000, 'replacement_cost' => 180000]);
            if ($i <= 10) {
                Tire::firstOrCreate(['fire_number' => 'PN-'.str_pad($i, 4, '0', STR_PAD_LEFT)], ['brand' => 'Michelin', 'model' => 'XDR', 'size' => '40.00R57', 'equipment_id' => $e->id, 'position' => 'Dianteiro direito', 'installed_on' => today()->subMonths(6), 'installation_meter' => 3500, 'initial_tread' => 85, 'current_tread' => 62, 'status' => 'Em Uso', 'cost' => 38000]);
            }
            OilAnalysis::firstOrCreate(['sample_number' => 'SOS-2026-'.$i], ['equipment_id' => $e->id, 'collected_on' => today()->subDays($i), 'laboratory' => 'Laboratório demonstração', 'result' => $i % 7 === 0 ? 'Atenção' : 'Normal', 'iron_ppm' => 10 + $i, 'silicon_ppm' => 3 + $i % 5, 'viscosity' => 14.2, 'recommendation' => 'Acompanhar na próxima coleta.']);
        }
        foreach (['Filtro de óleo motor', 'Filtro de combustível', 'Óleo hidráulico ISO 68', 'Correia do alternador', 'Mangueira hidráulica 1 pol.'] as $i => $name) {
            Part::firstOrCreate(['code' => 'PC-00'.($i + 1)], ['description' => $name, 'manufacturer' => 'OEM', 'category' => 'Manutenção', 'unit' => 'UN', 'unit_cost' => 85 + $i * 40, 'stock' => 0, 'minimum_stock' => 5, 'maximum_stock' => 100, 'location_id' => $location->id]);
        }
        $this->call(OperationalDemoSeeder::class);
    }
}
