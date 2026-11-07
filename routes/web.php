<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EtiquetaA4Controller;
use App\Livewire\Etiquetes\GeneradorEtiquetes;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});
Route::middleware(['auth'])->group(function () {
    Route::view('/mostres', 'mostres.index')
        ->name('mostres.index');
});
Route::get('/administracio/usuaris', function () {
    \Illuminate\Support\Facades\Gate::authorize(
        'viewAny',
        \App\Models\User::class
    );

    return view('administracio.usuaris');
})->middleware('auth')->name('usuaris.index');
Route::get('/administracio/usuaris', function () {
    \Illuminate\Support\Facades\Gate::authorize(
        'viewAny',
        \App\Models\User::class
    );

    return view('administracio.usuaris');
})->middleware('auth')->name('usuaris.index');

Route::get('/mostres/{mostra}/proces', function (\App\Models\Mostra $mostra) {
    return view('mostres.proces', compact('mostra'));
})
    ->middleware('auth')
    ->name('mostres.proces');

    
    Route::middleware('auth')->group(function () {
        Route::get('/etiquetes', GeneradorEtiquetes::class)
            ->name('etiquetes.index');
    
        Route::get('/etiquetes/a4', [EtiquetaA4Controller::class, 'index'])
            ->name('etiquetes.a4');
    });

require __DIR__.'/settings.php';
require __DIR__.'/qr.php';