@extends('layouts.app')

@section('title', $fornecedor->nome)

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card p-4">
                <h6 class="text-muted mb-3">Dados do Fornecedor</h6>

                <div class="mb-2">
                    <span class="text-muted small">Nome</span>
                    <div class="fw-semibold">{{ $fornecedor->nome }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted small">Categoria</span>
                    <div>
                        <span class="badge bg-secondary">
                            {{ \App\Models\Fornecedor::CATEGORIAS[$fornecedor->categoria] ?? $fornecedor->categoria }}
                        </span>
                    </div>
                </div>

                <div class="mb-2">
                    <span class="text-muted small">Telefone</span>
                    <div>{{ $fornecedor->telefone ?? '-' }}</div>
                </div>

                <div class="mb-3">
                    <span class="text-muted small">Status</span>
                    <div>
                        <span class="badge {{ $fornecedor->ativo ? 'badge-ativo' : 'badge-inativo' }}">
                            {{ $fornecedor->ativo ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('fornecedores.edit', $fornecedor) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="{{ route('fornecedores.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card p-4">
                <h6 class="text-muted mb-3">Historico de Pagamentos</h6>
                <p class="text-muted small">O historico de pagamentos estara disponivel apos a implementacao do modulo de pagamentos.</p>
            </div>
        </div>
    </div>
@endsection
