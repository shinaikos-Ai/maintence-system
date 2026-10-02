<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\PreventivePlan;
use App\Models\Signature;
use App\Models\Tire;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Lifecycle;
use App\Services\Maintenance;
use App\Services\Reports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_orders_do_not_double_count_downtime(): void
    {
        $e = Equipment::create(['tag' => 'E1', 'status' => 'Operando', 'created_at' => now()->subDays(5)]);
        $from = now()->startOfDay();
        $to = $from->copy()->addHours(10);
        foreach ([[1, 4], [2, 5]] as [$a,$b]) {
            WorkOrder::create(['equipment_id' => $e->id, 'type' => 'Corretiva', 'status' => 'Encerrada', 'started_at' => $from->copy()->addHours($a), 'finished_at' => $from->copy()->addHours($b), 'total_cost' => 100]);
        }
        $row = app(Reports::class)->equipment($from, $to)[0];
        $this->assertEquals(4, $row['Horas paradas']);
        $this->assertEquals(60, $row['Disponibilidade %']);
        $this->assertEquals(3, $row['MTBF h']);
        $this->assertEquals(3, $row['MTTR h']);
        $this->assertEquals(200, $row['Custo R$']);
    }

    public function test_calendar_and_mileage_plans_respect_thresholds(): void
    {
        $e = Equipment::create(['tag' => 'E1', 'status' => 'Operando', 'mileage' => 5000]);
        PreventivePlan::create(['equipment_id' => $e->id, 'name' => 'Calendar', 'basis' => 'Calendário', 'interval_value' => 30, 'next_date' => today()->addDays(3), 'warning_threshold' => 5, 'active' => true]);
        PreventivePlan::create(['equipment_id' => $e->id, 'name' => 'Km', 'basis' => 'Quilometragem', 'interval_value' => 1000, 'next_value' => 5100, 'warning_threshold' => 100, 'active' => true]);
        PreventivePlan::create(['equipment_id' => $e->id, 'name' => 'Future', 'basis' => 'Quilometragem', 'interval_value' => 1000, 'next_value' => 6000, 'warning_threshold' => 100, 'active' => true]);
        $this->assertSame(2, app(Maintenance::class)->preventive());
        $this->assertSame(0, app(Maintenance::class)->preventive());
    }

    public function test_signatures_bind_to_closed_order_and_reject_duplicates(): void
    {
        Storage::fake('local');
        $u = User::factory()->create(['role' => 'Gerente']);
        $this->actingAs($u);
        $e = Equipment::create(['tag' => 'E1']);
        $w = WorkOrder::create(['equipment_id' => $e->id, 'status' => 'Encerrada']);
        Storage::disk('local')->put('signatures/test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1sAAAAASUVORK5CYII='));
        app(Maintenance::class)->sign($u, $w, 'signatures/test.png');
        $this->assertSame(64, strlen(Signature::first()->document_hash));
        $this->expectException(ValidationException::class);
        app(Maintenance::class)->sign($u, $w, 'signatures/test.png');
    }

    public function test_tire_refurbishment_records_cost_and_history(): void
    {
        $u = User::factory()->create(['role' => 'Controlador']);
        $this->actingAs($u);
        $e = Equipment::create(['tag' => 'E1']);
        $t = Tire::create(['equipment_id' => $e->id, 'fire_number' => 'T1', 'status' => 'Em Uso']);
        app(Lifecycle::class)->record($u, $t, ['action' => 'Reforma', 'reading' => 1200, 'cost' => 2500, 'description' => 'Recapagem']);
        $this->assertSame('Reforma', $t->fresh()->status);
        $this->assertDatabaseHas('historico_pneus', ['tire_id' => $t->id, 'cost' => 2500]);
        $this->assertDatabaseHas('historico_equipamentos', ['equipment_id' => $e->id, 'event' => 'Reforma de pneu']);
    }

    public function test_equipment_index_handles_more_than_500_records(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Administrador']));
        $rows = [];
        for ($i = 0; $i < 550; $i++) {
            $rows[] = ['tag' => 'EQ-'.$i, 'status' => 'Operando', 'created_at' => now(), 'updated_at' => now()];
        }
        Equipment::insert($rows);
        $this->get('/admin/equipment')->assertOk();
        $this->assertSame(550,Equipment::count());
    }
}
