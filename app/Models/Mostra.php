<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $identificacio
 * @property string $tipus_mostra
 * @property string $estat
 * @property string|null $qr_token
 * @property Carbon|null $data_entrada
 * @property string|null $hora_entrada
 * @property bool $recepcio_confirmada
 * @property int|null $edat
 * @property string|null $genere
 * @property string|null $motiu_sollicitud
 * @property string|null $procedencia
 * @property string|null $sollicitant
 * @property string|null $tipus_principal
 * @property string|null $tipus_secundari
 * @property string|null $organ_localitzacio
 */
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
