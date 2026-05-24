@extends('pdf.layout')

@section('content')

@if($semValores ?? false)

{{-- ── Destinatário + Dados da Ordem ── --}}
<div class="section">
    <div class="section-title">Destinatário</div>
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <div style="font-size: 16px; font-weight: bold; color: #1a1a2e; margin-bottom: 4px;">
                {{ $pedido->fornecedor?->nome ?? '—' }}
            </div>
            @if($pedido->fornecedor?->telefone)
            <div style="font-size: 10px; color: #6b7280;">{{ $pedido->fornecedor->telefone }}</div>
            @endif
        </div>
        <table style="font-size: 10px; color: #374151; border-collapse: collapse;">
            <tr>
                <td style="padding: 2px 8px 2px 0; color: #6b7280; white-space: nowrap;">Nº da Ordem</td>
                <td style="padding: 2px 0;">#{{ str_pad($pedido->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td style="padding: 2px 8px 2px 0; color: #6b7280; white-space: nowrap;">Emissão</td>
                <td style="padding: 2px 0;">{{ $pedido->created_at->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 2px 8px 2px 0; color: #6b7280; white-space: nowrap;">Prazo solicitado</td>
                <td style="padding: 2px 0;">{{ $pedido->data_estimativa_entrega ? $pedido->data_estimativa_entrega->format('d/m/Y') : '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 2px 8px 2px 0; color: #6b7280; white-space: nowrap;">Solicitante</td>
                <td style="padding: 2px 0;">{{ $pedido->usuario?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 2px 8px 2px 0; color: #6b7280; white-space: nowrap;">Loja / Unidade</td>
                <td style="padding: 2px 0;">{{ $pedido->loja?->nome ?? '—' }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- ── Produtos solicitados ── --}}
<div class="section">
    <div class="section-title">Produtos Solicitados</div>
    <table>
        <thead>
            <tr>
                <th style="width: 40px">#</th>
                <th>Produto</th>
                <th class="text-right" style="width: 120px">Quantidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->itens as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->produto?->nome ?? '—' }}</td>
                <td class="text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ── Observações ── --}}
@if($pedido->observacao)
<div class="section">
    <div class="section-title">Observações / Instruções de Entrega</div>
    <div class="obs-box">{{ $pedido->observacao }}</div>
</div>
@endif


@else

{{-- ── Identificação e Status ── --}}
<div class="section">
    <div class="section-title">Identificação</div>
    <div class="fields-grid">
        <div class="field">
            <div class="field-label">Nº do Pedido</div>
            <div class="field-value">#{{ str_pad($pedido->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div class="field">
            <div class="field-label">Status</div>
            <div class="field-value">
                @php
                    $statusStyle = match($pedido->status) {
                        'confirmado' => 'background:#dbeafe;color:#1d4ed8;',
                        'entregue'   => 'background:#d1fae5;color:#065f46;',
                        'cancelado'  => 'background:#fee2e2;color:#991b1b;',
                        default      => 'background:#f3f4f6;color:#374151;',
                    };
                @endphp
                <span class="badge" style="{{ $statusStyle }}">
                    {{ \App\Models\PedidoCompra::STATUS[$pedido->status] ?? $pedido->status }}
                </span>
            </div>
        </div>
        <div class="field">
            <div class="field-label">Data de Criação</div>
            <div class="field-value">{{ $pedido->created_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="field">
            <div class="field-label">Fornecedor</div>
            <div class="field-value">{{ $pedido->fornecedor?->nome ?? '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Criado por</div>
            <div class="field-value">{{ $pedido->usuario?->name ?? '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Loja</div>
            <div class="field-value">{{ $pedido->loja?->nome ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- ── Datas ── --}}
<div class="section">
    <div class="section-title">Datas</div>
    <div class="fields-grid">
        <div class="field">
            <div class="field-label">Est. de Entrega</div>
            <div class="field-value">
                {{ $pedido->data_estimativa_entrega ? $pedido->data_estimativa_entrega->format('d/m/Y') : '—' }}
            </div>
        </div>
        <div class="field">
            <div class="field-label">Entrega Real</div>
            <div class="field-value">
                {{ $pedido->data_entrega ? $pedido->data_entrega->format('d/m/Y') : '—' }}
            </div>
        </div>
        @if($pedido->status === 'confirmado' || $pedido->status === 'entregue')
        <div class="field">
            <div class="field-label">Confirmado em</div>
            <div class="field-value">
                {{ $pedido->confirmado_em ? $pedido->confirmado_em->format('d/m/Y H:i') : '—' }}
            </div>
        </div>
        @endif
        @if($pedido->status === 'entregue')
        <div class="field">
            <div class="field-label">Entregue em</div>
            <div class="field-value">
                {{ $pedido->entregue_em ? $pedido->entregue_em->format('d/m/Y H:i') : '—' }}
            </div>
        </div>
        @endif
        @if($pedido->status === 'cancelado')
        <div class="field">
            <div class="field-label">Cancelado em</div>
            <div class="field-value">
                {{ $pedido->cancelado_em ? $pedido->cancelado_em->format('d/m/Y H:i') : '—' }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── Itens ── --}}
<div class="section">
    <div class="section-title">Itens do Pedido</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produto</th>
                <th class="text-right">Qtd</th>
                <th class="text-right">Vlr. Unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->itens as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->produto?->nome ?? '—' }}</td>
                <td class="text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item->quantidade * $item->valor_unitario, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- ── Pagamento ── --}}
<div class="section">
    <div class="section-title">Dados de Pagamento</div>
    <div class="fields-grid">
        <div class="field">
            <div class="field-label">Forma de Pagamento</div>
            <div class="field-value">{{ $pedido->forma_pagamento ?? '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Vencimento</div>
            <div class="field-value">
                {{ $pedido->data_vencimento ? $pedido->data_vencimento->format('d/m/Y') : '—' }}
            </div>
        </div>
        @if($pedido->banco)
        <div class="field">
            <div class="field-label">Banco</div>
            <div class="field-value">{{ $pedido->banco->nome }}</div>
        </div>
        @endif
        @if($pedido->quantidade_parcelas)
        <div class="field">
            <div class="field-label">Parcelas</div>
            <div class="field-value">{{ $pedido->quantidade_parcelas }}x</div>
        </div>
        @endif
        @if($pedido->recorrencia_dias)
        <div class="field">
            <div class="field-label">Recorrência</div>
            <div class="field-value">{{ $pedido->recorrencia_dias }} dias</div>
        </div>
        @endif
    </div>
</div>

{{-- ── Pagamentos registrados ── --}}
@if($pedido->pagamentos->count())
<div class="section">
    <div class="section-title">Pagamentos</div>
    <table>
        <thead>
            <tr>
                <th>Vencimento</th>
                <th>Pagamento</th>
                <th>Forma</th>
                <th>Banco</th>
                <th class="text-right">Valor Total</th>
                <th class="text-right">Valor Pago</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->pagamentos as $pag)
            @php
                $pagStatusStyle = match($pag->status) {
                    'pago'    => 'background:#d1fae5;color:#065f46;',
                    'atrasado'=> 'background:#fee2e2;color:#991b1b;',
                    'parcial' => 'background:#fef3c7;color:#92400e;',
                    default   => 'background:#f3f4f6;color:#374151;',
                };
            @endphp
            <tr>
                <td>{{ $pag->data_vencimento ? $pag->data_vencimento->format('d/m/Y') : '—' }}</td>
                <td>{{ $pag->data_pagamento ? $pag->data_pagamento->format('d/m/Y') : '—' }}</td>
                <td>{{ $pag->forma_pagamento ?? '—' }}</td>
                <td>{{ $pag->banco?->nome ?? '—' }}</td>
                <td class="text-right">R$ {{ number_format($pag->valor_total, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($pag->valor_pago, 2, ',', '.') }}</td>
                <td><span class="badge" style="{{ $pagStatusStyle }}">{{ ucfirst($pag->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right">R$ {{ number_format($pedido->pagamentos->sum('valor_total'), 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($pedido->pagamentos->sum('valor_pago'), 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- ── Observação ── --}}
@if($pedido->observacao)
<div class="section">
    <div class="section-title">Observação</div>
    <div class="obs-box">{{ $pedido->observacao }}</div>
</div>
@endif

@endif

@endsection
