<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiquetes QR · Lot #{{ $lot->id }}</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111; background: white; }
        .toolbar { padding: 12px; display: flex; align-items: center; gap: 12px; background: #f3f4f6; }
        .toolbar button { border: 0; border-radius: 7px; padding: 9px 16px; color: white; background: #111; cursor: pointer; }
        .sheet { width: 200mm; height: 287mm; display: grid; grid-template-columns: repeat(2, 90mm); grid-template-rows: repeat(5, 55mm); column-gap: 10mm; row-gap: 3mm; margin: 0 auto; break-after: page; page-break-after: always; }
        .sheet:last-of-type { break-after: auto; page-break-after: auto; }
        .label { width: 90mm; height: 55mm; border: 0.3mm solid #222; border-radius: 1mm; display: flex; align-items: center; gap: 3mm; padding: 3mm; overflow: hidden; break-inside: avoid; }
        .qr { width: 44mm; height: 44mm; flex: 0 0 44mm; display: flex; align-items: center; justify-content: center; }
        .qr svg { width: 44mm; height: 44mm; display: block; }
        .details { min-width: 0; overflow-wrap: anywhere; }
        .details strong { font-size: 12pt; }
        .details .meta { margin-top: 3mm; font-size: 8pt; }
        .details code { font-size: 9pt; font-weight: bold; }
        .details small { display: block; margin-top: 2mm; font-size: 6.5pt; }
        @media print { .toolbar { display: none; } .sheet { margin: 0; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Imprimir A4</button><span>Lot #{{ $lot->id }} · {{ $etiquetes->count() }} codis sense mostra</span></div>
    @foreach($etiquetes->chunk(10) as $pagina)
        <div class="sheet">
            @foreach($pagina as $etiqueta)
                <div class="label">
                    <div class="qr">{!! $etiqueta['svg'] !!}</div>
                    <div class="details">
                        <strong>Traçabilitat</strong>
                        <div class="meta">Lot #{{ $lot->id }}</div>
                        <div class="meta"><code>{{ $etiqueta['curt'] }}</code></div>
                        <small>QR únic · Assignar en recepció</small>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
