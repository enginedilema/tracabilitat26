<?php

namespace App\Http\Controllers\Mostres;

use App\Http\Controllers\Controller;
use App\Models\Mostra;
use App\Models\QrEtiqueta;
use App\Services\MostraQrService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MostraQrController extends Controller
{
    public function etiqueta(Mostra $mostra, MostraQrService $qr): Response
    {
        Gate::authorize('view', $mostra);

        return response()->view('mostres.etiqueta-qr', [
            'mostra' => $mostra,
            'urlQr' => $qr->url($mostra),
        ])->withHeaders($this->capcaleresPrivades());
    }

    public function imatge(Mostra $mostra, MostraQrService $qr): Response
    {
        Gate::authorize('view', $mostra);

        $qrCode = new QrCode(
            data: $qr->url($mostra),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 12,
        );

        $resultat = (new SvgWriter())->write($qrCode);

        return response($resultat->getString(), 200, array_merge(
            ['Content-Type' => 'image/svg+xml; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff'],
            $this->capcaleresPrivades()
        ));
    }

    public function resoldre(string $token): RedirectResponse
    {
        $etiqueta = QrEtiqueta::query()->where('token', strtolower($token))->first();

        if ($etiqueta) {
            if ($etiqueta->estat === 'anullat') {
                abort(410, 'Aquest QR ha estat anul·lat.');
            }

            if ($etiqueta->estat === 'disponible' && $etiqueta->mostra_id === null) {
                Gate::authorize('create', Mostra::class);

                // Obrim el formulari de recepció amb el token, sense assignar-lo encara.
                return redirect()->route('mostres.index', ['qr' => $etiqueta->token]);
            }

            abort_unless($etiqueta->estat === 'assignat' && $etiqueta->mostra_id !== null, 404);
            $mostra = Mostra::query()->findOrFail($etiqueta->mostra_id);
            Gate::authorize('view', $mostra);

            return redirect()->route('mostres.proces', $mostra);
        }

        // Compatibilitat amb QR de la v0.6 assignats abans de la preimpressió.
        $mostra = Mostra::query()->where('qr_token', $token)->firstOrFail();
        Gate::authorize('view', $mostra);
        return redirect()->route('mostres.proces', $mostra);
    }

    private function capcaleresPrivades(): array
    {
        return [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
    }
}
