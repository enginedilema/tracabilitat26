<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrEtiqueta extends Model
{
    protected $table = 'qr_etiquetes';

    protected $fillable = ['lot_id', 'token', 'estat', 'mostra_id', 'assignat_per', 'assignat_at', 'anullat_at'];

    protected function casts(): array
    {
        return ['assignat_at' => 'datetime', 'anullat_at' => 'datetime'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(QrLot::class, 'lot_id');
    }

    public function mostra(): BelongsTo
    {
        return $this->belongsTo(Mostra::class);
    }
}
