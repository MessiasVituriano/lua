@extends('layouts.app')

@section('title', 'Bancos')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="text-muted">{{ $bancos->count() }}/5 bancos cadastrados</span>
        @if($bancos->count() < 5)
            <a href="{{ route('bancos.create') }}" class="btn btn-lua">
                <i class="bi bi-plus-lg"></i> Novo Banco
            </a>
        @endif
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th width="160">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bancos as $banco)
                        <tr>
                            <td class="fw-semibold">{{ $banco->nome }}</td>
                            <td>
                                <span class="badge {{ $banco->ativo ? 'badge-ativo' : 'badge-inativo' }}">
                                    {{ $banco->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('bancos.edit', $banco) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('bancos.destroy', $banco) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Tem certeza que deseja remover este banco?')">
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
                            <td colspan="3" class="text-center text-muted py-4">Nenhum banco cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
