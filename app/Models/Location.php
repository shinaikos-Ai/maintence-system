<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use Auditable;

    protected $table = 'localizacoes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }
}
