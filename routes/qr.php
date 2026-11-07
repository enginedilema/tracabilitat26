<?php

use App\Http\Controllers\Mostres\MostraQrController;
use App\Models\Mostra;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// Aquest fitxer s'ha d'incloure UNA VEGADA des de routes/web.php.
Route::middleware('auth')->group(function (): void {
    Route::get('/lector-qr', function () {
        Gate::authorize('viewAny', Mostra::class);

        return response()->view('mostres.lector-qr')
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    })->name('mostres.qr.lector');

    Route::get('/mostres/{mostra}/etiqueta-qr', [MostraQrController::class, 'etiqueta'])
        ->name('mostres.qr.etiqueta');

    Route::get('/mostres/{mostra}/imatge-qr.svg', [MostraQrController::class, 'imatge'])
        ->name('mostres.qr.imatge');

    Route::get('/qr/m/{token}', [MostraQrController::class, 'resoldre'])
        ->whereUuid('token')
        ->name('mostres.qr.resolve');
});
