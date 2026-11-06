<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mostres', function (Blueprint $table) {
            $table->string('estat', 20)->default('pendent')->index();
        });

        // Manté la recepció de les mostres creades abans de la migració.
        DB::table('mostres')->where('recepcio_confirmada', true)->update(['estat' => 'rebuda']);

        Schema::create('mostra_processaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mostra_id')->unique()->constrained('mostres')->cascadeOnDelete();
            $table->text('descripcio_macroscopica')->nullable();
            $table->boolean('descripcio_confirmada')->default(false);
            // Citologia
            $table->json('procediments')->nullable();
            $table->boolean('procediment_confirmat')->default(false);
            // Ambdós circuits
            $table->json('tincions')->nullable();
            $table->boolean('tincions_confirmades')->default(false);
            $table->boolean('muntatge_confirmat')->default(false);
            $table->boolean('diagnostic_confirmat')->default(false);
            $table->boolean('arxiu_confirmat')->default(false);
            $table->text('incidencies')->nullable();
            // Citologia
            $table->boolean('lliurament_confirmat')->default(false);
            // Biòpsia: estació de tallat
            $table->unsignedInteger('numero_blocs')->nullable();
            $table->text('talls_per_bloc')->nullable();
            $table->text('informacio_tincions')->nullable();
            $table->boolean('tallat_macroscopic_confirmat')->default(false);
            // Biòpsia: processament, inclusió i microtomia
            $table->boolean('processador_teixits_confirmat')->default(false);
            $table->boolean('inclusio_parafina_confirmada')->default(false);
            $table->text('laminetes_per_bloc')->nullable();
            $table->boolean('confeccio_blocs_confirmada')->default(false);
            $table->boolean('lliurament_talls_confirmat')->default(false);
            $table->boolean('tincio_confirmada')->default(false);
            $table->boolean('lliurament_patoleg_confirmat')->default(false);
            $table->timestamps();
        });

        Schema::create('mostra_esdeveniments', function (Blueprint $table) {
            $table->id();
            // No s'elimina amb la baixa lògica de la mostra.
            $table->foreignId('mostra_id')->constrained('mostres')->restrictOnDelete();
            $table->foreignId('usuari_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuari_nom', 255)->nullable(); // Nom en el moment de l'acció.
            $table->string('accio', 50);
            $table->json('detall')->nullable(); // Metadades: noms de camps, no valors clínics.
            $table->timestamp('created_at')->useCurrent();
            $table->index(['mostra_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mostra_esdeveniments');
        Schema::dropIfExists('mostra_processaments');
        Schema::table('mostres', fn (Blueprint $table) => $table->dropColumn('estat'));
    }
};
