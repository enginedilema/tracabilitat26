<?php

namespace App\Http\Controllers;

use App\Models\QrLot;
use App\Models\User;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class EtiquetaA4Controller extends Controller
{
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
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
