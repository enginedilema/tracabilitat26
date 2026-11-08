<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $token
 * @property string $estat
 * @property int|null $mostra_id
 * @property int $lot_id
 */
class QrEtiqueta extends Model
{
    protected $table = 'qr_etiquetes';

    protected $fillable = ['lot_id', 'token', 'estat', 'mostra_id', 'assignat_per', 'assignat_at', 'anullat_at', 'anullat_per', 'motiu_anullacio'];

    protected function casts(): array
    {
        return ['assignat_at' => 'datetime', 'anullat_at' => 'datetime'];
    }

    /** @return BelongsTo<QrLot, $this> */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(QrLot::class, 'lot_id');
    }

    /** @return BelongsTo<User, $this> */
    public function anullatPer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anullat_per');
    }

    /** Vincle auditable a la mostra original quan el QR és substituït. */
    /** @return HasOne<QrSubstitucio, $this> */
    public function substitucioOrigen(): HasOne
    {
        return $this->hasOne(QrSubstitucio::class, 'etiqueta_antiga_id');
    }

    /** @return BelongsTo<Mostra, $this> */
    public function mostra(): BelongsTo
    {
        return $this->belongsTo(Mostra::class);
    }
}
