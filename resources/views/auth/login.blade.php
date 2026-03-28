@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">Lembrar de mim</label>
        </div>

        <button type="submit" class="btn btn-lua w-100 py-2">
            <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>

        <div class="text-center mt-3">
            <a href="{{ route('register') }}" class="text-decoration-none small">Criar uma conta</a>
        </div>
    </form>
@endsection
