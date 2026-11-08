<?php

namespace App\Services;

use App\Models\Mostra;
use App\Models\QrEtiqueta;
use App\Models\QrLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrPreimpressioService
{
    public function crearLot(int $quantitat): QrLot
    {
        if ($quantitat < 1 || $quantitat > 100) {
            throw ValidationException::withMessages(['quantitat' => 'Tria entre 1 i 100 etiquetes.']);
        }

        return DB::transaction(function () use ($quantitat): QrLot {
            $lot = QrLot::create(['creat_per' => auth()->id(), 'quantitat' => $quantitat]);

            for ($i = 0; $i < $quantitat; $i++) {
                QrEtiqueta::create([
                    'lot_id' => $lot->id,
                    'token' => (string) Str::uuid(),
                    'estat' => 'disponible',
                ]);
            }

            return $lot;
        });
    }

    /** Accepta UUID o URL que acabi amb /qr/m/UUID. No confia en el domini rebut. */
    public function extreureToken(string $entrada): ?string
    {
        $valor = trim($entrada);
        if (Str::isUuid($valor)) {
            return strtolower($valor);
        }
        $cami = parse_url($valor, PHP_URL_PATH);
        if (! is_string($cami)) {
            return null;
        }
        if (preg_match('~(?:^|/)qr/m/([a-f0-9-]{36})/?$~i', $cami, $coincidencia) !== 1) {
            return null;
        }

        return Str::isUuid($coincidencia[1]) ? strtolower($coincidencia[1]) : null;
    }

    /** Cridar dins de la mateixa transacció que crea la mostra. */
    public function assignar(string $entrada, Mostra $mostra): QrEtiqueta
    {
        $token = $this->extreureToken($entrada);
        if ($token === null) {
            throw ValidationException::withMessages(['qrCodi' => 'Escaneja un QR vàlid de Traçabilitat.']);
        }

        $etiqueta = QrEtiqueta::query()->where('token', $token)->first();
        if (! $etiqueta || $etiqueta->estat !== 'disponible' || $etiqueta->mostra_id !== null) {
            throw ValidationException::withMessages(['qrCodi' => 'Aquest QR no existeix, està anul·lat o ja està assignat.']);
        }

        // Condició atòmica: evita que dues altes assignin la mateixa etiqueta.
        $actualitzades = QrEtiqueta::query()
            ->whereKey($etiqueta->id)
            ->where('estat', 'disponible')
            ->whereNull('mostra_id')
            ->update([
                'estat' => 'assignat',
                'mostra_id' => $mostra->getKey(),
                'assignat_per' => auth()->id(),
                'assignat_at' => now(),
                'updated_at' => now(),
            ]);

        if ($actualitzades !== 1) {
            throw ValidationException::withMessages(['qrCodi' => 'Aquest QR ja s’ha assignat. Escaneja’n un altre.']);
        }

        return $etiqueta->fresh();
    }

    /** Anul·la una etiqueta disponible. No permet anul·lar codis ja assignats. */
    public function anullarEtiqueta(int $lotId, int $etiquetaId, string $motiu): QrEtiqueta
    {
        Gate::authorize('viewAny', User::class);
        $motiu = $this->validarMotiu($motiu);
        QrLot::query()->findOrFail($lotId);

        return DB::transaction(function () use ($lotId, $etiquetaId, $motiu): QrEtiqueta {
            $etiqueta = QrEtiqueta::query()
                ->where('lot_id', $lotId)->findOrFail($etiquetaId);

            if ($etiqueta->estat !== 'disponible' || $etiqueta->mostra_id !== null) {
                throw ValidationException::withMessages([
                    'motiuAnullacio' => 'Només es poden anul·lar codis QR disponibles i sense mostra.',
                ]);
            }

            // Actualització atòmica: no anul·la un QR que s'assigni simultàniament.
            $canvis = QrEtiqueta::query()
                ->whereKey($etiquetaId)
                ->where('lot_id', $lotId)
                ->where('estat', 'disponible')
                ->whereNull('mostra_id')
                ->update([
                    'estat' => 'anullat',
                    'anullat_per' => auth()->id(),
                    'anullat_at' => now(),
                    'motiu_anullacio' => $motiu,
                    'updated_at' => now(),
                ]);

            if ($canvis !== 1) {
                throw ValidationException::withMessages([
                    'motiuAnullacio' => 'El QR ja no està disponible. Actualitza el lot.',
                ]);
            }

            return $etiqueta->fresh();
        });
    }

    /** Anul·la només els QR disponibles d'un lot; conserva els que estan assignats. */
    public function anullarDisponiblesLot(int $lotId, string $motiu): int
    {
        Gate::authorize('viewAny', User::class);
        $motiu = $this->validarMotiu($motiu);
        QrLot::query()->findOrFail($lotId);

        return DB::transaction(fn (): int => QrEtiqueta::query()
            ->where('lot_id', $lotId)
            ->where('estat', 'disponible')
            ->whereNull('mostra_id')
            ->update([
                'estat' => 'anullat',
                'anullat_per' => auth()->id(),
                'anullat_at' => now(),
                'motiu_anullacio' => $motiu,
                'updated_at' => now(),
            ]));
    }

    private function validarMotiu(string $motiu): string
    {
        $motiu = trim($motiu);
        if (Str::length($motiu) < 8 || Str::length($motiu) > 255) {
            throw ValidationException::withMessages([
                'motiuAnullacio' => 'Escriu un motiu entre 8 i 255 caràcters.',
            ]);
        }

        return $motiu;
    }
}
