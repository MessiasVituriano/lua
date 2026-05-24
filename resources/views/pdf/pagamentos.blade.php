@extends('pdf.layout')

@section('content')

{{-- ── Filtros aplicados ── --}}
@php
    $categorias = ['boleto' => 'Boleto', 'imposto' => 'Imposto', 'custo_fixo' => 'Custo Fixo', 'funcionario' => 'Funcionário', 'fornecedor' => 'Fornecedor', 'outros' => 'Outros'];
    $statusLabels = ['pendente' => 'Pendente', 'pago' => 'Pago', 'atrasado' => 'Atrasado', 'parcial' => 'Parcial'];
@endphp

@if($filtros['data_inicio'] || $filtros['data_fim'] || $filtros['status'] || $filtros['categoria'])
<div style="font-size: 9px; color: #6b7280; margin-bottom: 14px;">
    Filtros:
    @if($filtros['data_inicio'] || $filtros['data_fim'])
        Vencimento: {{ $filtros['data_inicio'] ? \Carbon\Carbon::parse($filtros['data_inicio'])->format('d/m/Y') : '—' }}
        até {{ $filtros['data_fim'] ? \Carbon\Carbon::parse($filtros['data_fim'])->format('d/m/Y') : '—' }}
    @endif
    @if($filtros['status']) · Status: {{ $statusLabels[$filtros['status']] ?? $filtros['status'] }} @endif
    @if($filtros['categoria']) · Categoria: {{ $categorias[$filtros['categoria']] ?? $filtros['categoria'] }} @endif
</div>
@endif

{{-- ── Resumo ── --}}
<div class="fields-grid section">
    <div class="field">
        <div class="field-label">Total Geral</div>
        <div class="field-value" style="font-weight: bold;">R$ {{ number_format($totais['total_geral'], 2, ',', '.') }}</div>
    </div>
    <div class="field">
        <div class="field-label">Total Pago</div>
        <div class="field-value" style="color: #065f46; font-weight: bold;">R$ {{ number_format($totais['total_pago'], 2, ',', '.') }}</div>
    </div>
    <div class="field">
        <div class="field-label">Total Pendente</div>
        <div class="field-value" style="color: #92400e; font-weight: bold;">R$ {{ number_format($totais['total_pendente'], 2, ',', '.') }}</div>
    </div>
    <div class="field">
        <div class="field-label">Registros</div>
        <div class="field-value">{{ $totais['count'] }}</div>
    </div>
</div>

{{-- ── Tabela ── --}}
<div class="section">
    <div class="section-title">Pagamentos</div>
    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Fornecedor</th>
                <th>Vencimento</th>
                <th class="text-right">Valor Total</th>
                <th class="text-right">Valor Pago</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagamentos as $p)
            @php
                $pagStatusStyle = match($p->status) {
                    'pago'     => 'background:#d1fae5;color:#065f46;',
                    'atrasado' => 'background:#fee2e2;color:#991b1b;',
                    'parcial'  => 'background:#fef3c7;color:#92400e;',
                    default    => 'background:#f3f4f6;color:#374151;',
                };
            @endphp
            <tr>
                <td style="font-weight: 600;">{{ $p->descricao }}</td>
                <td>{{ $categorias[$p->categoria] ?? $p->categoria }}</td>
                <td>{{ $p->fornecedor?->nome ?? '—' }}</td>
                <td>{{ $p->data_vencimento?->format('d/m/Y') ?? '—' }}</td>
                <td class="text-right">R$ {{ number_format($p->valor_total, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($p->valor_pago, 2, ',', '.') }}</td>
                <td><span class="badge" style="{{ $pagStatusStyle }}">{{ $statusLabels[$p->status] ?? $p->status }}</span></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right" style="font-weight: bold;">Totais</td>
                <td class="text-right" style="font-weight: bold;">R$ {{ number_format($totais['total_geral'], 2, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold; color: #065f46;">R$ {{ number_format($totais['total_pago'], 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
