<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventivePlan extends Model
{
    use Auditable;

    protected $table = 'planos_preventivos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['interval_value' => 'decimal:3', 'next_value' => 'decimal:3', 'next_date' => 'date', 'warning_threshold' => 'decimal:3', 'active' => 'boolean'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
