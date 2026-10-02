<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldos_estoque', function (Blueprint $t) {
            $t->id();
            $t->foreignId('part_id')->constrained('pecas')->restrictOnDelete();
            $t->foreignId('location_id')->constrained('localizacoes')->restrictOnDelete();
            $t->decimal('quantity', 14, 3)->default(0);
            $t->unique(['part_id', 'location_id']);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldos_estoque');
    }
};
