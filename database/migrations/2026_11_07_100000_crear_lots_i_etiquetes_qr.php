<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qr_lots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creat_per')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('quantitat');
            $table->timestamps();
        });

        Schema::create('qr_etiquetes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lot_id')->constrained('qr_lots')->restrictOnDelete();
            $table->uuid('token')->unique();
            $table->string('estat', 16)->default('disponible');
            $table->foreignId('mostra_id')->nullable()->unique()->constrained('mostres')->restrictOnDelete();
            $table->foreignId('assignat_per')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assignat_at')->nullable();
            $table->timestamp('anullat_at')->nullable();
            $table->timestamps();
            $table->index(['lot_id', 'estat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_etiquetes');
        Schema::dropIfExists('qr_lots');
    }
};
