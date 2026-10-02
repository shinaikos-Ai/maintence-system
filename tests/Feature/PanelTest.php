<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceRequest\ManageRecords;
use App\Models\Equipment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_pages_render(): void
    {
        $user = User::factory()->create(['role' => 'Administrador']);
        $this->actingAs($user);
        Equipment::create(['tag' => 'CAT-001', 'manufacturer' => 'CAT', 'model' => '793F', 'status' => 'Operando']);
        $this->get('/admin')->assertOk();
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }
        $this->get('/admin/reports')->assertOk();
        $this->get('/admin/requests-board')->assertOk();
    }

    public function test_operator_access_is_restricted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Operador']));
        $this->get('/admin/maintenance-requests')->assertOk();
        foreach (['work-orders', 'parts', 'users', 'reports', 'audits'] as $page) {
            $this->get('/admin/'.$page)->assertForbidden();
        }
    }

    public function test_pdf_xlsx_and_qr_exports_work(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Gerente']));
        $e = Equipment::create(['tag' => 'CAT-001', 'manufacturer' => 'CAT', 'model' => '793F', 'status' => 'Operando']);
        $query = '?type=frota&from='.today()->startOfMonth()->toDateString().'&to='.today()->toDateString();
        $this->get('/reports/pdf'.$query)->assertOk()->assertHeader('content-type', 'application/pdf');
        $response = $this->get('/reports/xlsx'.$query)->assertOk();
        $this->assertStringStartsWith('PK', $response->streamedContent());
        $this->get(route('equipment.qr', $e))->assertOk()->assertSee('<svg', false);
    }

    public function test_request_creation_through_livewire_generates_order(): void
    {
        $u = User::factory()->create(['role' => 'Operador']);
        $this->actingAs($u);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $e = Equipment::create(['tag' => 'CAT-001', 'hour_meter' => 100, 'status' => 'Operando']);
        Livewire::test(ManageRecords::class)
            ->callAction('create', data: ['equipment_id' => $e->id, 'hour_meter' => 101, 'priority' => 'Alta', 'description' => 'Vazamento'])
            ->assertHasNoActionErrors();
        $this->assertDatabaseHas('solicitacoes', ['equipment_id' => $e->id, 'requested_by' => $u->id]);
        $this->assertDatabaseCount('ordens_servico', 1);
    }
}
