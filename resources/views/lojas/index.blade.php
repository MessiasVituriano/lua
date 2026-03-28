@extends('layouts.app')

@section('title', 'Lojas')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div></div>
        <a href="{{ route('lojas.create') }}" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Nova Loja
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Endereco</th>
                        <th>Telefone</th>
                        <th>Usuarios</th>
                        <th>Status</th>
                        <th width="160">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lojas as $loja)
                        <tr>
                            <td class="fw-semibold">{{ $loja->nome }}</td>
                            <td>{{ $loja->endereco ?? '-' }}</td>
                            <td>{{ $loja->telefone ?? '-' }}</td>
                            <td>
                                <a href="{{ route('lojas.usuarios', $loja) }}" class="text-decoration-none">
                                    {{ $loja->usuarios_count }} <i class="bi bi-people"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $loja->ativa ? 'badge-ativo' : 'badge-inativo' }}">
                                    {{ $loja->ativa ? 'Ativa' : 'Inativa' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('lojas.edit', $loja) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('lojas.destroy', $loja) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Tem certeza que deseja remover esta loja?')">
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
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhuma loja cadastrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $lojas->links() }}
    </div>
@endsection
