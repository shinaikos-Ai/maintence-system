<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Concerns\Auditable, HasFactory, Notifiable;

    protected $attributes = ['role' => 'Operador', 'active' => true];

    protected $fillable = ['name', 'email', 'password', 'role', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'active' => 'boolean'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->active;
    }

    public function manages(): bool
    {
        return $this->active && in_array($this->role, ['Administrador', 'Controlador']);
    }

    public function reports(): bool
    {
        return $this->active && in_array($this->role, ['Administrador', 'Controlador', 'Gerente']);
    }
}
