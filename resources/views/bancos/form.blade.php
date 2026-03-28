@extends('layouts.app')

@section('title', isset($banco) ? 'Editar Banco' : 'Novo Banco')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4">
                <form method="POST"
                      action="{{ isset($banco) ? route('bancos.update', $banco) : route('bancos.store') }}">
                    @csrf
                    @if(isset($banco))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control @error('nome') is-invalid @enderror"
                               id="nome" name="nome" value="{{ old('nome', $banco->nome ?? '') }}" required>
                        @error('nome')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(isset($banco))
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="ativo" value="0">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1"
                                       {{ old('ativo', $banco->ativo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ativo">Banco ativo</label>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua">
                            <i class="bi bi-check-lg"></i> {{ isset($banco) ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <a href="{{ route('bancos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
