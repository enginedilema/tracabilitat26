
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\EtiquetaA4Controller;
use App\Models\Mostra;
use App\Models\User;

// Pàgina inicial
Route::view('/', 'welcome')->name('home');

// Rutes protegides amb autenticació
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    // Mostres
    Route::view('/mostres', 'mostres.index')
        ->name('mostres.index');

    // Procés d'una mostra
    Route::get('/mostres/{mostra}/proces', function (Mostra $mostra) {
        return view('mostres.proces', compact('mostra'));
    })->name('mostres.proces');

    // Administració d'usuaris
    Route::get('/administracio/usuaris', function () {
        Gate::authorize('viewAny', User::class);

        return view('administracio.usuaris');
    })->name('usuaris.index');

    // Impressió A4 de mostres registrades
    Route::get('/etiquetes/a4', [EtiquetaA4Controller::class, 'index'])
        ->name('etiquetes.mostres.a4');

});

// Configuració
require __DIR__.'/settings.php';

// Codis QR
require __DIR__.'/qr.php';

// Generació i gestió de lots QR
require __DIR__.'/qr_preimpressio.php';
