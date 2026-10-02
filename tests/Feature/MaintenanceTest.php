<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Audit;
use App\Models\Equipment;
use App\Models\MaintenanceRequest;
use App\Models\Part;
use App\Models\PreventivePlan;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Catalog;
use App\Services\Maintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $mechanic;

    private User $operator;

    private Equipment $equipment;

    private Maintenance $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->manager = User::factory()->create(['role' => 'Controlador']);
        $this->mechanic = User::factory()->create(['role' => 'Mecânico']);
        $this->operator = User::factory()->create(['role' => 'Operador']);
        $this->equipment = Equipment::create(['tag' => 'CAT-001', 'manufacturer' => 'CAT', 'model' => '793F', 'hour_meter' => 1000, 'mileage' => 100, 'status' => 'Operando']);
        $this->service = app(Maintenance::class);
        $this->actingAs($this->manager);
    }

    private function order(): WorkOrder
    {
        $r = $this->service->request($this->operator, ['equipment_id' => $this->equipment->id, 'hour_meter' => 1001, 'priority' => 'Alta', 'description' => 'Vazamento hidráulico']);
        $this->service->review($this->manager, $r, 'Aprovada');
        $w = WorkOrder::where('maintenance_request_id', $r->id)->firstOrFail();
        $this->service->assign($this->manager, $w, ['assigned_to' => $this->mechanic->id, 'priority' => 'Alta', 'due_at' => now()->addDay()]);
        $this->service->start($this->mechanic, $w->fresh());

        return $w->fresh();
    }

    public function test_full_order_flow_preserves_cost_stock_history_and_meter(): void
    {
        $w = $this->order();
        $part = Part::create(['code' => 'F1', 'description' => 'Filtro', 'stock' => 10, 'unit_cost' => 25]);
        $usage = $this->service->requestPart($this->mechanic, $w, ['part_id' => $part->id, 'quantity' => 2]);
        $this->service->release($this->manager, $usage, true);
        $this->service->labor($this->mechanic, $w, ['hours' => 2, 'hourly_rate' => 80, 'description' => 'Troca', 'worked_on' => today()->toDateString()]);
        $this->service->diagnose($this->mechanic, $w, ['diagnosis' => 'Filtro rompido', 'root_cause' => 'Desgaste', 'service_performed' => 'Substituição']);
        foreach (['Antes', 'Depois'] as $kind) {
            Attachment::create(['work_order_id' => $w->id, 'kind' => $kind, 'path' => 'attachments/photo.png', 'user_id' => $this->mechanic->id]);
        }
        $this->service->close($this->mechanic, $w, ['closing_hour_meter' => 1002, 'closing_mileage' => 110]);
        $this->assertEquals(8, $part->fresh()->stock);
        $this->assertEquals(210, $w->fresh()->total_cost);
        $this->assertSame('Encerrada', $w->fresh()->status);
        $this->assertEquals(1002, $this->equipment->fresh()->hour_meter);
        $this->assertDatabaseHas('historico_equipamentos', ['work_order_id' => $w->id, 'event' => 'OS encerrada']);
        $this->assertDatabaseCount('movimentacoes_estoque', 1);
        $this->assertGreaterThan(5, Audit::count());
    }

    public function test_duplicate_release_never_consumes_stock_twice(): void
    {
        $w = $this->order();
        $p = Part::create(['stock' => 3, 'unit_cost' => 10]);
        $usage = $this->service->requestPart($this->mechanic, $w, ['part_id' => $p->id, 'quantity' => 2]);
        $this->service->release($this->manager, $usage, true);
        try {
            $this->service->release($this->manager, $usage, true);
            $this->fail('Duplicate accepted');
        } catch (ValidationException) {
        }
        $this->assertEquals(1, $p->fresh()->stock);
        $this->assertDatabaseCount('movimentacoes_estoque', 1);
    }

    public function test_insufficient_stock_rolls_back_release(): void
    {
        $w = $this->order();
        $p = Part::create(['stock' => 1, 'unit_cost' => 10]);
        $usage = $this->service->requestPart($this->mechanic, $w, ['part_id' => $p->id, 'quantity' => 2]);
        try {
            $this->service->release($this->manager, $usage, true);
            $this->fail('Negative stock');
        } catch (ValidationException) {
        }
        $this->assertEquals(1, $p->fresh()->stock);
        $this->assertSame('Solicitada', $usage->fresh()->status);
        $this->assertDatabaseCount('movimentacoes_estoque', 0);
    }

    public function test_operator_cannot_assign_order(): void
    {
        $w = $this->order();
        $this->expectException(HttpException::class);
        $this->service->assign($this->operator, $w, []);
    }

    public function test_mechanic_cannot_mutate_another_mechanics_order(): void
    {
        $w = $this->order();
        $other = User::factory()->create(['role' => 'Mecânico']);
        $this->expectException(HttpException::class);
        $this->service->requestPart($other, $w, ['part_id' => Part::create()->id, 'quantity' => 1]);
    }

    public function test_meter_regression_does_not_create_request_or_order(): void
    {
        $this->expectException(ValidationException::class);
        try {
            $this->service->request($this->operator, ['equipment_id' => $this->equipment->id, 'hour_meter' => 999, 'priority' => 'Alta', 'description' => 'Teste']);
        } finally {
            $this->assertDatabaseCount('solicitacoes', 0);
            $this->assertDatabaseCount('ordens_servico', 0);
        }
    }

    public function test_preventive_generation_is_idempotent(): void
    {
        PreventivePlan::create(['equipment_id' => $this->equipment->id, 'name' => '500h', 'basis' => 'Horímetro', 'interval_value' => 500, 'next_value' => 1100, 'warning_threshold' => 100, 'active' => true]);
        $this->assertSame(1, $this->service->preventive());
        $this->assertSame(0, $this->service->preventive());
        $this->assertDatabaseCount('execucoes_preventivas', 1);
        $this->assertDatabaseCount('ordens_servico', 1);
    }

    public function test_closing_requires_diagnosis_labor_and_photos(): void
    {
        $w = $this->order();
        $this->expectException(ValidationException::class);
        $this->service->close($this->mechanic, $w, ['closing_hour_meter' => 1002, 'closing_mileage' => 100]);
    }

    public function test_rejected_request_cannot_start(): void
    {
        $w = $this->order();
        $w->update(['status' => 'Aberta', 'started_at' => null]);
        $r = MaintenanceRequest::find($w->maintenance_request_id);
        $this->service->review($this->manager, $r, 'Rejeitada');
        $this->expectException(ValidationException::class);
        $this->service->start($this->mechanic, $w);
    }

    public function test_private_files_and_reports_reject_unauthorized_users(): void
    {
        Storage::disk('local')->put('attachments/test.pdf', 'private');
        $a = Attachment::create(['user_id' => $this->mechanic->id, 'path' => 'attachments/test.pdf', 'kind' => 'Documento', 'equipment_id' => $this->equipment->id]);
        $this->actingAs($this->operator)->get(route('attachment.download', $a))->assertForbidden();
        $this->get('/reports/pdf?type=frota&from=2026-01-01&to=2026-12-31')->assertForbidden();
        $this->actingAs($this->manager)->get(route('attachment.download', $a))->assertOk();
        $this->manager->update(['active' => false]);
        $this->get(route('attachment.download', $a))->assertForbidden();
    }

    public function test_audit_never_contains_passwords(): void
    {
        $audit = Audit::where('subject_type', 'User')->firstOrFail();
        $this->assertArrayNotHasKey('password', $audit->new_values);
    }

    public function test_attachment_cannot_reference_unowned_request(): void
    {
        $r = $this->service->request($this->operator, ['equipment_id' => $this->equipment->id, 'hour_meter' => 1001, 'priority' => 'Alta', 'description' => 'Teste']);
        $other = User::factory()->create(['role' => 'Operador']);
        $this->expectException(HttpException::class);
        app(Catalog::class)->save($other,'Attachment',['maintenance_request_id' => $r->id, 'kind' => 'Documento', 'path' => 'attachments/test.pdf']);
    }
}
