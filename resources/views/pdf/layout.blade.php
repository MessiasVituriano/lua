<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
        }

        /* ── Cabeçalho ── */
        .header {
            border-bottom: 3px solid #5b21b6;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .loja-nome {
            font-size: 20px;
            font-weight: bold;
            color: #5b21b6;
            letter-spacing: 0.5px;
        }
        .loja-sub {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }
        .header-meta {
            text-align: right;
            font-size: 9px;
            color: #6b7280;
            line-height: 1.6;
        }
        .doc-titulo {
            font-size: 15px;
            font-weight: bold;
            color: #374151;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        /* ── Seções ── */
        .section {
            margin-bottom: 18px;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #5b21b6;
            border-bottom: 1px solid #ddd6fe;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        /* ── Grid de campos ── */
        .fields-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
        }
        .field {
            width: 33.33%;
            padding: 6px 8px 6px 0;
        }
        .field.half { width: 50%; }
        .field.full { width: 100%; }
        .field-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        .field-value {
            font-size: 11px;
            color: #111827;
            font-weight: 500;
        }

        /* ── Badge de status ── */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-pendente  { background: #f3f4f6; color: #374151; }
        .badge-confirmado { background: #dbeafe; color: #1d4ed8; }
        .badge-entregue  { background: #d1fae5; color: #065f46; }
        .badge-cancelado { background: #fee2e2; color: #991b1b; }

        /* ── Tabela ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        thead tr {
            background: #5b21b6;
            color: #fff;
        }
        thead th {
            padding: 7px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        thead th.text-right { text-align: right; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr { border-bottom: 1px solid #f3f4f6; }
        tbody td {
            padding: 7px 8px;
            color: #374151;
        }
        tbody td.text-right { text-align: right; }
        tfoot tr {
            background: #f3f4f6;
            border-top: 2px solid #ddd6fe;
        }
        tfoot td {
            padding: 8px;
            font-weight: bold;
        }
        tfoot td.text-right { text-align: right; }

        /* ── Rodapé ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e5e7eb;
            padding: 6px 0;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
        .footer span { margin: 0 8px; }

        /* ── Observação ── */
        .obs-box {
            background: #fafafa;
            border-left: 3px solid #ddd6fe;
            padding: 8px 10px;
            font-size: 10px;
            color: #374151;
            border-radius: 2px;
        }
    </style>
</head>
<body>

    <!-- Cabeçalho fixo -->
    <div class="header">
        <div class="header-top">
            <div>
                <div class="loja-nome">{{ $lojaNome }}</div>
                <div class="loja-sub">Sistema LUA · Gestão PetShop</div>
            </div>
            <div class="header-meta">
                <div>Gerado em {{ $geradoEm }}</div>
                <div>Por {{ $usuarioNome }}</div>
            </div>
        </div>
        @if($titulo)
        <div class="doc-titulo">{{ $titulo }}</div>
        @endif
    </div>

    <!-- Conteúdo injetado pelo template específico -->
    @yield('content')

    <!-- Rodapé -->
    <div class="footer">
        <span>{{ $lojaNome }}</span>·
        <span>{{ $titulo }}</span>·
        <span>{{ $geradoEm }}</span>
    </div>

</body>
</html>
