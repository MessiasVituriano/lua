@extends('layouts.app')

@section('title', 'Usuarios - ' . $loja->nome)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Usuarios vinculados</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th width="100">Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usuarios as $usuario)
                                <tr>
                                    <td>{{ $usuario->name }}</td>
                                    <td>{{ $usuario->email }}</td>
                                    <td>
                                        <form action="{{ route('lojas.desvincular-usuario', [$loja, $usuario]) }}" method="POST"
                                              onsubmit="return confirm('Desvincular este usuario?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Nenhum usuario vinculado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    {{ $usuarios->links() }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4">
                <h6 class="mb-3">Vincular Usuario</h6>
                @if($disponiveis->count() > 0)
                    <form method="POST" action="{{ route('lojas.vincular-usuario', $loja) }}">
                        @csrf
                        <div class="mb-3">
                            <select name="user_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                @foreach($disponiveis as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-lua w-100">
                            <i class="bi bi-link-45deg"></i> Vincular
                        </button>
                    </form>
                @else
                    <p class="text-muted small mb-0">Todos os usuarios ja estao vinculados.</p>
                @endif
            </div>

            <a href="{{ route('lojas.index') }}" class="btn btn-outline-secondary w-100 mt-3">
                <i class="bi bi-arrow-left"></i> Voltar para Lojas
            </a>
        </div>
    </div>
@endsection
