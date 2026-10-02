<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Part extends Model
{
    use Auditable;

    protected $table = 'pecas';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2', 'stock' => 'decimal:3', 'minimum_stock' => 'decimal:3', 'maximum_stock' => 'decimal:3'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
