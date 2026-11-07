<?php

namespace App\Services;

use App\Models\Mostra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MostraQrService
{
    /**
     * Els tokens antics es mantenen estables. Un token no conte dades cliniques.
     * Els models nous poden obtenir el token al primer acces a l'etiqueta.
     */
    public function token(Mostra $mostra): string
    {
        if ($mostra->qr_token) {
            return (string) $mostra->qr_token;
        }

        return DB::transaction(function () use ($mostra): string {
            $actual = Mostra::query()->lockForUpdate()->findOrFail($mostra->getKey());

            if (! $actual->qr_token) {
                $actual->forceFill(['qr_token' => (string) Str::uuid()])->save();
            }

            $mostra->setAttribute('qr_token', $actual->qr_token);

            return (string) $actual->qr_token;
        });
    }

    /**
     * L'APP_URL de produccio ha de ser l'adreca HTTPS definitiva.
     */
    public function url(Mostra $mostra): string
    {
        $cami = route('mostres.qr.resolve', ['token' => $this->token($mostra)], false);

        return rtrim((string) config('app.url'), '/').$cami;
    }
}
