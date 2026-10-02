<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentHistory extends Model
{
    use Auditable;

    protected $table = 'historico_equipamentos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['hour_meter' => 'decimal:3', 'mileage' => 'decimal:3', 'cost' => 'decimal:2'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
}
