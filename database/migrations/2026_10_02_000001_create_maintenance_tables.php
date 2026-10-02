<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('Operador')->index();
            $t->boolean('active')->default(true);
        });
        Schema::create('contratos', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('code')->nullable()->unique();
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->timestamps();
        });
        Schema::create('centros_custo', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable()->unique();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('localizacoes', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('equipamentos', function (Blueprint $t) {
            $t->id();
            $t->string('tag')->nullable()->unique();
            $t->string('asset_number')->nullable();
            $t->string('manufacturer')->nullable();
            $t->string('model')->nullable();
            $t->string('serial_number')->nullable();
            $t->unsignedInteger('year')->nullable();
            $t->decimal('hour_meter', 14, 3)->default(0);
            $t->decimal('mileage', 14, 3)->default(0);
            $t->foreignId('cost_center_id')->nullable()->constrained('centros_custo')->restrictOnDelete();
            $t->foreignId('contract_id')->nullable()->constrained('contratos')->restrictOnDelete();
            $t->foreignId('location_id')->nullable()->constrained('localizacoes')->restrictOnDelete();
            $t->string('criticality')->nullable();
            $t->string('status')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('solicitacoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->decimal('hour_meter', 14, 3)->default(0);
            $t->string('priority')->nullable();
            $t->text('description')->nullable();
            $t->foreignId('requested_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('status')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('ordens_servico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->foreignId('maintenance_request_id')->nullable()->constrained('solicitacoes')->restrictOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('type')->nullable();
            $t->string('priority')->nullable();
            $t->string('status')->nullable()->index();
            $t->text('failure_description')->nullable();
            $t->text('diagnosis')->nullable();
            $t->text('root_cause')->nullable();
            $t->text('service_performed')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamp('due_at')->nullable()->index();
            $t->decimal('downtime_hours', 14, 3)->default(0);
            $t->decimal('closing_hour_meter', 14, 3)->default(0);
            $t->decimal('closing_mileage', 14, 3)->default(0);
            $t->decimal('total_cost', 14, 2)->default(0);
            $t->unique('maintenance_request_id');
            $t->timestamps();
        });
        Schema::create('pecas', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable()->unique();
            $t->string('description')->nullable();
            $t->string('manufacturer')->nullable();
            $t->string('category')->nullable();
            $t->string('unit')->nullable();
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->decimal('stock', 14, 3)->default(0);
            $t->decimal('minimum_stock', 14, 3)->default(0);
            $t->decimal('maximum_stock', 14, 3)->default(0);
            $t->foreignId('location_id')->nullable()->constrained('localizacoes')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('movimentacoes_estoque', function (Blueprint $t) {
            $t->id();
            $t->foreignId('part_id')->nullable()->constrained('pecas')->restrictOnDelete();
            $t->foreignId('work_order_id')->nullable()->constrained('ordens_servico')->restrictOnDelete();
            $t->string('type')->nullable();
            $t->decimal('quantity', 14, 3)->default(0);
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->foreignId('from_location_id')->nullable()->constrained('localizacoes')->restrictOnDelete();
            $t->foreignId('to_location_id')->nullable()->constrained('localizacoes')->restrictOnDelete();
            $t->text('reason')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('mao_obra_os', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_order_id')->nullable()->constrained('ordens_servico')->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->decimal('hours', 14, 3)->default(0);
            $t->decimal('hourly_rate', 14, 2)->default(0);
            $t->text('description')->nullable();
            $t->date('worked_on')->nullable();
            $t->timestamps();
        });
        Schema::create('pecas_os', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_order_id')->nullable()->constrained('ordens_servico')->restrictOnDelete();
            $t->foreignId('part_id')->nullable()->constrained('pecas')->restrictOnDelete();
            $t->decimal('quantity', 14, 3)->default(0);
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->string('status')->nullable()->index();
            $t->foreignId('requested_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('planos_preventivos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->string('name')->nullable();
            $t->string('basis')->nullable();
            $t->decimal('interval_value', 14, 3)->default(0);
            $t->decimal('next_value', 14, 3)->default(0);
            $t->date('next_date')->nullable()->index();
            $t->decimal('warning_threshold', 14, 3)->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('execucoes_preventivas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('preventive_plan_id')->nullable()->constrained('planos_preventivos')->restrictOnDelete();
            $t->foreignId('work_order_id')->nullable()->constrained('ordens_servico')->restrictOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->decimal('reading', 14, 3)->default(0);
            $t->unique('work_order_id');
            $t->timestamps();
        });
        Schema::create('componentes', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('serial_number')->nullable();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->date('installed_on')->nullable();
            $t->decimal('installation_meter', 14, 3)->default(0);
            $t->decimal('worked_hours', 14, 3)->default(0);
            $t->decimal('overhaul_hours', 14, 3)->default(0);
            $t->decimal('useful_life', 14, 3)->default(0);
            $t->decimal('replacement_cost', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('historico_componentes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('component_id')->nullable()->constrained('componentes')->restrictOnDelete();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->string('action')->nullable();
            $t->decimal('reading', 14, 3)->default(0);
            $t->decimal('cost', 14, 2)->default(0);
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('pneus', function (Blueprint $t) {
            $t->id();
            $t->string('fire_number')->nullable()->unique();
            $t->string('brand')->nullable();
            $t->string('model')->nullable();
            $t->string('size')->nullable();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->string('position')->nullable();
            $t->date('installed_on')->nullable();
            $t->decimal('installation_meter', 14, 3)->default(0);
            $t->decimal('worked_hours', 14, 3)->default(0);
            $t->decimal('initial_tread', 14, 3)->default(0);
            $t->decimal('current_tread', 14, 3)->default(0);
            $t->string('status')->nullable()->index();
            $t->decimal('cost', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('historico_pneus', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tire_id')->nullable()->constrained('pneus')->restrictOnDelete();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->string('action')->nullable();
            $t->decimal('reading', 14, 3)->default(0);
            $t->decimal('cost', 14, 2)->default(0);
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('analises_sos', function (Blueprint $t) {
            $t->id();
            $t->string('sample_number')->nullable()->unique();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->foreignId('component_id')->nullable()->constrained('componentes')->restrictOnDelete();
            $t->date('collected_on')->nullable();
            $t->string('laboratory')->nullable();
            $t->string('result')->nullable();
            $t->decimal('iron_ppm', 14, 3)->default(0);
            $t->decimal('silicon_ppm', 14, 3)->default(0);
            $t->decimal('viscosity', 14, 3)->default(0);
            $t->text('recommendation')->nullable();
            $t->timestamps();
        });
        Schema::create('historico_equipamentos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->foreignId('work_order_id')->nullable()->constrained('ordens_servico')->restrictOnDelete();
            $t->string('event')->nullable();
            $t->decimal('hour_meter', 14, 3)->default(0);
            $t->decimal('mileage', 14, 3)->default(0);
            $t->decimal('cost', 14, 2)->default(0);
            $t->text('details')->nullable();
            $t->timestamps();
        });
        Schema::create('anexos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->foreignId('work_order_id')->nullable()->constrained('ordens_servico')->restrictOnDelete();
            $t->foreignId('maintenance_request_id')->nullable()->constrained('solicitacoes')->restrictOnDelete();
            $t->foreignId('oil_analysis_id')->nullable()->constrained('analises_sos')->restrictOnDelete();
            $t->string('kind')->nullable();
            $t->string('path')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('assinaturas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_order_id')->nullable()->constrained('ordens_servico')->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('role')->nullable();
            $t->string('image_path')->nullable();
            $t->timestamp('signed_at')->nullable();
            $t->string('document_hash')->nullable();
            $t->unique(['work_order_id', 'role']);
            $t->timestamps();
        });
        Schema::create('auditoria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('action')->nullable();
            $t->string('subject_type')->nullable();
            $t->unsignedInteger('subject_id')->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->timestamps();
        });
        Schema::create('notificacoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $t->string('title')->nullable();
            $t->text('body')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
        Schema::dropIfExists('auditoria');
        Schema::dropIfExists('assinaturas');
        Schema::dropIfExists('anexos');
        Schema::dropIfExists('historico_equipamentos');
        Schema::dropIfExists('analises_sos');
        Schema::dropIfExists('historico_pneus');
        Schema::dropIfExists('pneus');
        Schema::dropIfExists('historico_componentes');
        Schema::dropIfExists('componentes');
        Schema::dropIfExists('execucoes_preventivas');
        Schema::dropIfExists('planos_preventivos');
        Schema::dropIfExists('pecas_os');
        Schema::dropIfExists('mao_obra_os');
        Schema::dropIfExists('movimentacoes_estoque');
        Schema::dropIfExists('pecas');
        Schema::dropIfExists('ordens_servico');
        Schema::dropIfExists('solicitacoes');
        Schema::dropIfExists('equipamentos');
        Schema::dropIfExists('localizacoes');
        Schema::dropIfExists('centros_custo');
        Schema::dropIfExists('contratos');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role', 'active']);
        });
    }
};
