// SUBSTITUIR només les rutes d'impressió ja existents dins del grup auth.
// No definir dues vegades cap nom de ruta.
// Mantén les redireccions antigues que ja funcionen.

// Impressió de mostres registrades, des de la sessió.
Route::get('/etiquetes/a4', [EtiquetaA4Controller::class, 'index'])
    ->name('etiquetes.mostres.a4');

// Impressió de lots QR encara sense mostra assignada.
// Els tests comproven literalment aquest URI i aquest nom.
Route::get('/etiquetes/lots/{lot}/a4', [EtiquetaA4Controller::class, 'lot'])
    ->whereNumber('lot')
    ->name('etiquetes.a4');

// Opcional, només si vols mantenir l'URL creada per la correcció anterior
// i NO tens ja aquesta ruta en cap altre fitxer:
// Route::get('/etiquetes/a4/lot/{lot}', fn (string $lot) =>
//     redirect()->route('etiquetes.a4', ['lot' => $lot]))
//     ->whereNumber('lot')->name('etiquetes.a4.lot');
