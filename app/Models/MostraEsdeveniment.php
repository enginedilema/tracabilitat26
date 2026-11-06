<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MostraEsdeveniment extends Model
{
    public $timestamps = false;

    protected $table = 'mostra_esdeveniments';

    protected $fillable = ['mostra_id', 'usuari_id', 'usuari_nom', 'accio', 'detall'];

    protected function casts(): array
    {
        return ['detall' => 'array', 'created_at' => 'datetime'];
    }

    public function mostra(): BelongsTo
    {
        return $this->belongsTo(Mostra::class);
    }

    public function usuari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_id');
    }

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
