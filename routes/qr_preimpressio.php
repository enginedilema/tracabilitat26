<?php

use App\Http\Controllers\EtiquetaA4Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// Substitueix les antigues rutes /etiquetes i /etiquetes/a4.
Route::middleware('auth')->group(function (): void {
    Route::get('/etiquetes', function () {
        Gate::authorize('viewAny', User::class);
        return view('etiquetes.index');
    })->name('etiquetes.index');

    Route::get('/etiquetes/lots/{lot}', function (string $lot) {
        Gate::authorize('viewAny', User::class);
        abort_unless(ctype_digit($lot) && (int) $lot > 0, 404);
        \App\Models\QrLot::query()->findOrFail((int) $lot);

        return view('etiquetes.lot', ['lotId' => (int) $lot]);
    })->name('etiquetes.lot');

    Route::get('/etiquetes/a4/{lot}', [EtiquetaA4Controller::class, 'index'])
        ->name('etiquetes.a4');
});
