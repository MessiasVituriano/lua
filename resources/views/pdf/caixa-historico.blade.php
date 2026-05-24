@extends('pdf.layout')

@section('content')

{{-- ── Filtros aplicados ── --}}
@if($filtros['data_inicio'] || $filtros['data_fim'] || $filtros['status'])
<div style="font-size: 9px; color: #6b7280; margin-bottom: 14px;">
    Filtros:
    @if($filtros['data_inicio'] || $filtros['data_fim'])
        Período: {{ $filtros['data_inicio'] ? \Carbon\Carbon::parse($filtros['data_inicio'])->format('d/m/Y') : '—' }}
        até {{ $filtros['data_fim'] ? \Carbon\Carbon::parse($filtros['data_fim'])->format('d/m/Y') : '—' }}
    @endif
    @if($filtros['status'])
        · Status: {{ ['aberto' => 'Aberto', 'pendente' => 'Pendente', 'fechado' => 'Fechado'][$filtros['status']] ?? $filtros['status'] }}
    @endif
</div>
@endif

{{-- ── Resumo ── --}}
<div class="fields-grid section">
    <div class="field">
        <div class="field-label">Total Entradas</div>
        <div class="field-value" style="color: #065f46; font-weight: bold;">R$ {{ number_format($totais['total_entradas'], 2, ',', '.') }}</div>
    </div>
    <div class="field">
        <div class="field-label">Total Saídas</div>
        <div class="field-value" style="color: #991b1b; font-weight: bold;">R$ {{ number_format($totais['total_saidas'], 2, ',', '.') }}</div>
    </div>
    <div class="field">
        <div class="field-label">Saldo do Período</div>
        <div class="field-value" style="font-weight: bold; color: {{ $totais['saldo'] >= 0 ? '#1d4ed8' : '#991b1b' }};">
            R$ {{ number_format($totais['saldo'], 2, ',', '.') }}
        </div>
    </div>
    <div class="field">
        <div class="field-label">Caixas no período</div>
        <div class="field-value">{{ $totais['count'] }}</div>
    </div>
</div>

{{-- ── Tabela ── --}}
<div class="section">
    <div class="section-title">Histórico Diário</div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th class="text-right">Entradas</th>
                <th class="text-right">Saídas</th>
                <th class="text-right">Saldo</th>
                <th>Status</th>
                <th>Fechado por</th>
            </tr>
        </thead>
        <tbody>
            @foreach($caixas as $c)
            @php
                $statusStyle = match($c->status) {
                    'aberto'   => 'background:#d1fae5;color:#065f46;',
                    'pendente' => 'background:#fef3c7;color:#92400e;',
                    'fechado'  => 'background:#f3f4f6;color:#374151;',
                    default    => 'background:#f3f4f6;color:#374151;',
                };
                $statusLabel = ['aberto' => 'Aberto', 'pendente' => 'Pendente', 'fechado' => 'Fechado'][$c->status] ?? $c->status;
            @endphp
            <tr>
                <td style="font-weight: 600;">{{ $c->data->format('d/m/Y') }}</td>
                <td class="text-right" style="color: #065f46;">R$ {{ number_format($c->total_entradas, 2, ',', '.') }}</td>
                <td class="text-right" style="color: #991b1b;">R$ {{ number_format($c->total_saidas, 2, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold; color: {{ $c->saldo >= 0 ? '#1d4ed8' : '#991b1b' }};">
                    R$ {{ number_format($c->saldo, 2, ',', '.') }}
                </td>
                <td><span class="badge" style="{{ $statusStyle }}">{{ $statusLabel }}</span></td>
                <td>{{ $c->fechadoPor?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td style="font-weight: bold;">Totais</td>
                <td class="text-right" style="font-weight: bold; color: #065f46;">R$ {{ number_format($totais['total_entradas'], 2, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold; color: #991b1b;">R$ {{ number_format($totais['total_saidas'], 2, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold; color: {{ $totais['saldo'] >= 0 ? '#1d4ed8' : '#991b1b' }};">
                    R$ {{ number_format($totais['saldo'], 2, ',', '.') }}
                </td>
                <td colspan="2" style="color: #6b7280; font-size: 9px;">{{ $totais['count'] }} caixa(s)</td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
