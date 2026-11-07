<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Etiqueta QR - {{ $mostra->identificacio }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 20px; color: #111; background: #f4f4f5; font-family: Arial, sans-serif; }
        .actions { max-width: 430px; margin: 0 auto 14px; display: flex; gap: 10px; }
        .actions button, .actions a { font: 14px Arial,sans-serif; padding: 9px 15px; border: 1px solid #aaa; border-radius: 7px; background: white; color: #111; text-decoration: none; cursor: pointer; }
        .etiqueta { background: white; border: 1px solid #bbb; border-radius: 6px; width: 90mm; min-height: 55mm; margin: 0 auto; padding: 4mm; display: flex; align-items: center; gap: 4mm; }
        .qr { width: 40mm; height: 40mm; flex-shrink: 0; }
        .qr img { width: 100%; height: 100%; display: block; }
        .detall { min-width: 0; overflow-wrap: anywhere; }
        .logo { font-size: 12px; font-weight: bold; letter-spacing: .02em; }
        .identificacio { font-size: 18px; font-weight: bold; margin: 9px 0; }
        .tipus { font-size: 12px; }
        .peu { font-size: 9px; margin-top: 12px; color: #444; }
        @media print {
            @page { size: 90mm 55mm; margin: 0; }
            body { margin: 0; padding: 0; background: white; }
            .actions { display: none; }
            .etiqueta { margin: 0; border: 0; border-radius: 0; width: 90mm; min-height: 55mm; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir etiqueta</button>
        <a href="{{ route('mostres.index') }}">Tornar a mostres</a>
    </div>
    <section class="etiqueta" aria-label="Etiqueta de la mostra {{ $mostra->identificacio }}">
        <div class="qr"><img src="{{ route('mostres.qr.imatge', $mostra) }}" alt="Codi QR de la mostra"></div>
        <div class="detall">
            <div class="logo">TRAÇABILITAT</div>
            <div class="identificacio">{{ $mostra->identificacio }}</div>
            <div class="tipus">{{ $mostra->tipus_mostra === 'biopsia' ? 'Biòpsia' : 'Citologia' }}</div>
            <div class="peu">Identificador de mostra · ús intern</div>
        </div>
    </section>
</body>
</html>
