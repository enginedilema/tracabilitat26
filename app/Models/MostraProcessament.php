<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MostraProcessament extends Model
{
    protected $table = 'mostra_processaments';

    protected $fillable = [
        'mostra_id', 'descripcio_macroscopica', 'descripcio_confirmada',
        'procediments', 'procediment_confirmat', 'tincions', 'tincions_confirmades',
        'muntatge_confirmat', 'diagnostic_confirmat', 'arxiu_confirmat',
        'incidencies', 'lliurament_confirmat', 'numero_blocs', 'talls_per_bloc',
        'informacio_tincions', 'tallat_macroscopic_confirmat',
        'processador_teixits_confirmat', 'inclusio_parafina_confirmada',
        'laminetes_per_bloc', 'confeccio_blocs_confirmada',
        'lliurament_talls_confirmat', 'tincio_confirmada',
        'lliurament_patoleg_confirmat',
    ];

    protected function casts(): array
    {
        return [
            'procediments' => 'array', 'tincions' => 'array',
            'numero_blocs' => 'integer',
            'descripcio_confirmada' => 'boolean',
            'procediment_confirmat' => 'boolean',
            'tincions_confirmades' => 'boolean',
            'muntatge_confirmat' => 'boolean',
            'diagnostic_confirmat' => 'boolean',
            'arxiu_confirmat' => 'boolean',
            'lliurament_confirmat' => 'boolean',
            'tallat_macroscopic_confirmat' => 'boolean',
            'processador_teixits_confirmat' => 'boolean',
            'inclusio_parafina_confirmada' => 'boolean',
            'confeccio_blocs_confirmada' => 'boolean',
            'lliurament_talls_confirmat' => 'boolean',
            'tincio_confirmada' => 'boolean',
            'lliurament_patoleg_confirmat' => 'boolean',
        ];
    }

    public function mostra(): BelongsTo
    {
        return $this->belongsTo(Mostra::class);
    }
}
