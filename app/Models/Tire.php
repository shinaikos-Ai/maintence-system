<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tire extends Model
{
    use Auditable;

    protected $table = 'pneus';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['installed_on' => 'date', 'installation_meter' => 'decimal:3', 'worked_hours' => 'decimal:3', 'initial_tread' => 'decimal:3', 'current_tread' => 'decimal:3', 'cost' => 'decimal:2'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
