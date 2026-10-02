<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentHistory extends Model
{
    use Auditable;

    protected $table = 'historico_componentes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reading' => 'decimal:3', 'cost' => 'decimal:2'];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
