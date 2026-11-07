<?php

use Illuminate\Support\Facades\Route;

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


require __DIR__.'/settings.php';
require __DIR__.'/qr.php';