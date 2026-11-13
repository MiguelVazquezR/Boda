<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lista de invitados · {{ $coupleNames }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Figtree:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eef1f5;
            color: #1f2937;
            font-family: Figtree, "Segoe UI", system-ui, -apple-system, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Barra de acciones (solo en pantalla) ── */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .7rem 1rem;
            background: rgba(255, 255, 255, .94);
            border-bottom: 1px solid #d7dce3;
        }

        .toolbar__hint {
            margin: 0;
            font-size: .78rem;
            color: #6b7280;
        }

        .toolbar__actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem .95rem;
            border: 1px solid #b9c0cb;
            border-radius: .375rem;
            background: #fff;
            color: #1f2937;
            font: inherit;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .btn:hover { background: #f3f5f8; }

        .btn--primary {
            background: #17233d;
            border-color: #17233d;
            color: #fff;
        }

        .btn--primary:hover { background: #223354; }

        /* ── La "hoja" ── */
        .sheet {
            max-width: 21cm;
            margin: 1.5rem auto 2.5rem;
            padding: 1.5cm 1.3cm;
            background: #fff;
            border-radius: .5rem;
            box-shadow: 0 8px 28px rgba(23, 35, 61, .12);
        }

        .doc-header {
            padding-bottom: 1rem;
            margin-bottom: 1.4rem;
            text-align: center;
            border-bottom: 2px solid #17233d;
        }

        .doc-header__kicker {
            margin: 0;
            font-size: .68rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .doc-header__title {
            margin: .4rem 0 .3rem;
            font-family: "Playfair Display", Georgia, "Times New Roman", serif;
            font-size: 1.65rem;
            font-weight: 600;
            color: #17233d;
        }

        .doc-header__meta {
            margin: 0;
            font-size: .85rem;
            color: #4b5563;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .2rem 1.25rem;
            margin: .85rem 0 0;
            padding: 0;
            list-style: none;
            font-size: .78rem;
            color: #4b5563;
        }

        .summary strong { color: #17233d; }

        /* ── Mesas ── */
        .tables {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(13.5rem, 1fr));
            gap: .9rem;
        }

        .table-card {
            padding: .7rem .85rem .8rem;
            border: 1px solid #d7dce3;
            border-radius: .45rem;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .table-card__title {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .5rem;
            margin: 0 0 .45rem;
            padding-bottom: .35rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: .95rem;
            font-weight: 700;
            color: #17233d;
        }

        .table-card__count {
            font-size: .65rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .table-card__list {
            margin: 0;
            padding-left: 1.25rem;
            font-size: .85rem;
            line-height: 1.45;
        }

        .table-card__list li { padding: .06rem 0; }

        .table-card__list li::marker {
            font-size: .72rem;
            color: #9ca3af;
        }

        .table-card--unassigned {
            border-style: dashed;
            border-color: #d6c4a3;
        }

        .table-card--unassigned .table-card__title { color: #b45309; }

        .empty {
            margin: 0;
            padding: 2rem 0;
            text-align: center;
            color: #6b7280;
        }

        .doc-footer {
            margin-top: 1.4rem;
            padding-top: .6rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: .7rem;
            color: #9ca3af;
        }

        /* ── Impresión / Guardar como PDF ── */
        @page {
            size: A4 portrait;
            margin: 14mm;
        }

        @media print {
            body { background: #fff; }

            .toolbar { display: none !important; }

            .sheet {
                max-width: none;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .tables { gap: .5rem; }

            .table-card { border-color: #9ca3af; }

            .doc-footer { margin-top: 1rem; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <p class="toolbar__hint">En el diálogo de impresión elige «Guardar como PDF» para descargar la lista.</p>
        <div class="toolbar__actions">
            <a class="btn" href="{{ route('admin.guests.index') }}">Volver al panel</a>
            <button type="button" class="btn btn--primary" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 9V3h12v6" />
                    <path d="M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2" />
                    <path d="M6 14h12v7H6z" />
                </svg>
                Imprimir / Guardar PDF
            </button>
        </div>
    </div>

    <div class="sheet">
        <header class="doc-header">
            <p class="doc-header__kicker">Lista de invitados</p>
            <h1 class="doc-header__title">{{ $coupleNames }}</h1>
            <p class="doc-header__meta">
                @if ($eventDate)
                    {{ $eventDate->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                @endif
                @if ($venueName)
                    · {{ $venueName }}
                @endif
            </p>
            <ul class="summary">
                <li><strong>{{ $totalGuests }}</strong> {{ $totalGuests === 1 ? 'invitado' : 'invitados' }}</li>
                <li><strong>{{ $totalTables }}</strong> {{ $totalTables === 1 ? 'mesa' : 'mesas' }}</li>
                @if ($unassignedCount > 0)
                    <li><strong>{{ $unassignedCount }}</strong> sin mesa</li>
                @endif
            </ul>
        </header>

        @if ($tables->isEmpty())
            <p class="empty">Aún no hay invitados registrados.</p>
        @else
            <main class="tables">
                @foreach ($tables as $table)
                    <section class="table-card {{ $table['assigned'] ? '' : 'table-card--unassigned' }}">
                        <h2 class="table-card__title">
                            <span>{{ $table['name'] }}</span>
                            <span class="table-card__count">{{ $table['guests']->count() }}</span>
                        </h2>
                        <ol class="table-card__list">
                            @foreach ($table['guests'] as $guest)
                                <li>{{ $guest->full_name }}</li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </main>
        @endif

        <footer class="doc-footer">
            Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
        </footer>
    </div>
</body>
</html>
