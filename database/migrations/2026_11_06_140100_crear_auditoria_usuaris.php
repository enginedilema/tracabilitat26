<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuari_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_email')->nullable();
            $table->unsignedBigInteger('usuari_id');
            $table->string('usuari_email');
            $table->string('accio', 50);
            $table->json('abans')->nullable();
            $table->json('despres')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['usuari_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuari_auditoria');
    }
};
