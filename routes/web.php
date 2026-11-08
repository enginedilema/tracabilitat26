<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\EtiquetaA4Controller;
use App\Livewire\Etiquetes\GeneradorEtiquetes;

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

Route::get('/mostres/{mostra}/proces', function (\App\Models\Mostra $mostra) {
    return view('mostres.proces', compact('mostra'));
})
    ->middleware('auth')
    ->name('mostres.proces');

    
    Route::middleware('auth')->group(function () {
        Route::get('/etiquetes', GeneradorEtiquetes::class)
            ->name('etiquetes.index');
<<<<<<< HEAD

=======
    
        Route::get('/etiquetes/a4', [EtiquetaA4Controller::class, 'index'])
            ->name('etiquetes.a4');
>>>>>>> fa41286a28c9f088fb5b9a68acbd117ed20126da
    });

require __DIR__.'/settings.php';

// Rutes antigues de codis QR
require __DIR__.'/qr.php';
require __DIR__.'/qr_preimpressio.php';
