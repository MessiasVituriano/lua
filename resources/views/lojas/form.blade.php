@extends('layouts.app')

@section('title', isset($loja) ? 'Editar Loja' : 'Nova Loja')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form method="POST"
                      action="{{ isset($loja) ? route('lojas.update', $loja) : route('lojas.store') }}">
                    @csrf
                    @if(isset($loja))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control @error('nome') is-invalid @enderror"
                               id="nome" name="nome" value="{{ old('nome', $loja->nome ?? '') }}" required>
                        @error('nome')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereco</label>
                        <input type="text" class="form-control @error('endereco') is-invalid @enderror"
                               id="endereco" name="endereco" value="{{ old('endereco', $loja->endereco ?? '') }}">
                        @error('endereco')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control @error('telefone') is-invalid @enderror"
                               id="telefone" name="telefone" value="{{ old('telefone', $loja->telefone ?? '') }}">
                        @error('telefone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(isset($loja))
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="ativa" value="0">
                                <input class="form-check-input" type="checkbox" id="ativa" name="ativa" value="1"
                                       {{ old('ativa', $loja->ativa) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ativa">Loja ativa</label>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua">
                            <i class="bi bi-check-lg"></i> {{ isset($loja) ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <a href="{{ route('lojas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
