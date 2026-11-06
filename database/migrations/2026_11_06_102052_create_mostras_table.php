
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mostres', function (Blueprint $table) {
            $table->id();

            // Identificació
            $table->string('identificacio')->unique();
            $table->string('tipus_mostra', 20);

            // Dades de recepció
            $table->string('genere', 20)->nullable();
            $table->unsignedTinyInteger('edat')->nullable();
            $table->text('motiu_sollicitud')->nullable();
            $table->string('procedencia')->nullable();
            $table->string('sollicitant')->nullable();

            $table->date('data_entrada');
            $table->time('hora_entrada');

            // Classificació de la mostra
            $table->string('tipus_principal')->nullable();
            $table->string('tipus_secundari')->nullable();
            $table->string('organ_localitzacio')->nullable();

            // Control de recepció
            $table->boolean('recepcio_confirmada')
                  ->default(false);

            // Control de registre
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mostres');
    }
};
