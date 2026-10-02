<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use Auditable;

    protected $table = 'centros_custo';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }
}
