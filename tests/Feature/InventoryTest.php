<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Part;
use App\Models\User;
use App\Services\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_transfer_preserves_total_and_consumption_uses_balances(): void
    {
        $u = User::factory()->create(['role' => 'Controlador']);
        $this->actingAs($u);
        $a = Location::create(['name' => 'A']);
        $b = Location::create(['name' => 'B']);
        $p = Part::create(['stock' => 10, 'unit_cost' => 20, 'location_id' => $a->id]);
        app(Inventory::class)->move($u, $p, ['type' => 'Transferência', 'quantity' => 3, 'unit_cost' => 20, 'from_location_id' => $a->id, 'to_location_id' => $b->id, 'reason' => 'Abastecer frente de serviço']);
        $this->assertEquals(10, $p->fresh()->stock);
        $this->assertDatabaseHas('saldos_estoque', ['location_id' => $a->id, 'quantity' => 7]);
        $this->assertDatabaseHas('saldos_estoque', ['location_id' => $b->id, 'quantity' => 3]);
    }

    public function test_inventory_count_adjusts_only_one_location(): void
    {
        $u = User::factory()->create(['role' => 'Controlador']);
        $this->actingAs($u);
        $a = Location::create(['name' => 'A']);
        $b = Location::create(['name' => 'B']);
        $p = Part::create(['stock' => 10, 'location_id' => $a->id, 'unit_cost' => 20]);
        $inv = app(Inventory::class);
        $inv->move($u, $p, ['type' => 'Transferência', 'quantity' => 3, 'unit_cost' => 20, 'to_location_id' => $b->id, 'reason' => 'Transferência']);
        $inv->move($u, $p, ['type' => 'Inventário', 'quantity' => 2, 'unit_cost' => 20, 'from_location_id' => $b->id, 'reason' => 'Contagem física']);
        $this->assertEquals(9, $p->fresh()->stock);
        $this->assertDatabaseHas('saldos_estoque', ['location_id' => $a->id, 'quantity' => 7]);
        $this->assertDatabaseHas('saldos_estoque', ['location_id' => $b->id, 'quantity' => 2]);
    }

    public function test_receipts_update_weighted_average_cost(): void
    {
        $u = User::factory()->create(['role' => 'Controlador']);
        $this->actingAs($u);
        $p = Part::create(['stock' => 10, 'unit_cost' => 20]);
        app(Inventory::class)->move($u, $p, ['type' => 'Entrada', 'quantity' => 10, 'unit_cost' => 40, 'reason' => 'Compra']);
        $this->assertEquals(20, $p->fresh()->stock);
        $this->assertEquals(30, $p->fresh()->unit_cost);
    }
}
