<?php

use App\Models\User;
use App\Services\Maintenance;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('maintenance:preventives', function () {
    $this->info(app(Maintenance::class)->preventive().' OS preventivas geradas.');
})->purpose('Gerar preventivas sem duplicar planos pendentes');
Schedule::command('maintenance:preventives')->hourly()->withoutOverlapping();
Artisan::command('maintenance:admin {email} {name}', function () {
    $password = $this->secret('Senha (mínimo 12 caracteres)');
    if (strlen($password ?? '') < 12) {
        $this->error('Senha muito curta.');

        return 1;
    }
    User::create(['email' => $this->argument('email'), 'name' => $this->argument('name'), 'password' => $password, 'role' => 'Administrador', 'active' => true]);
    $this->info('Administrador criado.');
});
