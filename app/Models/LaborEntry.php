<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaborEntry extends Model
{
    use Auditable;

    protected $table = 'mao_obra_os';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['hours' => 'decimal:3', 'hourly_rate' => 'decimal:2', 'worked_on' => 'date'];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
