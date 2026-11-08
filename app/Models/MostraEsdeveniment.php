<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $accio
 * @property string|null $usuari_nom
 * @property Carbon|null $created_at
 * @property-read Mostra|null $mostra
 */
class MostraEsdeveniment extends Model
{
    public $timestamps = false;

    protected $table = 'mostra_esdeveniments';

    protected $fillable = ['mostra_id', 'usuari_id', 'usuari_nom', 'accio', 'detall'];

    protected function casts(): array
    {
        return ['detall' => 'array', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<Mostra, $this> */
    public function mostra(): BelongsTo
    {
        return $this->belongsTo(Mostra::class);
    }

    /** @return BelongsTo<User, $this> */
    public function usuari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_id');
    }

    /** @param array<string, mixed> $detall */
    public static function registrar(Mostra $mostra, string $accio, array $detall = []): self
    {
        return self::create([
            'mostra_id' => $mostra->getKey(),
            'usuari_id' => auth()->id(),
            'usuari_nom' => auth()->user()?->name,
            'accio' => $accio,
            'detall' => $detall,
        ]);
    }
}
