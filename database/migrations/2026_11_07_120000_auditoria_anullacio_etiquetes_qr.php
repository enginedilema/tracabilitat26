<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('qr_etiquetes', function (Blueprint $table): void {
            $table->foreignId('anullat_per')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motiu_anullacio', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('qr_etiquetes', function (Blueprint $table): void {
            $table->dropForeign(['anullat_per']);
            $table->dropColumn(['anullat_per', 'motiu_anullacio']);
        });
    }
};
