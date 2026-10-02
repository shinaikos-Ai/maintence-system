<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventiveExecution extends Model
{
    use Auditable;

    protected $table = 'execucoes_preventivas';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'reading' => 'decimal:3'];
    }

    public function preventivePlan(): BelongsTo
    {
        return $this->belongsTo(PreventivePlan::class, 'preventive_plan_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
}
