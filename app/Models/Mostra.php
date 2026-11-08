<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mostra extends Model
{
    use SoftDeletes;

    protected $table = 'mostres';

    protected $fillable = [
        'identificacio',
        'tipus_mostra',
        'genere',
        'edat',
        'motiu_sollicitud',
        'procedencia',
        'sollicitant',
        'data_entrada',
        'hora_entrada',
        'tipus_principal',
        'tipus_secundari',
        'organ_localitzacio',
        'recepcio_confirmada',
    ];

    protected function casts(): array
    {
        return [
            'edat' => 'integer',
            'data_entrada' => 'date',
            'recepcio_confirmada' => 'boolean',
        ];
    }
}
