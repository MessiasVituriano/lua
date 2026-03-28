@extends('layouts.app')

@section('title', isset($usuario) ? 'Editar Usuario' : 'Novo Usuario')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form method="POST"
                      action="{{ isset($usuario) ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
                    @csrf
                    @if(isset($usuario))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="name" class="form-label">Nome *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $usuario->name ?? '') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail *</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email', $usuario->email ?? '') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Senha {{ isset($usuario) ? '(deixe vazio para manter)' : '*' }}
                        </label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" {{ isset($usuario) ? '' : 'required' }}>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lojas</label>
                        <div class="row">
                            @foreach($lojas as $loja)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="lojas[]"
                                               value="{{ $loja->id }}" id="loja_{{ $loja->id }}"
                                               {{ in_array($loja->id, old('lojas', $usuarioLojas ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="loja_{{ $loja->id }}">
                                            {{ $loja->nome }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('lojas')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(isset($usuario))
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="ativo" value="0">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1"
                                       {{ old('ativo', $usuario->ativo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ativo">Usuario ativo</label>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua">
                            <i class="bi bi-check-lg"></i> {{ isset($usuario) ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
