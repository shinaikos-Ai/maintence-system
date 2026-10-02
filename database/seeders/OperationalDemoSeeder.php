<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Equipment;
use App\Models\MaintenanceRequest;
use App\Models\Part;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Maintenance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OperationalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demonstração somente fora de produção.');
        }
        if (MaintenanceRequest::where('description', 'like', '[DEMO]%')->exists()) {
            return;
        }
        $operator = User::where('role', 'Operador')->firstOrFail();
        $manager = User::where('role', 'Controlador')->firstOrFail();
        $mechanic = User::where('role', 'Mecânico')->firstOrFail();
        $service = app(Maintenance::class);
        Auth::login($manager);
        foreach (Part::all() as $p) {
            $service->stock($manager, $p, ['type' => 'Entrada', 'quantity' => 50, 'unit_cost' => $p->unit_cost, 'reason' => '[DEMO] Saldo inicial']);
        }
        $sample = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1sAAAAASUVORK5CYII=');
        Storage::disk('local')->put('attachments/demo-before.png', $sample);
        Storage::disk('local')->put('attachments/demo-after.png', $sample);
        foreach (Equipment::limit(10)->get() as $i => $e) {
            $e->update(['created_at' => now()->subMonths(7)]);
            Auth::login($operator);
            $r = $service->request($operator, ['equipment_id' => $e->id, 'hour_meter' => $e->hour_meter, 'priority' => $i % 2 ? 'Média' : 'Alta', 'description' => '[DEMO] '.['Vazamento hidráulico', 'Falha no sistema de carga', 'Inspeção de freios', 'Aquecimento do motor', 'Ruído na transmissão'][$i % 5]]);
            if ($i === 9) {
                continue;
            }
            Auth::login($manager);
            $service->review($manager, $r, 'Aprovada');
            $w = WorkOrder::where('maintenance_request_id', $r->id)->firstOrFail();
            $service->assign($manager, $w, ['assigned_to' => $mechanic->id, 'priority' => $r->priority, 'due_at' => now()->addDays($i % 3)]);
            Auth::login($mechanic);
            $service->start($mechanic, $w);
            if ($i === 8) {
                continue;
            }
            $usage = $service->requestPart($mechanic, $w, ['part_id' => Part::first()->id, 'quantity' => 1]);
            if ($i === 7) {
                continue;
            }
            Auth::login($manager);
            $service->release($manager, $usage, true);
            Auth::login($mechanic);
            $service->labor($mechanic, $w, ['hours' => 1 + $i % 3, 'hourly_rate' => 85, 'description' => '[DEMO] Diagnóstico e reparo', 'worked_on' => today()->toDateString()]);
            $service->diagnose($mechanic, $w, ['diagnosis' => '[DEMO] Desgaste identificado na inspeção', 'root_cause' => 'Desgaste por uso', 'service_performed' => 'Substituição e teste funcional']);
            foreach (['Antes' => 'before', 'Depois' => 'after'] as $kind => $file) {
                Attachment::create(['work_order_id' => $w->id, 'kind' => $kind, 'path' => 'attachments/demo-'.$file.'.png', 'user_id' => $mechanic->id]);
            }
            $service->close($mechanic, $w, ['closing_hour_meter' => $e->hour_meter + 5, 'closing_mileage' => $e->mileage + 10]);
            // Demo-only historical dates for populated trend charts.
            $finished = now()->subMonths($i % 6)->subHours(1);
            $w->update(['created_at' => $finished->copy()->subHours(4), 'started_at' => $finished->copy()->subHours(3), 'finished_at' => $finished, 'downtime_hours' => 3]);
        }
        Auth::logout();
        $service->preventive();
    }
}
