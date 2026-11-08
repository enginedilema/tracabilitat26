<?php

namespace App\Services;

use App\Models\Mostra;
use App\Models\MostraEsdeveniment;
use App\Models\QrEtiqueta;
use App\Models\QrSubstitucio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Substitueix l'etiqueta activa sense perdre l'historial del QR antic. */
class QrSubstitucioService
{
    public function substituir(Mostra $mostra, string $codiNou, string $motiu): QrSubstitucio
    {
        // Operació d'identificació crítica: només administradors actius.
        Gate::authorize('viewAny', User::class);
        $motiu = trim($motiu);
        if (Str::length($motiu) < 8 || Str::length($motiu) > 255) {
            throw ValidationException::withMessages([
                'motiu' => 'El motiu ha de tenir entre 8 i 255 caràcters.',
            ]);
        }

        $tokenNou = app(QrPreimpressioService::class)->extreureToken($codiNou);
        if ($tokenNou === null) {
            throw ValidationException::withMessages([
                'qrNou' => 'Escaneja un QR preimprès vàlid de Traçabilitat.',
            ]);
        }

        return DB::transaction(function () use ($mostra, $tokenNou, $motiu): QrSubstitucio {
            $actual = Mostra::query()->lockForUpdate()->findOrFail($mostra->getKey());

            if ($actual->estat === 'arxivada') {
                throw ValidationException::withMessages([
                    'qrNou' => 'No es pot substituir el QR d’una mostra arxivada.',
                ]);
            }

            $antiga = QrEtiqueta::query()
                ->where('mostra_id', $actual->getKey())
                ->where('estat', 'assignat')
                ->lockForUpdate()
                ->first();
            $legacy = $actual->qr_token ? (string) $actual->qr_token : null;

            if ($antiga === null && $legacy === null) {
                throw ValidationException::withMessages([
                    'qrNou' => 'Aquesta mostra encara no té cap QR assignat.',
                ]);
            }

            $nova = QrEtiqueta::query()
                ->where('token', $tokenNou)
                ->lockForUpdate()
                ->first();
            if (! $nova || $nova->estat !== 'disponible' || $nova->mostra_id !== null) {
                throw ValidationException::withMessages([
                    'qrNou' => 'El nou QR no està disponible o ja està assignat.',
                ]);
            }

            if ($antiga !== null) {
                // El camp mostra_id és únic: alliberem l'anterior sense esborrar-lo.
                // La relació històrica queda a qr_substitucions.
                $canvis = QrEtiqueta::query()
                    ->whereKey($antiga->getKey())
                    ->where('estat', 'assignat')
                    ->where('mostra_id', $actual->getKey())
                    ->update([
                        'estat' => 'anullat',
                        'mostra_id' => null,
                        'anullat_per' => auth()->id(),
                        'anullat_at' => now(),
                        'motiu_anullacio' => $motiu,
                        'updated_at' => now(),
                    ]);
                if ($canvis !== 1) {
                    throw ValidationException::withMessages([
                        'qrNou' => 'El QR original ha canviat. Torna a carregar la mostra.',
                    ]);
                }
            }

            // Si era un QR antic de la v0.6 també queda desactivat.
            // El lector antic cerca a mostres.qr_token i no el trobarà més.
            if ($legacy !== null) {
                $actual->forceFill(['qr_token' => null])->save();
            }

            $etiquetaNova = app(QrPreimpressioService::class)->assignar($tokenNou, $actual);

            $substitucio = QrSubstitucio::create([
                'mostra_id' => $actual->getKey(),
                'etiqueta_antiga_id' => $antiga?->getKey(),
                'etiqueta_nova_id' => $etiquetaNova->getKey(),
                'token_legacy_hash' => $legacy === null ? null : hash('sha256', strtolower($legacy)),
                'substituit_per' => auth()->id(),
                'motiu' => $motiu,
            ]);

            MostraEsdeveniment::registrar($actual, 'qr_substituit', [
                'substitucio_id' => $substitucio->id,
                'etiqueta_antiga_id' => $antiga?->getKey(),
                'etiqueta_nova_id' => $etiquetaNova->getKey(),
                'motiu' => $motiu,
            ]);

            return $substitucio;
        }, 3);
    }
}
