<?php

use App\Http\Controllers\EtiquetaA4Controller;
use App\Models\Mostra;
use App\Models\QrLot;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// v0.10.1: el punt d'impressió té una ruta específica dins de cada lot.
// Conservem els noms de ruta perquè les vistes i les proves anteriors funcionin.
Route::middleware('auth')->group(function (): void {
    Route::get('/etiquetes', function () {
        Gate::authorize('viewAny', User::class);

        return view('etiquetes.index');
    })->name('etiquetes.index');

    Route::get('/etiquetes/lots/{lot}', function (string $lot) {
        Gate::authorize('viewAny', User::class);
        abort_unless(ctype_digit($lot) && (int) $lot > 0, 404);
        QrLot::query()->findOrFail((int) $lot);

        return view('etiquetes.lot', ['lotId' => (int) $lot]);
    })->whereNumber('lot')->name('etiquetes.lot');

    // Ruta principal: es fa servir amb route('etiquetes.a4', ['lot' => $lot->id]).
    Route::get('/etiquetes/lots/{lot}/a4', [EtiquetaA4Controller::class, 'index'])
        ->whereNumber('lot')
        ->name('etiquetes.a4');

    // Compatibilitat amb els enllaços d'impressió de v0.8 i v0.9.
    // El paràmetre té un nom diferent per evitar vinculacions antigues de {lot}.
    Route::get('/etiquetes/a4/{id}', function (string $id) {
        Gate::authorize('viewAny', User::class);
        abort_unless(ctype_digit($id) && (int) $id > 0, 404);

        return redirect()->route('etiquetes.a4', ['lot' => $id]);
    })->whereNumber('id')->name('etiquetes.a4.antic');

    // v0.10: substitució controlada del QR d'una mostra.
    Route::get('/mostres/{mostra}/qr/substituir', function (string $mostra) {
        Gate::authorize('viewAny', User::class);
        abort_unless(ctype_digit($mostra) && (int) $mostra > 0, 404);
        $mostra = Mostra::query()->findOrFail((int) $mostra);

        return response()->view('mostres.substituir-qr', ['mostraId' => $mostra->id])
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer');
    })->name('mostres.qr.substituir');
});
