<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qr_substitucions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mostra_id')->constrained('mostres')->restrictOnDelete();
            $table->foreignId('etiqueta_antiga_id')->nullable()->constrained('qr_etiquetes')->restrictOnDelete();
            $table->foreignId('etiqueta_nova_id')->unique()->constrained('qr_etiquetes')->restrictOnDelete();
            $table->char('token_legacy_hash', 64)->nullable()->unique();
            $table->foreignId('substituit_per')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motiu', 255);
            $table->timestamps();
            $table->index(['mostra_id', 'created_at']);
            $table->index('etiqueta_antiga_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_substitucions');
    }
};
