<?php

use App\Models\User;
use App\Services\Access;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('permissoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('perfil_id')->constrained('perfis');
            $t->string('resource');
            $t->boolean('can_view');
            $t->boolean('can_create');
            $t->boolean('can_edit');
            $t->unique(['perfil_id', 'resource']);
        });
        foreach (['Operador', 'Mecânico', 'Controlador', 'Gerente', 'Administrador'] as $role) {
            $id = DB::table('perfis')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);
            $u = new User(['role' => $role, 'active' => true]);
            foreach ([...array_keys(config('maintenance')), 'User'] as $kind) {
                DB::table('permissoes')->insert(['perfil_id' => $id, 'resource' => $kind, 'can_view' => Access::view($u, $kind), 'can_create' => Access::create($u, $kind), 'can_edit' => Access::edit($u, $kind)]);
            }
        }
        Schema::table('ordens_servico', function (Blueprint $t) {
            $t->index(['equipment_id', 'status']);
            $t->index('finished_at');
            $t->index('started_at');
        });
        Schema::table('auditoria', function (Blueprint $t) {
            $t->index(['subject_type', 'subject_id']);
        });
        Schema::table('historico_equipamentos', function (Blueprint $t) {
            $t->index(['equipment_id', 'created_at']);
        });
        Schema::table('analises_sos', function (Blueprint $t) {
            $t->index(['equipment_id', 'collected_on']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pecas ADD CONSTRAINT pecas_stock_nonnegative CHECK (stock >= 0)');
            DB::statement('ALTER TABLE pecas_os ADD CONSTRAINT pecas_os_positive CHECK (quantity > 0 AND unit_cost >= 0)');
            DB::statement('ALTER TABLE mao_obra_os ADD CONSTRAINT mao_obra_positive CHECK (hours > 0 AND hours <= 24 AND hourly_rate >= 0)');
            DB::statement('ALTER TABLE equipamentos ADD CONSTRAINT equipamentos_readings_nonnegative CHECK (hour_meter >= 0 AND mileage >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (['pecas' => 'pecas_stock_nonnegative', 'pecas_os' => 'pecas_os_positive', 'mao_obra_os' => 'mao_obra_positive', 'equipamentos' => 'equipamentos_readings_nonnegative'] as $table => $constraint) {
                DB::statement('ALTER TABLE '.$table.' DROP CONSTRAINT '.$constraint);
            }
        }
        Schema::table('ordens_servico', function (Blueprint $t) {
            $t->dropIndex(['equipment_id', 'status']);
            $t->dropIndex(['finished_at']);
            $t->dropIndex(['started_at']);
        });
        Schema::table('auditoria', fn (Blueprint $t) => $t->dropIndex(['subject_type', 'subject_id']));
        Schema::table('historico_equipamentos', fn (Blueprint $t) => $t->dropIndex(['equipment_id', 'created_at']));
        Schema::table('analises_sos', fn (Blueprint $t) => $t->dropIndex(['equipment_id', 'collected_on']));
        Schema::dropIfExists('permissoes');
        Schema::dropIfExists('perfis');
    }
};
