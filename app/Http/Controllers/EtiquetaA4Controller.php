<?php

namespace App\Http\Controllers;

use App\Models\Mostra;
use App\Models\QrLot;
use App\Models\User;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class EtiquetaA4Controller extends Controller
{
    /**
     * Imprimeix etiquetes de mostres ja registrades.
     * Ruta: /etiquetes/a4
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Mostra::class);

        $items = collect($request->session()->get('etiquetes_a4.items', []))
            ->filter(fn ($item) => is_array($item) && isset($item['mostra_id']))
            ->take(100);

        abort_if($items->isEmpty(), 404, 'Selecciona mostres abans de generar les etiquetes.');

        $mostres = Mostra::query()
            ->whereIn('id', $items->pluck('mostra_id')->map(fn ($id) => (int) $id))
            ->get()
            ->keyBy('id');

        $etiquetes = collect();

        foreach ($items as $item) {
            $mostra = $mostres->get((int) $item['mostra_id']);

            if (! $mostra) {
                continue;
            }

            Gate::authorize('view', $mostra);

            $copies = min(10, max(1, (int) ($item['copies'] ?? 1)));

            for ($i = 0; $i < $copies && $etiquetes->count() < 100; $i++) {
                $etiquetes->push($mostra);
            }

            if ($etiquetes->count() >= 100) {
                break;
            }
        }

        abort_if($etiquetes->isEmpty(), 404, 'No s’han trobat mostres per imprimir.');

        return response()->view('etiquetes.a4', compact('etiquetes'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Imprimeix els QR disponibles d'un lot previ a l'assignació de mostres.
     * Ruta: /etiquetes/lots/{lot}/a4
     */
    public function lot(string $lot): Response
    {
        Gate::authorize('viewAny', User::class);

        abort_unless(ctype_digit($lot) && (int) $lot > 0, 404);
        $lot = QrLot::query()->findOrFail((int) $lot);

        $writer = new SvgWriter();

        $etiquetes = $lot->etiquetes()
            ->where('estat', 'disponible')
            ->whereNull('mostra_id')
            ->orderBy('id')
            ->get()
            ->map(function ($etiqueta) use ($writer): array {
                $qr = new QrCode(
                    data: $etiqueta->token,
                    errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                    size: 300,
                    margin: 10,
                );

                return [
                    'token' => $etiqueta->token,
                    'curt' => strtoupper(substr(str_replace('-', '', $etiqueta->token), -8)),
                    'svg' => preg_replace('/^<\?xml[^>]*\?>\s*/i', '', ltrim($writer->write($qr)->getString())),
                ];
            });

        return response()->view('etiquetes.a4-lot', compact('etiquetes', 'lot'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
