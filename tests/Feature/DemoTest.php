<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_modules_and_record_details_render(): void
    {
        $password = bin2hex(random_bytes(16));
        putenv('DEMO_PASSWORD='.$password);
        $_ENV['DEMO_PASSWORD'] = $password;
        try {
            $this->seed(DatabaseSeeder::class);
            $this->assertSame(25, Equipment::count());
            $this->assertSame(7, WorkOrder::where('status', 'Encerrada')->count());
            $this->flushSession();
            $this->actingAs(User::where('role', 'Administrador')->firstOrFail());
            Filament::setCurrentPanel(Filament::getPanel('admin'));
            foreach (Filament::getPanel('admin')->getResources() as $resource) {
                $this->get($resource::getUrl('index'))->assertOk();
                $record = $resource::getModel()::first();
                if (! $record) {
                    continue;
                }
                $class = 'App\\Filament\\Resources\\'.$resource::kind().'\\ManageRecords';
                Livewire::test($class)->mountAction(TestAction::make('view')->table($record))->assertActionMounted(TestAction::make('view')->table($record));
            }
            foreach (['/admin', '/admin/schedule', '/admin/reports', '/admin/requests-board'] as $path) {
                $this->get($path)->assertOk();
            }
        } finally {
            putenv('DEMO_PASSWORD');
            unset($_ENV['DEMO_PASSWORD']);
        }
    }
}
