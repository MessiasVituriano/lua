@extends('layouts.app')

@section('title', 'Fornecedores')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div></div>
        <a href="{{ route('fornecedores.create') }}" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Novo Fornecedor
        </a>
    </div>

    {{-- Filtros --}}
    <div class="card p-3 mb-4">
        <form method="GET" action="{{ route('fornecedores.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Busca</label>
                <input type="text" class="form-control form-control-sm" name="busca"
                       value="{{ request('busca') }}" placeholder="Nome do fornecedor...">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Categoria</label>
                <select class="form-select form-select-sm" name="categoria">
                    <option value="">Todas</option>
                    @foreach(\App\Models\Fornecedor::CATEGORIAS as $key => $label)
                        <option value="{{ $key }}" {{ request('categoria') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="ativo">
                    <option value="">Todos</option>
                    <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativo</option>
                    <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-lua">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="{{ route('fornecedores.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th width="200">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fornecedores as $fornecedor)
                        <tr>
                            <td class="fw-semibold">{{ $fornecedor->nome }}</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ \App\Models\Fornecedor::CATEGORIAS[$fornecedor->categoria] ?? $fornecedor->categoria }}
                                </span>
                            </td>
                            <td>{{ $fornecedor->telefone ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $fornecedor->ativo ? 'badge-ativo' : 'badge-inativo' }}">
                                    {{ $fornecedor->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('fornecedores.show', $fornecedor) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('fornecedores.edit', $fornecedor) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('fornecedores.destroy', $fornecedor) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Tem certeza que deseja remover este fornecedor?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nenhum fornecedor encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $fornecedores->links() }}
    </div>
@endsection
