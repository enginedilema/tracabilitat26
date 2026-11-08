<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrSubstitucio extends Model
{
    protected $table = 'qr_substitucions';

    protected $fillable = [
        'mostra_id', 'etiqueta_antiga_id', 'etiqueta_nova_id',
        'token_legacy_hash', 'substituit_per', 'motiu',
    ];

    public function mostra(): BelongsTo
    {
        return $this->belongsTo(Mostra::class);
    }

    public function antiga(): BelongsTo
    {
        return $this->belongsTo(QrEtiqueta::class, 'etiqueta_antiga_id');
    }

    public function nova(): BelongsTo
    {
        return $this->belongsTo(QrEtiqueta::class, 'etiqueta_nova_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substituit_per');
    }
}
