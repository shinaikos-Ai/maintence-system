<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use Concerns\Auditable;

    protected $table = 'saldos_estoque';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
