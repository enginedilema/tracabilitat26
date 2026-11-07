<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mostres', function (Blueprint $table): void {
            $table->uuid('qr_token')->nullable()->unique();
        });

        // Assigna un identificador opac a les mostres existents sense alterar-les.
        DB::table('mostres')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->chunkById(200, function ($mostres): void {
                foreach ($mostres as $mostra) {
                    DB::table('mostres')
                        ->where('id', $mostra->id)
                        ->update(['qr_token' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('mostres', function (Blueprint $table): void {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
