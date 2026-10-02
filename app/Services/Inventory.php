<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** All callers lock the part first, keeping lock order consistent. */
class Inventory
{
    private function require(bool $valid, string $message): void
    {
        if (! $valid) {
            throw ValidationException::withMessages(['quantity' => $message]);
        }
    }

    private function initialize(Part $p): void
    {
        if (StockBalance::where('part_id', $p->id)->exists()) {
            return;
        }
        $location = $p->location_id ?? Location::firstOrCreate(['name' => 'Estoque principal'])->id;
        if (! $p->location_id) {
            $p->update(['location_id' => $location]);
        }
        StockBalance::create(['part_id' => $p->id, 'location_id' => $location, 'quantity' => $p->stock]);
    }

    public function consume(Part $p, float $quantity, int $orderId, User $u): void
    {
        $this->initialize($p);
        $this->require($p->stock >= $quantity, 'Estoque insuficiente.');
        $remaining = $quantity;
        foreach (StockBalance::where('part_id', $p->id)->orderBy('id')->lockForUpdate()->get() as $balance) {
            $take = min((float) $balance->quantity, $remaining);
            if ($take <= 0) {
                continue;
            }
            $balance->update(['quantity' => $balance->quantity - $take]);
            $remaining -= $take;
            StockMovement::create(['part_id' => $p->id, 'work_order_id' => $orderId, 'type' => 'Saída', 'quantity' => $take, 'unit_cost' => $p->unit_cost, 'from_location_id' => $balance->location_id, 'reason' => 'Consumo OS #'.$orderId, 'user_id' => $u->id]);
        }
        $this->require($remaining < 0.0001, 'Saldos por localização inconsistentes.');
        $p->update(['stock' => $p->stock - $quantity]);
    }

    public function move(User $u, Part $part, array $data): void
    {
        abort_unless($u->manages(), 403);
        $data = Validator::make($data, ['type' => 'required|in:Entrada,Saída,Transferência,Ajuste,Inventário', 'quantity' => 'required|numeric|min:0', 'unit_cost' => 'required|numeric|min:0', 'from_location_id' => 'nullable|exists:localizacoes,id', 'to_location_id' => 'nullable|exists:localizacoes,id', 'reason' => 'required|string'])->validate();
        DB::transaction(function () use ($u, $part, $data) {
            $p = Part::lockForUpdate()->findOrFail($part->id);
            $this->initialize($p);
            $origin = $data['from_location_id'] ?? $p->location_id;
            $destination = $data['to_location_id'] ?? $p->location_id;
            $location = $data['type'] === 'Entrada' ? $destination : $origin;
            $balance = StockBalance::firstOrCreate(['part_id' => $p->id, 'location_id' => $location], ['quantity' => 0]);
            $balance = StockBalance::lockForUpdate()->findOrFail($balance->id);
            $qty = (float) $data['quantity'];
            $old = (float) $balance->quantity;
            if (in_array($data['type'], ['Entrada', 'Saída', 'Transferência'])) {
                $this->require($qty > 0, 'Quantidade deve ser positiva.');
            }
            $new = match ($data['type']) {
                'Entrada' => $old + $qty,'Saída','Transferência' => $old - $qty,default => $qty
            };
            $this->require($new >= 0, 'Estoque insuficiente na localização selecionada.');
            $balance->update(['quantity' => $new]);
            if ($data['type'] === 'Transferência') {
                $this->require($destination !== $origin, 'Escolha uma localização de destino diferente.');
                $to = StockBalance::firstOrCreate(['part_id' => $p->id, 'location_id' => $destination], ['quantity' => 0]);
                $to->increment('quantity', $qty);
            }
            $total = StockBalance::where('part_id', $p->id)->sum('quantity');
            if ($data['type'] === 'Entrada' && $total > 0) {
                $p->unit_cost = round(($p->stock * $p->unit_cost + $qty * $data['unit_cost']) / $total, 2);
            }
            $p->stock = $total;
            $p->save();
            StockMovement::create(['part_id' => $p->id, 'type' => $data['type'], 'quantity' => $qty, 'unit_cost' => $data['unit_cost'], 'from_location_id' => $data['type'] === 'Entrada' ? null : $origin, 'to_location_id' => in_array($data['type'], ['Entrada', 'Transferência']) ? $destination : null, 'reason' => $data['reason'], 'user_id' => $u->id]);
        });
    }
}
