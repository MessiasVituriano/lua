<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LUA - PetShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* ===== Light (default) ===== */
        :root, [data-bs-theme="light"] {
            --lua-bg: #f4f6f9;
            --lua-sidebar: #2c1e4a;
            --lua-sidebar-hover: rgba(255,255,255,0.1);
            --lua-topbar: #ffffff;
            --lua-topbar-border: #e2e8f0;
            --lua-card-bg: #ffffff;
            --lua-card-shadow: 0 1px 3px rgba(0,0,0,0.08);
            --lua-text: #1f2937;
            --lua-text-muted: #6b7280;
            --lua-primary: #6f42c1;
            --lua-primary-hover: #5a32a3;
            --lua-input-bg: #ffffff;
            --lua-input-border: #dee2e6;
            --lua-table-hover: rgba(0,0,0,0.03);
        }

        /* ===== Dark ===== */
        [data-bs-theme="dark"] {
            --lua-bg: #121318;
            --lua-sidebar: #1a1b2e;
            --lua-sidebar-hover: rgba(255,255,255,0.08);
            --lua-topbar: #1e1f2e;
            --lua-topbar-border: #2d2e3f;
            --lua-card-bg: #1e1f2e;
            --lua-card-shadow: 0 1px 3px rgba(0,0,0,0.3);
            --lua-text: #e5e7eb;
            --lua-text-muted: #9ca3af;
            --lua-primary: #8b5cf6;
            --lua-primary-hover: #7c3aed;
            --lua-input-bg: #272838;
            --lua-input-border: #3d3e50;
            --lua-table-hover: rgba(255,255,255,0.04);
        }

        body {
            background: var(--lua-bg);
            color: var(--lua-text);
        }

        .card {
            border: none;
            box-shadow: var(--lua-card-shadow);
            border-radius: 10px;
            background: var(--lua-card-bg);
        }
        .card-header.bg-white,
        .card-footer.bg-white {
            background: var(--lua-card-bg) !important;
        }

        .btn-lua { background: var(--lua-primary); color: #fff; border: none; }
        .btn-lua:hover { background: var(--lua-primary-hover); color: #fff; }
        .btn-lua:disabled { opacity: 0.65; color: #fff; background: var(--lua-primary); }

        .table th {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--lua-text-muted);
        }
        .table-hover > tbody > tr:hover { --bs-table-hover-bg: var(--lua-table-hover); }

        .badge-ativo { background: #d1fae5; color: #065f46; }
        .badge-inativo { background: #fee2e2; color: #991b1b; }

        [data-bs-theme="dark"] .badge-ativo { background: #065f46; color: #d1fae5; }
        [data-bs-theme="dark"] .badge-inativo { background: #991b1b; color: #fee2e2; }

        [data-bs-theme="dark"] .table-warning { --bs-table-bg: rgba(217,119,6,0.15); color: var(--lua-text); }
        [data-bs-theme="dark"] .table-danger { --bs-table-bg: rgba(220,38,38,0.15); color: var(--lua-text); }

        /* Theme toggle button */
        .theme-toggle {
            background: none;
            border: 1px solid var(--lua-input-border);
            border-radius: 8px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--lua-text-muted);
            transition: all 0.2s;
        }
        .theme-toggle:hover {
            color: var(--lua-primary);
            border-color: var(--lua-primary);
        }

        /* Responsivo */
        @media (max-width: 767.98px) {
            .table { font-size: 0.82rem; }
            .table th, .table td { padding: 0.4rem 0.5rem; white-space: nowrap; }
            .card { border-radius: 8px; }
            .fs-4 { font-size: 1.1rem !important; }
            h5 { font-size: 1rem; }
        }
    </style>
    <script>
        // Prevent FOUC: apply theme before render
        (function() {
            var t = localStorage.getItem('lua-theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
