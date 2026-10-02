<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Component extends Model
{
    use Auditable;

    protected $table = 'componentes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['installed_on' => 'date', 'installation_meter' => 'decimal:3', 'worked_hours' => 'decimal:3', 'overhaul_hours' => 'decimal:3', 'useful_life' => 'decimal:3', 'replacement_cost' => 'decimal:2'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
