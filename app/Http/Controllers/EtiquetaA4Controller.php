<?php

namespace App\Http\Controllers;

use App\Models\Mostra;
use App\Models\QrEtiqueta;
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

        $rawItems = $request->session()->get('etiquetes_a4.items', []);
        abort_unless(is_array($rawItems), 404, 'Selecciona mostres abans de generar les etiquetes.');

        /** @var list<array{mostra_id: int, copies: int}> $items */
        $items = [];
        foreach ($rawItems as $item) {
            if (! is_array($item) || ! isset($item['mostra_id']) || ! is_numeric($item['mostra_id'])) {
                continue;
            }

            $id = (int) $item['mostra_id'];
            if ($id < 1) {
                continue;
            }

            $items[] = [
                'mostra_id' => $id,
                'copies' => min(10, max(1, (int) ($item['copies'] ?? 1))),
            ];

            if (count($items) >= 100) {
                break;
            }
        }

        abort_if($items === [], 404, 'Selecciona mostres abans de generar les etiquetes.');

        $mostres = Mostra::query()
            ->whereIn('id', array_column($items, 'mostra_id'))
            ->get()
            ->keyBy('id');

        /** @var list<Mostra> $etiquetes */
        $etiquetes = [];

        foreach ($items as $item) {
            $mostra = $mostres->get((int) $item['mostra_id']);

            if (! $mostra) {
                continue;
            }

            Gate::authorize('view', $mostra);

            $copies = $item['copies'];

            for ($i = 0; $i < $copies && count($etiquetes) < 100; $i++) {
                $etiquetes[] = $mostra;
            }

            if (count($etiquetes) >= 100) {
                break;
            }
        }

        abort_if($etiquetes === [], 404, 'No s’han trobat mostres per imprimir.');

        $etiquetes = collect($etiquetes);

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

        $writer = new SvgWriter;

        $etiquetes = $lot->etiquetes()
            ->where('estat', 'disponible')
            ->whereNull('mostra_id')
            ->orderBy('id')
            ->get()
            ->map(function (QrEtiqueta $etiqueta) use ($writer): array {
                $qr = new QrCode(
                    data: $etiqueta->token,
                    errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                    size: 300,
                    margin: 10,
                );

                return [
                    'token' => $etiqueta->token,
                    'curt' => strtoupper(substr(str_replace('-', '', $etiqueta->token), -8)),
                    'svg' => preg_replace('/^\s*<\?xml[^>]*\?>\s*/i', '', $writer->write($qr)->getString()),
                ];
            });

        return response()->view('etiquetes.a4-lot', compact('etiquetes', 'lot'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
