@extends('pdf.layout')

@section('content')

{{-- ── Filtros aplicados ── --}}
@php
    $tiposLabel = ['transferencia_banco' => 'Transf. Banco', 'sangria' => 'Sangria', 'aporte' => 'Aporte', 'transferencia_loja' => 'Transf. Loja'];
    $statusLabels = ['solicitada' => 'Solicitada', 'aprovada' => 'Aprovada', 'rejeitada' => 'Rejeitada'];
@endphp

@if($filtros['data_inicio'] || $filtros['data_fim'] || $filtros['status'] || $filtros['tipo'])
<div style="font-size: 9px; color: #6b7280; margin-bottom: 14px;">
    Filtros:
    @if($filtros['data_inicio'] || $filtros['data_fim'])
        Período: {{ $filtros['data_inicio'] ? \Carbon\Carbon::parse($filtros['data_inicio'])->format('d/m/Y') : '—' }}
        até {{ $filtros['data_fim'] ? \Carbon\Carbon::parse($filtros['data_fim'])->format('d/m/Y') : '—' }}
    @endif
    @if($filtros['tipo']) · Tipo: {{ $tiposLabel[$filtros['tipo']] ?? $filtros['tipo'] }} @endif
    @if($filtros['status']) · Status: {{ $statusLabels[$filtros['status']] ?? $filtros['status'] }} @endif
</div>
@endif

{{-- ── Resumo ── --}}
@php
    $totalValor = $movimentacoes->sum('valor');
    $totalAprovadas = $movimentacoes->where('status', 'aprovada')->sum('valor');
@endphp
<div class="fields-grid section">
    <div class="field">
        <div class="field-label">Total de Registros</div>
        <div class="field-value" style="font-weight: bold;">{{ $movimentacoes->count() }}</div>
    </div>
    <div class="field">
        <div class="field-label">Valor Total</div>
        <div class="field-value" style="font-weight: bold;">R$ {{ number_format($totalValor, 2, ',', '.') }}</div>
    </div>
    <div class="field">
        <div class="field-label">Total Aprovado</div>
        <div class="field-value" style="color: #065f46; font-weight: bold;">R$ {{ number_format($totalAprovadas, 2, ',', '.') }}</div>
    </div>
</div>

{{-- ── Tabela ── --}}
<div class="section">
    <div class="section-title">Movimentações</div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Banco Origem</th>
                <th>Banco Destino</th>
                <th class="text-right">Valor</th>
                <th>Solicitado por</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimentacoes as $m)
            @php
                $tipoStyle = match($m->tipo) {
                    'sangria'             => 'background:#fee2e2;color:#991b1b;',
                    'aporte'              => 'background:#d1fae5;color:#065f46;',
                    'transferencia_banco' => 'background:#dbeafe;color:#1d4ed8;',
                    'transferencia_loja'  => 'background:#ede9fe;color:#5b21b6;',
                    default               => 'background:#f3f4f6;color:#374151;',
                };
                $statusStyle = match($m->status) {
                    'aprovada'  => 'background:#d1fae5;color:#065f46;',
                    'rejeitada' => 'background:#fee2e2;color:#991b1b;',
                    default     => 'background:#fef3c7;color:#92400e;',
                };
            @endphp
            <tr>
                <td>{{ $m->data_movimentacao?->format('d/m/Y') ?? '—' }}</td>
                <td><span class="badge" style="{{ $tipoStyle }}">{{ $tiposLabel[$m->tipo] ?? $m->tipo }}</span></td>
                <td style="font-weight: 600;">{{ $m->descricao }}</td>
                <td>{{ $m->bancoOrigem?->nome ?? '—' }}</td>
                <td>{{ $m->bancoDestino?->nome ?? '—' }}</td>
                <td class="text-right" style="font-weight: bold;">R$ {{ number_format($m->valor, 2, ',', '.') }}</td>
                <td>{{ $m->solicitadoPor?->name ?? '—' }}</td>
                <td><span class="badge" style="{{ $statusStyle }}">{{ $statusLabels[$m->status] ?? $m->status }}</span></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right" style="font-weight: bold;">Total</td>
                <td class="text-right" style="font-weight: bold;">R$ {{ number_format($totalValor, 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
