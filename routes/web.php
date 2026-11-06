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

require __DIR__.'/settings.php';
