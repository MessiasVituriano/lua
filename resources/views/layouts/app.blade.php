<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LUA - PetShop | @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --lua-primary: #6f42c1;
            --lua-sidebar: #2c1e4a;
        }
        body { background: #f4f6f9; }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: var(--lua-sidebar);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s;
        }
        .sidebar .brand {
            padding: 1.2rem;
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand i { color: #a78bfa; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.7rem 1.2rem;
            border-radius: 0;
            font-size: 0.9rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link i { width: 24px; }
        .main-content {
            margin-left: 250px;
            padding: 0;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .topbar .loja-selector select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        .content-area { padding: 1.5rem; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-radius: 10px; }
        .btn-lua { background: var(--lua-primary); color: #fff; border: none; }
        .btn-lua:hover { background: #5a32a3; color: #fff; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: #6b7280; }
        .badge-ativo { background: #d1fae5; color: #065f46; }
        .badge-inativo { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    {{-- Sidebar --}}
    <nav class="sidebar">
        <div class="brand">
            <i class="bi bi-heart-pulse-fill"></i> LUA PetShop
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('lojas.*') ? 'active' : '' }}" href="{{ route('lojas.index') }}">
                    <i class="bi bi-shop"></i> Lojas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('bancos.*') ? 'active' : '' }}" href="{{ route('bancos.index') }}">
                    <i class="bi bi-bank2"></i> Bancos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('fornecedores.*') ? 'active' : '' }}" href="{{ route('fornecedores.index') }}">
                    <i class="bi bi-truck"></i> Fornecedores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                    <i class="bi bi-people-fill"></i> Usuarios
                </a>
            </li>
        </ul>
    </nav>

    {{-- Main --}}
    <div class="main-content">
        {{-- Topbar --}}
        <div class="topbar">
            <div>
                <h5 class="mb-0">@yield('title', 'Dashboard')</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if(auth()->user()->lojas->count() > 1)
                    <form action="{{ route('switch-loja') }}" method="POST" class="loja-selector">
                        @csrf
                        <select name="loja_id" onchange="this.form.submit()">
                            @foreach(auth()->user()->lojas as $loja)
                                <option value="{{ $loja->id }}" {{ auth()->user()->loja_id == $loja->id ? 'selected' : '' }}>
                                    {{ $loja->nome }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @elseif(auth()->user()->lojaAtiva)
                    <span class="text-muted small">
                        <i class="bi bi-shop"></i> {{ auth()->user()->lojaAtiva->nome }}
                    </span>
                @endif

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item" type="submit">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="content-area">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
