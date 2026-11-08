<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\QrLot;
use App\Models\User;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
=======
use App\Models\Mostra;
use Illuminate\Http\Request;
>>>>>>> fa41286a28c9f088fb5b9a68acbd117ed20126da
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class EtiquetaA4Controller extends Controller
{
<<<<<<< HEAD
    public function index(string $lot): Response
    {
        Gate::authorize('viewAny', User::class);

        // Recuperem el lot per ID. No depenem de la vinculació implícita
        // del model, que en aquesta instal·lació retornava un model buit.
        abort_unless(ctype_digit($lot) && (int) $lot > 0, 404);
        $lot = QrLot::query()->findOrFail((int) $lot);

        $writer = new SvgWriter();
        $etiquetes = $lot->etiquetes()
            ->where('estat', 'disponible')
            ->whereNull('mostra_id')
            ->orderBy('id')->get()->map(
            function ($etiqueta) use ($writer): array {
                // Només el token UUID, sense dades clíniques ni URL local.
                $qr = new QrCode(
                    data: $etiqueta->token,
                    errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                    size: 300,
                    margin: 10,
                );

                $svg = $writer->write($qr)->getString();

                return [
                    'token' => $etiqueta->token,
                    'curt' => strtoupper(substr(str_replace('-', '', $etiqueta->token), -8)),
                    'estat' => $etiqueta->estat,
                    'svg' => preg_replace('/<\?xml[^>]*\?>\s*/i', '', $svg),
                ];
            }
        );

        return response()->view('etiquetes.a4', compact('etiquetes', 'lot'))
=======
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

        // No guardem etiquetes amb identificadors en caches compartides.
        return response()->view('etiquetes.a4', compact('etiquetes'))
>>>>>>> fa41286a28c9f088fb5b9a68acbd117ed20126da
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
