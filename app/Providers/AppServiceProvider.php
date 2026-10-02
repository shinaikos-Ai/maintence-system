<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\MaintenancePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        foreach (array_keys(config('maintenance')) as $model) {
            Gate::policy('App\\Models\\'.$model, MaintenancePolicy::class);
        }
        Gate::policy(User::class, MaintenancePolicy::class);
    }
}
