<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Access;
use Illuminate\Database\Eloquent\Model;

class MaintenancePolicy
{
    public function view(User $user, Model $record): bool
    {
        return Access::record($user, $record);
    }

    public function update(User $user, Model $record): bool
    {
        return Access::edit($user, class_basename($record));
    }

    public function delete(User $user, Model $record): bool
    {
        return Access::delete($user, class_basename($record));
    }
}
