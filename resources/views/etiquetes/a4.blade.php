
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">

    <title>Etiquetes QR DINA4 · Traçabilitat</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            background: #f4f4f5;
        }

        .toolbar {
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toolbar button,
        .toolbar a {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #111;
            color: #fff;
            text-decoration: none;
            font: 14px Arial, sans-serif;
            cursor: pointer;
        }

        .page {
            width: 190mm;
            height: 286mm;
            margin: 0 auto 8mm;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(2, 90mm);
            grid-template-rows: repeat(5, 55mm);
            column-gap: 10mm;
            row-gap: 2mm;
            align-content: start;
            break-after: page;
        }

        .page:last-of-type {
            break-after: auto;
        }

        .label {
            width: 90mm;
            height: 55mm;
            border: 1px solid #bbb;
            border-radius: 2mm;
            padding: 4mm;
            display: flex;
            gap: 3mm;
            align-items: center;
            overflow: hidden;
            break-inside: avoid;
        }

        .left {
            flex: 1;
            min-width: 0;
            overflow-wrap: anywhere;
            display: flex;
            flex-direction: column;
            gap: 2mm;
        }

        .title {
            font-weight: bold;
            font-size: 11pt;
        }

        .meta {
            font-size: 9pt;
            line-height: 1.2;
        }

        .small {
            font-size: 7pt;
            color: #444;
        }

        .qr {
            width: 29mm;
            height: 29mm;
            flex: 0 0 29mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr img {
            width: 29mm;
            height: 29mm;
            display: block;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .page {
                margin: 0 auto;
            }

            .label {
                border: 1px solid #aaa;
            }
        }
    </style>
</head>

<body>

    <!-- Barra d'eines -->
    <div class="toolbar">
        <button type="button" onclick="window.print()">
            Imprimir full DINA4
        </button>

        <a href="{{ route('etiquetes.index') }}">
            Tornar al generador
        </a>
    </div>

    <!-- Generació dels fulls A4 -->
    @foreach ($etiquetes->chunk(10) as $bloc)

        <section class="page" aria-label="Full d'etiquetes">

            @foreach ($bloc as $mostra)

                <article
                    class="label"
                    aria-label="Etiqueta de la mostra {{ $mostra->identificacio }}"
                >

                    <div class="left">

                        <div class="title">
                            TRAÇABILITAT
                        </div>

                        <div class="meta">
                            <strong>Mostra:</strong>
                            {{ $mostra->identificacio }}
                        </div>

                        <div class="meta">
                            <strong>Tipus:</strong>
                            {{ $mostra->tipus_mostra === 'biopsia' ? 'Biòpsia' : 'Citologia' }}
                        </div>

                        <div class="meta">
                            <strong>Entrada:</strong>
                            {{ optional($mostra->data_entrada)->format('d/m/Y') }}
                        </div>

                        <div class="small">
                            Identificador de mostra · Ús intern
                        </div>

                    </div>

                    <!-- Codi QR -->
                    <div class="qr">
                        <img
                            src="{{ route('mostres.qr.imatge', $mostra) }}"
                            alt="Codi QR de la mostra {{ $mostra->identificacio }}"
                        >
                    </div>

                </article>

            @endforeach

        </section>

    @endforeach

</body>
</html>
