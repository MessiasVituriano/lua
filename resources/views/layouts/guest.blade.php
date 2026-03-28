<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUA - PetShop | @yield('title', 'Login')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c1e4a 0%, #6f42c1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
        }
        .auth-card .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-card .brand h1 {
            color: #6f42c1;
            font-weight: 700;
            font-size: 1.8rem;
        }
        .auth-card .brand p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .btn-lua { background: #6f42c1; color: #fff; border: none; }
        .btn-lua:hover { background: #5a32a3; color: #fff; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand">
            <h1><i class="bi bi-heart-pulse-fill"></i> LUA</h1>
            <p>Sistema de Gestao - PetShop</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2">
                @foreach($errors->all() as $error)
                    <div class="small">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
