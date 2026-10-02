<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OilAnalysis extends Model
{
    use Auditable;

    protected $table = 'analises_sos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['collected_on' => 'date', 'iron_ppm' => 'decimal:3', 'silicon_ppm' => 'decimal:3', 'viscosity' => 'decimal:3'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }
}
