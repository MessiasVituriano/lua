@extends('layouts.app')

@section('title', isset($fornecedor) ? 'Editar Fornecedor' : 'Novo Fornecedor')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form method="POST"
                      action="{{ isset($fornecedor) ? route('fornecedores.update', $fornecedor) : route('fornecedores.store') }}">
                    @csrf
                    @if(isset($fornecedor))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control @error('nome') is-invalid @enderror"
                               id="nome" name="nome" value="{{ old('nome', $fornecedor->nome ?? '') }}" required>
                        @error('nome')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoria *</label>
                        <select class="form-select @error('categoria') is-invalid @enderror" id="categoria" name="categoria" required>
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\Fornecedor::CATEGORIAS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('categoria', $fornecedor->categoria ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('categoria')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control @error('telefone') is-invalid @enderror"
                               id="telefone" name="telefone" value="{{ old('telefone', $fornecedor->telefone ?? '') }}">
                        @error('telefone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(isset($fornecedor))
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="ativo" value="0">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1"
                                       {{ old('ativo', $fornecedor->ativo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ativo">Fornecedor ativo</label>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua">
                            <i class="bi bi-check-lg"></i> {{ isset($fornecedor) ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <a href="{{ route('fornecedores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
