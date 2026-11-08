<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Etiquetes QR del lot #{{ $lot->id }} · Traçabilitat</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111; background: #f4f4f5; }
        .toolbar { padding: 12px; display: flex; align-items: center; gap: 10px; }
        .toolbar button, .toolbar a { padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; background: #111; color: #fff; text-decoration: none; font: 14px Arial, sans-serif; cursor: pointer; }
        .warning { margin: 8px 12px 16px; font-size: 13px; color: #555; }
        .page { width: 190mm; height: 286mm; margin: 0 auto 8mm; background: #fff; display: grid; grid-template-columns: repeat(2, 90mm); grid-template-rows: repeat(5, 55mm); column-gap: 10mm; row-gap: 2mm; align-content: start; break-after: page; }
        .page:last-of-type { break-after: auto; }
        .label { width: 90mm; height: 55mm; border: 1px solid #bbb; border-radius: 2mm; padding: 4mm; display: flex; gap: 3mm; align-items: center; overflow: hidden; break-inside: avoid; }
        .left { flex: 1; min-width: 0; overflow-wrap: anywhere; display: flex; flex-direction: column; gap: 2mm; }
        .title { font-weight: bold; font-size: 11pt; }
        .meta { font-size: 9pt; line-height: 1.2; }
        .small { font-size: 7pt; color: #444; }
        .qr { width: 29mm; height: 29mm; flex: 0 0 29mm; display: flex; align-items: center; justify-content: center; }
        .qr svg { width: 29mm; height: 29mm; display: block; }
        @media print {
            body { background: #fff; }
            .toolbar, .warning { display: none; }
            .page { margin: 0 auto; }
            .label { border: 1px solid #aaa; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir full DINA4</button>
        <a href="{{ route('etiquetes.index') }}">Tornar al generador</a>
    </div>

    <p class="warning">Lot #{{ $lot->id }} · {{ $etiquetes->count() }} codis disponibles per assignar.</p>
    <p class="warning">Cada QR és un identificador únic, encara sense mostra assignada. No reimprimeixis el mateix lot sense retirar els fulls anteriors.</p>

    @forelse ($etiquetes->chunk(10) as $bloc)
        <main class="page" aria-label="Full d'etiquetes QR del lot {{ $lot->id }}">
            @foreach ($bloc as $etiqueta)
                <article class="label" aria-label="Etiqueta {{ $etiqueta['curt'] }}">
                    <div class="left">
                        <div class="title">TRAÇABILITAT</div>
                        <div class="meta"><strong>Lot:</strong> #{{ $lot->id }}</div>
                        <div class="meta"><strong>Codi:</strong> {{ $etiqueta['curt'] }}</div>
                        <div class="small">QR pendent d'assignar a una mostra</div>
                    </div>
                    <div class="qr">
                        {{-- SVG creat al servidor amb Endroid a partir d'un UUID intern. --}}
                        {!! $etiqueta['svg'] !!}
                    </div>
                </article>
            @endforeach
        </main>
    @empty
        <p class="warning">No hi ha etiquetes disponibles per imprimir en aquest lot.</p>
    @endforelse
</body>
</html>
