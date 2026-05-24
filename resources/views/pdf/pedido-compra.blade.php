@extends('pdf.layout')

@section('content')

@if($semValores ?? false)

{{-- ═══════════════════════════════════════════════════
     LAYOUT FORNECEDOR — Ordem de Compra
═══════════════════════════════════════════════════ --}}

{{-- ── Destinatário e referência ── --}}
<div class="section">
    <div class="section-title">Destinatário</div>
    <div style="font-size: 16px; font-weight: bold; color: #1a1a2e; margin-bottom: 4px;">
        {{ $pedido->fornecedor?->nome ?? '—' }}
    </div>
    @if($pedido->fornecedor?->telefone)
    <div style="font-size: 10px; color: #6b7280;">{{ $pedido->fornecedor->telefone }}</div>
    @endif
</div>

{{-- ── Dados da ordem ── --}}
<div class="section">
    <div class="section-title">Dados da Ordem</div>
    <div class="fields-grid">
        <div class="field">
            <div class="field-label">Nº da Ordem</div>
            <div class="field-value">#{{ str_pad($pedido->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div class="field">
            <div class="field-label">Data de Emissão</div>
            <div class="field-value">{{ $pedido->created_at->format('d/m/Y') }}</div>
        </div>
        <div class="field">
            <div class="field-label">Prazo de Entrega Solicitado</div>
            <div class="field-value">
                {{ $pedido->data_estimativa_entrega ? $pedido->data_estimativa_entrega->format('d/m/Y') : '—' }}
            </div>
        </div>
        <div class="field half">
            <div class="field-label">Solicitante</div>
            <div class="field-value">{{ $pedido->usuario?->name ?? '—' }}</div>
        </div>
        <div class="field half">
            <div class="field-label">Loja / Unidade</div>
            <div class="field-value">{{ $pedido->loja?->nome ?? '—' }}</div>
        </div>
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

{{-- ── Assinatura ── --}}
<div style="margin-top: 40px; display: flex; justify-content: space-between;">
    <div style="text-align: center; width: 45%;">
        <div style="border-top: 1px solid #374151; padding-top: 6px; font-size: 9px; color: #6b7280;">
            Responsável pelo Pedido
        </div>
    </div>
    <div style="text-align: center; width: 45%;">
        <div style="border-top: 1px solid #374151; padding-top: 6px; font-size: 9px; color: #6b7280;">
            Recebido por (Fornecedor)
        </div>
    </div>
</div>

@else

{{-- ═══════════════════════════════════════════════════
     LAYOUT INTERNO — Pedido de Compra completo
═══════════════════════════════════════════════════ --}}

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
                <span class="badge badge-{{ $pedido->status }}">
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

{{-- ── Observação ── --}}
@if($pedido->observacao)
<div class="section">
    <div class="section-title">Observação</div>
    <div class="obs-box">{{ $pedido->observacao }}</div>
</div>
@endif

@endif

@endsection
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
                <span class="badge badge-{{ $pedido->status }}">
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
                @unless($semValores ?? false)
                <th class="text-right">Vlr. Unit.</th>
                <th class="text-right">Subtotal</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->itens as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->produto?->nome ?? '—' }}</td>
                <td class="text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                @unless($semValores ?? false)
                <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item->quantidade * $item->valor_unitario, 2, ',', '.') }}</td>
                @endunless
            </tr>
            @endforeach
        </tbody>
        @unless($semValores ?? false)
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endunless
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
        @unless($semValores ?? false)
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
        @endunless
    </div>
</div>

{{-- ── Observação ── --}}
@if($pedido->observacao)
<div class="section">
    <div class="section-title">Observação</div>
    <div class="obs-box">{{ $pedido->observacao }}</div>
</div>
@endif

@endsection
