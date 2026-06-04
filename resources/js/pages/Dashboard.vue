<template>
    <div class="dashboard">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Visão geral</h1>
                <p class="page-subtitle">Acompanhamento financeiro e operacional do período</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Início</label>
                <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio">
            </div>
            <div class="filter-group">
                <label class="filter-label">Fim</label>
                <input type="date" class="form-control form-control-sm" v-model="filters.data_fim">
            </div>
            <div class="filter-group">
                <label class="filter-label">Agrupar</label>
                <select class="form-select form-select-sm" v-model="filters.agrupamento">
                    <option value="dia">Dia</option>
                    <option value="mes">Mês</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-sm btn-lua" @click="load">
                    <Search :size="14" /> Filtrar
                </button>
                <div class="range-chips">
                    <button class="chip" @click="setRange('mes')">Este mês</button>
                    <button class="chip" @click="setRange('3meses')">3 meses</button>
                    <button class="chip" @click="setRange('ano')">Ano</button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary"></div>
        </div>

        <template v-if="d && !loading">
            <!-- KPIs -->
            <div class="kpi-grid">
                <div class="kpi">
                    <div class="kpi-label">
                        <TrendingUp :size="14" class="kpi-icon success" />
                        Total Entradas
                    </div>
                    <div class="kpi-value num-tabular">R$ {{ fmt(d.total_entradas) }}</div>
                    <div class="kpi-delta" :class="varClass(d.total_entradas, d.entradas_anterior)">
                        {{ varPercent(d.total_entradas, d.entradas_anterior) }}
                        <span class="kpi-delta-label">vs período anterior</span>
                    </div>
                </div>

                <div class="kpi">
                    <div class="kpi-label">
                        <TrendingDown :size="14" class="kpi-icon danger" />
                        Total Saídas
                    </div>
                    <div class="kpi-value num-tabular">R$ {{ fmt(d.total_saidas) }}</div>
                    <div class="kpi-delta" :class="varClassInv(d.total_saidas, d.saidas_anterior)">
                        {{ varPercent(d.total_saidas, d.saidas_anterior) }}
                        <span class="kpi-delta-label">vs período anterior</span>
                    </div>
                </div>

                <div class="kpi">
                    <div class="kpi-label">
                        <Wallet :size="14" class="kpi-icon primary" />
                        Saldo
                    </div>
                    <div class="kpi-value num-tabular" :class="d.saldo >= 0 ? 'text-primary' : 'text-danger'">
                        R$ {{ fmt(d.saldo) }}
                    </div>
                    <div class="kpi-delta-label">Receitas menos despesas</div>
                </div>

                <div class="kpi">
                    <div class="kpi-label">
                        <Bell :size="14" class="kpi-icon warning" />
                        Alertas
                    </div>
                    <div class="kpi-alert-list">
                        <div class="kpi-alert-row">
                            <span class="badge bg-danger">{{ d.pagamentos_atrasados }}</span>
                            <span>atrasados</span>
                            <span class="ms-auto fw-semibold num-tabular text-danger">R$ {{ fmt(d.total_pagamentos_atrasados) }}</span>
                        </div>
                        <div class="kpi-alert-row">
                            <span class="badge bg-warning">{{ d.pagamentos_pendentes }}</span>
                            <span>pendentes</span>
                            <span class="ms-auto fw-semibold num-tabular text-warning">R$ {{ fmt(d.total_pagamentos_pendentes) }}</span>
                        </div>
                        <div class="kpi-alert-row">
                            <span class="badge bg-info">{{ d.estoque_baixo }}</span>
                            <span>estoque baixo</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fluxo de Caixa Geral (Acumulado) -->
            <div class="card section-card geral-panel">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Fluxo de Caixa Geral — Acumulado</h3>
                        <p class="section-subtitle">
                            Totais históricos sem filtro de período
                            <template v-if="dGeral?.desde"> · desde {{ fmtDate(dGeral.desde) }}</template>
                        </p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" @click="loadGeral" :disabled="loadingGeral" title="Atualizar">
                        <span v-if="loadingGeral" class="spinner-border spinner-border-sm"></span>
                        <span v-else>↻</span>
                    </button>
                </div>

                <div v-if="loadingGeral" class="loading-state py-3">
                    <div class="spinner-border text-primary spinner-border-sm"></div>
                </div>

                <template v-else-if="dGeral">
                    <div class="geral-grid">
                        <div class="geral-card success">
                            <div class="geral-label">Total Vendas (Entradas)</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_entradas) }}</div>
                        </div>
                        <div class="geral-card danger">
                            <div class="geral-label">Total Saídas (Pagamentos)</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_saidas) }}</div>
                        </div>
                        <div class="geral-card warning">
                            <div class="geral-label">Total Sangrias</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_sangrias) }}</div>
                        </div>
                        <div class="geral-card info">
                            <div class="geral-label">Total Aportes</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_aportes) }}</div>
                        </div>
                        <div class="geral-card" :class="dGeral.saldo_geral >= 0 ? 'primary' : 'danger'">
                            <div class="geral-label">Saldo Geral Acumulado</div>
                            <div class="geral-value fw-bold">R$ {{ fmt(dGeral.saldo_geral) }}</div>
                            <div class="geral-formula">Vendas − Saídas − Sangrias + Aportes</div>
                        </div>
                    </div>

                    <!-- Breakdown por forma (mesmo padrão do Movimentações) -->
                    <template v-if="dGeralSaldos">
                        <div class="geral-section-label">Por forma de recebimento</div>
                        <div class="geral-forma-grid">
                            <!-- Caixa Dinheiro -->
                            <div class="geral-forma-card" style="border-left-color: #059669">
                                <div class="geral-forma-title"><i class="bi bi-cash-stack"></i> Caixa Dinheiro</div>
                                <div class="geral-forma-value" :class="dGeralSaldos.caixa_dinheiro.saldo >= 0 ? 'positive' : 'negative'">
                                    R$ {{ fmt(dGeralSaldos.caixa_dinheiro.saldo) }}
                                </div>
                                <div class="geral-forma-sub">
                                    Entradas: R$ {{ fmt(dGeralSaldos.caixa_dinheiro.entradas) }} ·
                                    Saídas: R$ {{ fmt(dGeralSaldos.caixa_dinheiro.saidas) }}
                                </div>
                            </div>
                            <!-- PIX sem banco -->
                            <div v-if="dGeralSaldos.formas?.pix" class="geral-forma-card" style="border-left-color: #d97706">
                                <div class="geral-forma-title"><i class="bi bi-qr-code"></i> PIX (sem banco)</div>
                                <div class="geral-forma-value" :class="dGeralSaldos.formas.pix.saldo >= 0 ? 'positive' : 'negative'">
                                    R$ {{ fmt(dGeralSaldos.formas.pix.saldo) }}
                                </div>
                                <div class="geral-forma-sub">
                                    Entradas: R$ {{ fmt(dGeralSaldos.formas.pix.entradas) }} ·
                                    Saídas: R$ {{ fmt(dGeralSaldos.formas.pix.saidas) }}
                                </div>
                            </div>
                            <!-- Cada banco -->
                            <div v-for="b in dGeralSaldos.bancos" :key="b.id" class="geral-forma-card" style="border-left-color: #0284c7" :class="{ 'opacity-75': !b.ativo }">
                                <div class="geral-forma-title">
                                    <i class="bi bi-bank"></i> {{ b.nome }}
                                    <span v-if="!b.ativo" class="geral-badge-inativo">inativo</span>
                                </div>
                                <div class="geral-forma-value" :class="b.saldo >= 0 ? 'positive' : 'negative'">
                                    R$ {{ fmt(b.saldo) }}
                                </div>
                                <div class="geral-forma-sub">
                                    Entradas: R$ {{ fmt(b.entradas) }} ·
                                    Saídas: R$ {{ fmt(b.saidas) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>

            <!-- Metas (Resumo) -->
            <div v-if="isAdmin" class="card section-card meta-panel">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Resumo de metas</h3>
                        <p class="section-subtitle">Gestão completa disponível na tela de Metas</p>
                    </div>
                    <router-link class="btn btn-sm btn-lua" :to="{ name: 'metas.index' }">
                        Gerenciar metas
                    </router-link>
                </div>

                <div class="row-split">
                    <div class="card meta-summary" :class="[metaStatusClass(metaVenda?.percentual_atingido), metaBorderClass(metaVenda?.percentual_atingido)]">
                        <div class="section-header-inline mb-2">
                            <h4 class="section-title">
                                Meta de venda
                                <span v-if="(metaVenda?.percentual_atingido || 0) >= 100" class="ms-1">🏆</span>
                            </h4>
                            <span class="badge" :class="metaBadgeClass(metaVenda?.percentual_atingido)">{{ metaVenda?.percentual_atingido || 0 }}%</span>
                        </div>
                        <div class="meta-stats">
                            <div><span>Realizado</span><strong>R$ {{ fmt(metaVenda?.valor_realizado) }}</strong></div>
                            <div><span>Meta</span><strong>R$ {{ fmt(metaVenda?.valor_meta) }}</strong></div>
                            <div><span>Restante</span><strong>R$ {{ fmt(metaVenda?.valor_restante) }}</strong></div>
                            <div><span>Média por dia</span><strong>R$ {{ fmt(metaVenda?.media_necessaria_dia) }}</strong></div>
                        </div>
                        <div class="progress goal-progress">
                            <div
                                class="progress-bar"
                                :class="metaBarClass(metaVenda?.percentual_atingido)"
                                :style="metaBarStyle(metaVenda?.percentual_atingido, Math.min(metaVenda?.percentual_atingido || 0, 100))"
                            ></div>
                        </div>
                        <div class="chart-wrap small mt-3">
                            <Bar v-if="metaChartReady && metaVendaChartData.labels.length" :data="metaVendaChartData" :options="metaChartOptions" />
                        </div>
                    </div>
                    <div class="card meta-summary" :class="[metaStatusClass(metaSaldo?.percentual_atingido), metaBorderClass(metaSaldo?.percentual_atingido)]">
                        <div class="section-header-inline mb-2">
                            <h4 class="section-title">
                                Meta por saldo
                                <span v-if="(metaSaldo?.percentual_atingido || 0) >= 100" class="ms-1">🏆</span>
                            </h4>
                            <span class="badge" :class="metaBadgeClass(metaSaldo?.percentual_atingido)">{{ metaSaldo?.percentual_atingido || 0 }}%</span>
                        </div>
                        <div class="meta-stats">
                            <div><span>Realizado</span><strong>R$ {{ fmt(metaSaldo?.valor_realizado) }}</strong></div>
                            <div><span>Meta</span><strong>R$ {{ fmt(metaSaldo?.valor_meta) }}</strong></div>
                            <div><span>Restante</span><strong>R$ {{ fmt(metaSaldo?.valor_restante) }}</strong></div>
                            <div><span>Média por dia</span><strong>R$ {{ fmt(metaSaldo?.media_necessaria_dia) }}</strong></div>
                        </div>
                        <div class="progress goal-progress">
                            <div
                                class="progress-bar"
                                :class="metaBarClass(metaSaldo?.percentual_atingido)"
                                :style="metaBarStyle(metaSaldo?.percentual_atingido, Math.min(metaSaldo?.percentual_atingido || 0, 100))"
                            ></div>
                        </div>
                        <div class="chart-wrap small mt-3">
                            <Bar v-if="metaChartReady && metaSaldoChartData.labels.length" :data="metaSaldoChartData" :options="metaChartOptions" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico principal -->
            <div class="card section-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Entradas vs Saídas</h3>
                        <p class="section-subtitle">Agrupado por {{ filters.agrupamento === 'mes' ? 'mês' : 'dia' }}</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <Bar v-if="barChartReady" :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Vendas por hora do dia -->
            <div class="card section-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Vendas por hora do dia</h3>
                        <p class="section-subtitle">Distribuição das entradas agrupadas por hora de lançamento</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <Bar v-if="barChartReady" :data="vendasHoraChartData" :options="vendasHoraChartOptions" />
                </div>
            </div>

            <!-- Gráfico saldo -->
            <div class="card section-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Saldo por período</h3>
                        <p class="section-subtitle">Resultado líquido (entradas menos saídas)</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <Bar v-if="barChartReady" :data="saldoChartData" :options="saldoChartOptions" />
                </div>
            </div>

            <!-- Gráficos pizza: saldo por forma -->
            <div class="row-split">
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Entradas por forma</h3>
                            <p class="section-subtitle">Distribuição dos recebimentos por tipo</p>
                        </div>
                    </div>
                    <div class="chart-wrap small">
                        <Doughnut v-if="barChartReady && saldoFormaLabels.length" :data="entradasFormaChartData" :options="formaFormaOptions" />
                        <p v-else-if="barChartReady" class="empty-state">Sem dados no período.</p>
                    </div>
                </div>
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Saídas por forma</h3>
                            <p class="section-subtitle">Distribuição dos pagamentos por tipo</p>
                        </div>
                    </div>
                    <div class="chart-wrap small">
                        <Doughnut v-if="barChartReady && Object.keys(d.saidas_por_forma || {}).length" :data="saidasFormaChartData" :options="formaFormaOptions" />
                        <p v-else-if="barChartReady" class="empty-state">Sem dados no período.</p>
                    </div>
                </div>
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Saldo por forma</h3>
                            <p class="section-subtitle">Resultado líquido por tipo (entradas menos saídas)</p>
                        </div>
                    </div>
                    <div class="chart-wrap small">
                        <Doughnut v-if="barChartReady && saldoFormaPositivo.length" :data="saldoFormaDonutData" :options="formaFormaOptions" />
                        <p v-else-if="barChartReady" class="empty-state">Sem dados no período.</p>
                    </div>
                </div>
            </div>

            <!-- Gráficos pizza -->
            <div class="row-split">
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Entradas por forma</h3>
                            <p class="section-subtitle">Distribuição dos recebimentos</p>
                        </div>
                    </div>
                    <div class="chart-wrap small">
                        <Doughnut v-if="Object.keys(d.entradas_por_forma).length" :data="formaChartData" :options="pieOptions" />
                        <p v-else class="empty-state">Sem dados no período.</p>
                    </div>
                </div>
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Saídas por categoria</h3>
                            <p class="section-subtitle">Distribuição das despesas</p>
                        </div>
                    </div>
                    <div class="chart-wrap small">
                        <Doughnut v-if="Object.keys(d.saidas_por_categoria).length" :data="categoriaChartData" :options="pieOptions" />
                        <p v-else class="empty-state">Sem dados no período.</p>
                    </div>
                </div>
            </div>

            <!-- Top 10 melhores e piores -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <Trophy :size="16" class="text-success" />
                        <h3 class="section-title">Top 10 melhores dias</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>#</th><th>Data</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr></thead>
                            <tbody>
                                <tr v-for="(c, i) in d.top_melhores" :key="i">
                                    <td><span class="rank-num">{{ i + 1 }}</span></td>
                                    <td class="fw-medium">{{ fmtDate(c.data) }}</td>
                                    <td class="text-success num-tabular">R$ {{ fmt(c.total_entradas) }}</td>
                                    <td class="text-danger num-tabular">R$ {{ fmt(c.total_saidas) }}</td>
                                    <td class="fw-semibold num-tabular">R$ {{ fmt(c.saldo) }}</td>
                                </tr>
                                <tr v-if="!d.top_melhores.length"><td colspan="5" class="empty-row">Sem dados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header section-header-inline">
                        <TrendingDown :size="16" class="text-danger" />
                        <h3 class="section-title">Top 10 piores dias</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>#</th><th>Data</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr></thead>
                            <tbody>
                                <tr v-for="(c, i) in d.top_piores" :key="i">
                                    <td><span class="rank-num danger">{{ i + 1 }}</span></td>
                                    <td class="fw-medium">{{ fmtDate(c.data) }}</td>
                                    <td class="text-success num-tabular">R$ {{ fmt(c.total_entradas) }}</td>
                                    <td class="text-danger num-tabular">R$ {{ fmt(c.total_saidas) }}</td>
                                    <td class="fw-semibold num-tabular">R$ {{ fmt(c.saldo) }}</td>
                                </tr>
                                <tr v-if="!d.top_piores.length"><td colspan="5" class="empty-row">Sem dados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resumo saidas + maiores despesas -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <h3 class="section-title">Saídas por categoria</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Categoria</th><th>Valor</th><th>%</th></tr></thead>
                            <tbody>
                                <tr v-for="(val, cat) in d.saidas_por_categoria" :key="cat">
                                    <td><span class="badge bg-secondary">{{ catLabels[cat] || cat }}</span></td>
                                    <td class="fw-medium num-tabular">R$ {{ fmt(val) }}</td>
                                    <td class="num-tabular">{{ d.total_saidas > 0 ? ((val / d.total_saidas) * 100).toFixed(1) : '0.0' }}%</td>
                                </tr>
                                <tr v-if="!Object.keys(d.saidas_por_categoria).length"><td colspan="3" class="empty-row">Sem dados.</td></tr>
                                <tr v-else class="row-total">
                                    <td>Total</td>
                                    <td class="num-tabular">R$ {{ fmt(d.total_saidas) }}</td>
                                    <td>100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header section-header-inline">
                        <h3 class="section-title">Top 5 maiores despesas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Descrição</th><th>Categoria</th><th>Valor</th><th>Data</th></tr></thead>
                            <tbody>
                                <tr v-for="(p, i) in d.maiores_despesas" :key="i">
                                    <td class="fw-medium">{{ p.descricao }}</td>
                                    <td><span class="badge bg-secondary">{{ catLabels[p.categoria] || p.categoria }}</span></td>
                                    <td class="text-danger fw-semibold num-tabular">R$ {{ fmt(p.valor_pago) }}</td>
                                    <td>{{ fmtDate(p.data_pagamento) }}</td>
                                </tr>
                                <tr v-if="!d.maiores_despesas.length"><td colspan="4" class="empty-row">Sem dados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Movimentacoes internas -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <ArrowLeftRight :size="16" class="text-primary" />
                        <h3 class="section-title">Movimentações internas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Tipo</th><th>Qtd</th><th>Total</th></tr></thead>
                            <tbody>
                                <tr v-for="(label, key) in movTipos" :key="key" v-if="d.movimentacoes_internas[key]">
                                    <td><span class="badge" :class="movBadge(key)">{{ label }}</span></td>
                                    <td class="num-tabular">{{ d.movimentacoes_internas[key].quantidade }}</td>
                                    <td class="fw-medium num-tabular">R$ {{ fmt(d.movimentacoes_internas[key].total) }}</td>
                                </tr>
                                <tr v-if="!Object.keys(d.movimentacoes_internas).length"><td colspan="3" class="empty-row">Nenhuma movimentação no período.</td></tr>
                                <tr v-if="d.movimentacoes_pendentes > 0" class="row-pending">
                                    <td colspan="2">
                                        <Hourglass :size="14" class="me-1" /> Pendentes de aprovação
                                    </td>
                                    <td class="fw-semibold">{{ d.movimentacoes_pendentes }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagamentos proximos + estoque baixo -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <AlertTriangle :size="16" class="text-warning" />
                        <h3 class="section-title">Pagamentos próximos / atrasados</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr v-for="p in d.pagamentos_proximos" :key="p.id">
                                    <td class="fw-medium">{{ p.descricao }}</td>
                                    <td>{{ fmtDate(p.data_vencimento) }}</td>
                                    <td class="num-tabular">R$ {{ fmt(p.valor_total) }}</td>
                                    <td><span class="badge" :class="pagamentoStatusBadge(p)">{{ pagamentoStatusLabel(p) }}</span></td>
                                </tr>
                                <tr v-if="!d.pagamentos_proximos.length"><td colspan="4" class="empty-row">Nenhum pagamento pendente.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header section-header-inline">
                        <Package :size="16" class="text-danger" />
                        <h3 class="section-title">Produtos com estoque baixo</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Produto</th><th>Atual</th><th>Mínimo</th></tr></thead>
                            <tbody>
                                <tr v-for="p in d.produtos_estoque_baixo" :key="p.id">
                                    <td class="fw-medium">{{ p.nome }}</td>
                                    <td class="text-danger fw-semibold num-tabular">{{ p.estoque_atual }}</td>
                                    <td class="num-tabular">{{ p.estoque_min }}</td>
                                </tr>
                                <tr v-if="!d.produtos_estoque_baixo.length"><td colspan="3" class="empty-row">Estoque OK.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { useAuthStore } from '../stores/auth';
import {
    Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement,
    Title, Tooltip, Legend
} from 'chart.js';
import axios from 'axios';
const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.role === 'admin');
import {
    TrendingUp, TrendingDown, Wallet, Bell, Trophy, ArrowLeftRight,
    AlertTriangle, Package, Search, Hourglass
} from 'lucide-vue-next';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

const d = ref(null);
const loading = ref(true);
const barChartReady = ref(false);
const metaChartReady = ref(false);

const dGeral = ref(null);
const dGeralSaldos = ref(null);
const loadingGeral = ref(true);

async function loadGeral() {
    loadingGeral.value = true;
    try {
        const [{ data: g }, { data: s }] = await Promise.all([
            axios.get('/dashboard/geral'),
            axios.get('/movimentacoes-internas-saldos'),
        ]);
        dGeral.value = g;
        dGeralSaldos.value = s;
    } catch {} finally { loadingGeral.value = false; }
}

const savingMetas = ref(false);
const savingCalendario = ref(false);
const savingExcecao = ref(false);
const savingMetaDiaria = ref(false);
const metaFeedback = ref('');

const diasSemana = [
    { value: 'segunda', label: 'Segunda' },
    { value: 'terca', label: 'Terça' },
    { value: 'quarta', label: 'Quarta' },
    { value: 'quinta', label: 'Quinta' },
    { value: 'sexta', label: 'Sexta' },
    { value: 'sabado', label: 'Sábado' },
    { value: 'domingo', label: 'Domingo' },
];

const metaDraft = reactive({
    competencia: '',
    venda: 0,
    saldo: 0,
});

const calendarioDraft = reactive({
    segunda: false,
    terca: false,
    quarta: false,
    quinta: false,
    sexta: false,
    sabado: false,
    domingo: false,
});

const excecaoDraft = reactive({
    data: '',
    tipo: 'fechado',
    motivo: '',
});

const diaDraft = reactive({
    tipo: 'venda',
    data: '',
    valor_meta: 0,
});

const now = new Date();
const filters = reactive({
    data_inicio: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
    data_fim: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10),
    agrupamento: 'dia',
});

const catLabels = {
    boleto: 'Boleto', imposto: 'Imposto', custo_fixo: 'Custo Fixo',
    funcionario: 'Funcionário', fornecedor: 'Fornecedor', outros: 'Outros',
};

const formaLabels = {
    dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartão Débito', cartao_credito: 'Cartão Crédito',
};

const movTipos = {
    transferencia_banco: 'Transf. Banco', sangria: 'Sangria', aporte: 'Aporte', transferencia_loja: 'Transf. Loja',
};

function movBadge(tipo) {
    return { transferencia_banco: 'bg-info', sangria: 'bg-danger', aporte: 'bg-success', transferencia_loja: 'bg-primary' }[tipo] || 'bg-secondary';
}

const formaColors = ['#059669', '#6e56cf', '#d97706', '#dc2626'];
const catColors = ['#6e56cf', '#dc2626', '#d97706', '#059669', '#0284c7', '#78716c'];

function setRange(range) {
    const n = new Date();
    if (range === 'mes') {
        filters.data_inicio = new Date(n.getFullYear(), n.getMonth(), 1).toISOString().slice(0, 10);
        filters.data_fim = new Date(n.getFullYear(), n.getMonth() + 1, 0).toISOString().slice(0, 10);
    } else if (range === '3meses') {
        filters.data_inicio = new Date(n.getFullYear(), n.getMonth() - 2, 1).toISOString().slice(0, 10);
        filters.data_fim = new Date(n.getFullYear(), n.getMonth() + 1, 0).toISOString().slice(0, 10);
    } else if (range === 'ano') {
        filters.data_inicio = new Date(n.getFullYear(), 0, 1).toISOString().slice(0, 10);
        filters.data_fim = new Date(n.getFullYear(), 11, 31).toISOString().slice(0, 10);
    }
    load();
}

async function load() {
    loading.value = true;
    barChartReady.value = false;
    metaChartReady.value = false;
    try {
        const { data } = await axios.get('/dashboard', { params: filters });
        d.value = data;
        syncMetaState(data);
        barChartReady.value = true;
        metaChartReady.value = true;
    } catch {} finally { loading.value = false; }
}

function syncMetaState(data) {
    const competencia = (data?.metas?.competencia || `${filters.data_inicio.slice(0, 7)}-01`).slice(0, 7);
    metaDraft.competencia = competencia;
    metaDraft.venda = Number(data?.metas?.venda?.valor_meta || data?.metas?.venda?.valor_meta_sugerido || 0);
    metaDraft.saldo = Number(data?.metas?.saldo?.valor_meta || data?.metas?.saldo?.valor_meta_sugerido || 0);
    metaFeedback.value = '';

    const calendarioAtivo = new Set((data?.metas?.calendario || []).filter(item => item.ativa).map(item => item.dia_semana));
    diasSemana.forEach((dia) => {
        calendarioDraft[dia.value] = calendarioAtivo.has(dia.value);
    });

    diaDraft.data = data?.metas?.venda?.dias?.[0]?.data || data?.metas?.saldo?.dias?.[0]?.data || filters.data_inicio;
    diaDraft.valor_meta = Number(data?.metas?.venda?.dias?.[0]?.valor_meta || data?.metas?.saldo?.dias?.[0]?.valor_meta || 0);
    excecaoDraft.data = '';
    excecaoDraft.tipo = 'fechado';
    excecaoDraft.motivo = '';
}

const isDark = computed(() => document.documentElement.getAttribute('data-bs-theme') === 'dark');
const gridColor = computed(() => isDark.value ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)');
const textColor = computed(() => isDark.value ? '#8a84a3' : '#78716c');

const barChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const labels = d.value.grafico.labels.map(l => {
        if (l.length === 7) {
            const [y, m] = l.split('-');
            return ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'][parseInt(m)-1] + '/' + y.slice(2);
        }
        const dt = new Date(l + 'T12:00:00');
        return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    });
    return {
        labels,
        datasets: [
            { label: 'Entradas', data: d.value.grafico.entradas, backgroundColor: '#059669', borderRadius: 4, borderSkipped: false },
            { label: 'Saídas', data: d.value.grafico.saidas, backgroundColor: '#dc2626', borderRadius: 4, borderSkipped: false },
        ],
    };
});

const barChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 14, font: { size: 12 } }
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            boxPadding: 4,
            titleFont: { size: 12, weight: 500 },
            bodyFont: { size: 12 },
            callbacks: {
                label: (ctx) => ctx.dataset.label + ': R$ ' + Number(ctx.raw).toFixed(2).replace('.', ','),
            },
        },
    },
    scales: {
        x: { ticks: { color: textColor.value, maxRotation: 0, font: { size: 11 } }, grid: { display: false } },
        y: {
            ticks: {
                color: textColor.value,
                font: { size: 11 },
                callback: (v) => 'R$ ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v),
            },
            grid: { color: gridColor.value, drawBorder: false },
            border: { display: false },
        },
    },
}));

const formaChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = Object.keys(d.value.entradas_por_forma);
    return {
        labels: keys.map(k => formaLabels[k] || k),
        datasets: [{ data: keys.map(k => d.value.entradas_por_forma[k]), backgroundColor: formaColors.slice(0, keys.length), borderWidth: 0 }],
    };
});

const categoriaChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = Object.keys(d.value.saidas_por_categoria);
    return {
        labels: keys.map(k => catLabels[k] || k),
        datasets: [{ data: keys.map(k => d.value.saidas_por_categoria[k]), backgroundColor: catColors.slice(0, keys.length), borderWidth: 0 }],
    };
});

const saldoChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const labels = d.value.grafico.labels.map(l => {
        if (l.length === 7) {
            const [y, m] = l.split('-');
            return ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'][parseInt(m)-1] + '/' + y.slice(2);
        }
        const dt = new Date(l + 'T12:00:00');
        return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    });
    const saldos = d.value.grafico.entradas.map((e, i) => e - d.value.grafico.saidas[i]);
    return {
        labels,
        datasets: [{
            label: 'Saldo',
            data: saldos,
            backgroundColor: saldos.map(v => v >= 0 ? '#059669' : '#dc2626'),
            borderRadius: 4,
            borderSkipped: false,
        }],
    };
});

const formasPagamentoLabels = {
    dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartão Débito',
    cartao_credito: 'Cartão Crédito', boleto: 'Boleto', transferencia: 'Transferência',
};

const allFormaColors = {
    dinheiro: '#059669', pix: '#6e56cf', cartao_debito: '#0284c7',
    cartao_credito: '#d97706', boleto: '#78716c', transferencia: '#0891b2',
};

const saldoFormaPositivo = computed(() => {
    if (!d.value) return [];
    return saldoFormaLabels.value.filter(k => {
        const e = parseFloat(d.value.entradas_por_forma[k] || 0);
        const s = parseFloat((d.value.saidas_por_forma || {})[k] || 0);
        return (e - s) > 0;
    });
});

const saldoFormaDonutData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = saldoFormaLabels.value;
    const saldos = keys.map(k => {
        const e = parseFloat(d.value.entradas_por_forma[k] || 0);
        const s = parseFloat((d.value.saidas_por_forma || {})[k] || 0);
        return Math.max(0, e - s);
    }).filter((_, i) => keys[i]);
    const posKeys = keys.filter((k, i) => saldos[i] > 0);
    const posVals = saldos.filter(v => v > 0);
    return {
        labels: posKeys.map(k => formasPagamentoLabels[k] || k),
        datasets: [{ data: posVals, backgroundColor: posKeys.map(k => allFormaColors[k] || '#6e56cf'), borderWidth: 0 }],
    };
});

const saldoFormaLabels = computed(() => {
    if (!d.value) return [];
    const keys = new Set([
        ...Object.keys(d.value.entradas_por_forma),
        ...Object.keys(d.value.saidas_por_forma || {}),
    ]);
    return [...keys];
});

const entradasFormaChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = Object.keys(d.value.entradas_por_forma);
    return {
        labels: keys.map(k => formasPagamentoLabels[k] || k),
        datasets: [{ data: keys.map(k => parseFloat(d.value.entradas_por_forma[k] || 0)), backgroundColor: keys.map(k => allFormaColors[k] || '#6e56cf'), borderWidth: 0 }],
    };
});

const saidasFormaChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = Object.keys(d.value.saidas_por_forma || {});
    return {
        labels: keys.map(k => formasPagamentoLabels[k] || k),
        datasets: [{ data: keys.map(k => parseFloat(d.value.saidas_por_forma[k] || 0)), backgroundColor: keys.map(k => allFormaColors[k] || '#6e56cf'), borderWidth: 0 }],
    };
});

const formaFormaOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: {
            position: 'bottom',
            labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', padding: 12, boxWidth: 6, boxHeight: 6, font: { size: 12 } },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            callbacks: { label: (ctx) => ctx.label + ': R$ ' + Number(ctx.raw).toFixed(2).replace('.', ',') },
        },
    },
}));

const saldoCategoriaChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = Object.keys(d.value.saidas_por_categoria);
    const values = keys.map(k => parseFloat(d.value.saidas_por_categoria[k] || 0));
    return {
        labels: keys.map(k => catLabels[k] || k),
        datasets: [{
            label: 'Saídas',
            data: values,
            backgroundColor: catColors.slice(0, keys.length),
            borderRadius: 4,
            borderSkipped: false,
        }],
    };
});

const saldoCategoriaChartOptions = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            boxPadding: 4,
            titleFont: { size: 12, weight: 500 },
            bodyFont: { size: 12 },
            callbacks: {
                label: (ctx) => 'R$ ' + Number(ctx.raw).toFixed(2).replace('.', ','),
            },
        },
    },
    scales: {
        x: {
            ticks: {
                color: textColor.value,
                font: { size: 11 },
                callback: (v) => 'R$ ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v),
            },
            grid: { color: gridColor.value, drawBorder: false },
            border: { display: false },
        },
        y: { ticks: { color: textColor.value, font: { size: 12 } }, grid: { display: false } },
    },
}));

const saldoChartOptions = computed(() => ({    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            boxPadding: 4,
            titleFont: { size: 12, weight: 500 },
            bodyFont: { size: 12 },
            callbacks: {
                label: (ctx) => 'Saldo: R$ ' + Number(ctx.raw).toFixed(2).replace('.', ','),
            },
        },
    },
    scales: {
        x: { ticks: { color: textColor.value, maxRotation: 0, font: { size: 11 } }, grid: { display: false } },
        y: {
            ticks: {
                color: textColor.value,
                font: { size: 11 },
                callback: (v) => 'R$ ' + (Math.abs(v) >= 1000 ? (v/1000).toFixed(0) + 'k' : v),
            },
            grid: { color: gridColor.value, drawBorder: false },
            border: { display: false },
        },
    },
}));

const vendasHoraChartData = computed(() => {
    if (!d.value?.vendas_por_hora) return { labels: [], datasets: [] };
    const horas = d.value.vendas_por_hora.filter(h => h.quantidade > 0);
    const labels = horas.map(h => `${String(h.hora).padStart(2, '0')}h`);
    return {
        labels,
        datasets: [
            {
                label: 'Total (R$)',
                data: horas.map(h => h.total),
                backgroundColor: '#6e56cf',
                borderRadius: 4,
                borderSkipped: false,
                yAxisID: 'y',
            },
            {
                label: 'Qtd. vendas',
                data: horas.map(h => h.quantidade),
                backgroundColor: '#059669',
                borderRadius: 4,
                borderSkipped: false,
                yAxisID: 'y1',
            },
            {
                label: 'Ticket médio (R$)',
                data: horas.map(h => h.quantidade > 0 ? +(h.total / h.quantidade).toFixed(2) : 0),
                backgroundColor: '#d97706',
                borderRadius: 4,
                borderSkipped: false,
                yAxisID: 'y2',
            },
        ],
    };
});

const vendasHoraChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 14, font: { size: 12 } },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            boxPadding: 4,
            titleFont: { size: 12, weight: 500 },
            bodyFont: { size: 12 },
            callbacks: {
                label: (ctx) => {
                    if (ctx.datasetIndex === 0) return 'Total: R$ ' + Number(ctx.raw).toFixed(2).replace('.', ',');
                    if (ctx.datasetIndex === 1) return 'Qtd: ' + ctx.raw;
                    return 'Ticket médio: R$ ' + Number(ctx.raw).toFixed(2).replace('.', ',');
                },
            },
        },
    },
    scales: {
        x: { ticks: { color: textColor.value, font: { size: 11 } }, grid: { display: false } },
        y: {
            type: 'linear',
            position: 'left',
            ticks: {
                color: '#6e56cf',
                font: { size: 11 },
                callback: (v) => 'R$ ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v),
            },
            grid: { color: gridColor.value, drawBorder: false },
            border: { display: false },
        },
        y1: {
            type: 'linear',
            position: 'right',
            display: false,
            ticks: { color: '#059669', font: { size: 11 }, stepSize: 1 },
            grid: { display: false },
            border: { display: false },
        },
        y2: {
            type: 'linear',
            position: 'right',
            ticks: {
                color: '#d97706',
                font: { size: 11 },
                callback: (v) => 'R$ ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v),
            },
            grid: { display: false },
            border: { display: false },
        },
    },
}));

const pieOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: {
            position: 'bottom',
            labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', padding: 12, boxWidth: 6, boxHeight: 6, font: { size: 12 } },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            callbacks: { label: (ctx) => ctx.label + ': R$ ' + Number(ctx.raw).toFixed(2).replace('.', ',') }
        },
    },
}));

const metaVenda = computed(() => d.value?.metas?.venda || null);
const metaSaldo = computed(() => d.value?.metas?.saldo || null);

function metaBarClass(pct) {
    const p = pct || 0;
    if (p >= 100) return 'meta-bar-green';
    if (p >= 90)  return 'meta-bar-blue';
    if (p >= 50)  return 'meta-bar-orange';
    return 'meta-bar-red';
}

function metaBadgeClass(pct) {
    const p = pct || 0;
    if (p >= 100) return 'meta-badge-green';
    if (p >= 90)  return 'meta-badge-blue';
    if (p >= 50)  return 'meta-badge-orange';
    return 'meta-badge-red';
}

function metaStatusClass(pct) {
    return (pct || 0) >= 100 ? 'meta-batida' : '';
}

function metaBorderClass(pct) {
    const p = pct || 0;
    if (p >= 100) return 'meta-border-green';
    if (p >= 90) return 'meta-border-blue';
    if (p >= 50) return 'meta-border-orange';
    return 'meta-border-red';
}

function metaBarStyle(pct, width) {
    const p = pct || 0;
    const color = p >= 100 ? '#16a34a' : p >= 90 ? '#2563eb' : p >= 50 ? '#ea580c' : '#dc2626';

    return {
        width: `${width}%`,
        backgroundColor: color,
        height: '16px',
    };
}

const mesesAbrev = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

function agruparMetaDias(dias, campo) {
    if (filters.agrupamento !== 'mes') return dias.map((d) => d[campo]);
    const mapa = {};
    for (const dia of dias) {
        const mes = dia.data.slice(0, 7);
        mapa[mes] = (mapa[mes] || 0) + dia[campo];
    }
    return Object.values(mapa);
}

function labelsMetaDias(dias) {
    if (filters.agrupamento !== 'mes') {
        return dias.map((dia) => {
            const dt = new Date(dia.data + 'T12:00:00');
            return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
        });
    }
    const meses = [...new Set(dias.map((d) => d.data.slice(0, 7)))].sort();
    return meses.map((m) => {
        const [y, mo] = m.split('-');
        return mesesAbrev[parseInt(mo) - 1] + '/' + y.slice(2);
    });
}

const metaChartLabels = computed(() => {
    const source = metaVenda.value?.dias?.length ? metaVenda.value.dias : metaSaldo.value?.dias || [];
    return labelsMetaDias(source);
});

const metaVendaChartData = computed(() => {
    const dias = metaVenda.value?.dias || [];
    return {
        labels: labelsMetaDias(dias),
        datasets: [
            {
                label: 'Meta de venda',
                data: agruparMetaDias(dias, 'valor_meta'),
                backgroundColor: '#93c5fd',
                borderRadius: 4,
                borderSkipped: false,
            },
            {
                label: 'Venda realizada',
                data: agruparMetaDias(dias, 'valor_realizado'),
                backgroundColor: '#1d4ed8',
                borderRadius: 4,
                borderSkipped: false,
            },
        ],
    };
});

const metaSaldoChartData = computed(() => {
    const dias = metaSaldo.value?.dias || [];
    return {
        labels: labelsMetaDias(dias),
        datasets: [
            {
                label: 'Meta por saldo',
                data: agruparMetaDias(dias, 'valor_meta'),
                backgroundColor: '#86efac',
                borderRadius: 4,
                borderSkipped: false,
            },
            {
                label: 'Saldo realizado',
                data: agruparMetaDias(dias, 'saldo_diario'),
                backgroundColor: '#15803d',
                borderRadius: 4,
                borderSkipped: false,
            },
        ],
    };
});

const metaChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 14, font: { size: 12 } }
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            boxPadding: 4,
            titleFont: { size: 12, weight: 500 },
            bodyFont: { size: 12 },
            callbacks: {
                label: (ctx) => `${ctx.dataset.label}: R$ ${Number(ctx.raw).toFixed(2).replace('.', ',')}`,
            },
        },
    },
    scales: {
        x: { ticks: { color: textColor.value, maxRotation: 0, font: { size: 11 } }, grid: { display: false } },
        y: {
            ticks: {
                color: textColor.value,
                font: { size: 11 },
                callback: (v) => 'R$ ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v),
            },
            grid: { color: gridColor.value, drawBorder: false },
            border: { display: false },
        },
    },
}));

function metaDiariaSelecionada() {
    const metas = d.value?.metas?.[diaDraft.tipo]?.dias || [];
    return metas.find((dia) => dia.data === diaDraft.data) || null;
}

async function salvarMetas() {
    if (!metaDraft.competencia) return;
    savingMetas.value = true;
    try {
        const competencia = `${metaDraft.competencia}-01`;
        await axios.post('/metas', {
            tipo: 'venda',
            competencia,
            valor_meta: metaDraft.venda,
        });
        await axios.post('/metas', {
            tipo: 'saldo',
            competencia,
            valor_meta: metaDraft.saldo,
        });
        metaFeedback.value = 'Metas mensais atualizadas com sucesso.';
        await load();
    } catch (error) {
        metaFeedback.value = error?.response?.data?.message || 'Nao foi possivel salvar as metas.';
    } finally {
        savingMetas.value = false;
    }
}

async function salvarCalendario() {
    savingCalendario.value = true;
    try {
        const dias_ativos = diasSemana.filter((dia) => calendarioDraft[dia.value]).map((dia) => dia.value);
        await axios.post('/metas/calendario', { dias_ativos });
        metaFeedback.value = 'Calendário atualizado.';
        await load();
    } catch (error) {
        metaFeedback.value = error?.response?.data?.message || 'Nao foi possivel salvar o calendário.';
    } finally {
        savingCalendario.value = false;
    }
}

async function salvarExcecao() {
    if (!excecaoDraft.data) return;
    savingExcecao.value = true;
    try {
        await axios.post('/metas/excecao', { ...excecaoDraft });
        metaFeedback.value = 'Exceção salva com sucesso.';
        await load();
    } catch (error) {
        metaFeedback.value = error?.response?.data?.message || 'Nao foi possivel salvar a exceção.';
    } finally {
        savingExcecao.value = false;
    }
}

async function salvarMetaDiaria() {
    const metaDiaria = metaDiariaSelecionada();
    if (!metaDiaria) {
        metaFeedback.value = 'Selecione uma data válida do mês atualizada pelo dashboard.';
        return;
    }

    savingMetaDiaria.value = true;
    try {
        await axios.post(`/metas/dias/${metaDiaria.id}`, { valor_meta: diaDraft.valor_meta });
        metaFeedback.value = 'Meta diária atualizada.';
        await load();
    } catch (error) {
        metaFeedback.value = error?.response?.data?.message || 'Nao foi possivel salvar a meta diária.';
    } finally {
        savingMetaDiaria.value = false;
    }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1'); }
function fmtDate(dt) {
    if (!dt) return '-';
    const s = typeof dt === 'string' ? dt.slice(0, 10) : dt;
    return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR');
}

function pagamentoAtrasado(p) {
    if (p.status === 'atrasado') return true;
    if (p.status !== 'pendente') return false;

    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    const vencimento = new Date(p.data_vencimento + 'T12:00:00');
    return vencimento < hoje;
}

function pagamentoStatusBadge(p) {
    return pagamentoAtrasado(p) ? 'bg-danger' : 'bg-warning text-dark';
}

function pagamentoStatusLabel(p) {
    return pagamentoAtrasado(p) ? 'Atrasado' : 'No dia';
}

function varPercent(atual, anterior) {
    if (!anterior || anterior == 0) return atual > 0 ? '+100%' : '0%';
    const pct = ((atual - anterior) / anterior * 100).toFixed(1);
    return (pct > 0 ? '+' : '') + pct + '%';
}

function varClass(atual, anterior) {
    if (atual >= anterior) return 'up';
    return 'down';
}

function varClassInv(atual, anterior) {
    if (atual <= anterior) return 'up';
    return 'down';
}

onMounted(() => {
    load();
    loadGeral();
});
</script>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 1.25rem; }

/* Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 1rem;
}
.page-title {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: -0.022em;
    color: var(--lua-text);
    margin: 0 0 0.25rem 0;
}
.page-subtitle {
    font-size: 0.875rem;
    color: var(--lua-text-muted);
    margin: 0;
}

/* Filtros */
.filters {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: end;
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    border-radius: var(--lua-radius);
    padding: 0.875rem 1rem;
}
.filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
.filter-label {
    font-size: 0.6875rem;
    font-weight: 500;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--lua-text-muted);
}
.filter-group .form-control,
.filter-group .form-select { min-width: 140px; }
.filter-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-left: auto; }
.range-chips { display: flex; gap: 0.375rem; }
.chip {
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    color: var(--lua-text-soft);
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.12s;
}
.chip:hover { background: var(--lua-surface-muted); color: var(--lua-text); border-color: var(--lua-border-strong); }

/* Loading */
.loading-state { display: flex; justify-content: center; padding: 3rem; }

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.875rem;
}
@media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px) { .kpi-grid { grid-template-columns: 1fr; } }

.kpi {
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    border-radius: var(--lua-radius);
    padding: 1rem 1.125rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}
.kpi-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8125rem;
    color: var(--lua-text-muted);
    font-weight: 500;
}
.kpi-icon.success { color: var(--lua-success); }
.kpi-icon.danger  { color: var(--lua-danger); }
.kpi-icon.primary { color: var(--lua-primary); }
.kpi-icon.warning { color: var(--lua-warning); }

.kpi-value {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 1.4rem;
    font-weight: 600;
    letter-spacing: -0.02em;
    color: var(--lua-text);
    line-height: 1.1;
}
.kpi-delta {
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    gap: 0.35rem;
    align-items: baseline;
}
.kpi-delta.up { color: var(--lua-success); }
.kpi-delta.down { color: var(--lua-danger); }
.kpi-delta-label {
    font-size: 0.75rem;
    color: var(--lua-text-muted);
    font-weight: 400;
}

.kpi-alert-list { display: flex; flex-direction: column; gap: 0.3rem; }
.kpi-alert-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8125rem;
    color: var(--lua-text-soft);
}

.meta-panel { display: flex; flex-direction: column; gap: 1rem; }
.meta-toolbar {
    display: grid;
    grid-template-columns: 1.2fr 1fr 1fr 1.4fr;
    gap: 0.75rem;
    align-items: end;
}
.meta-notice { align-self: stretch; }
.meta-config-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
}
.meta-config {
    padding: 0.95rem;
    border: 1px solid var(--lua-border);
    border-radius: calc(var(--lua-radius) - 2px);
    background: var(--lua-surface-muted);
    min-width: 0;
}
.weekday-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem 0.75rem;
}
.weekday-item {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8125rem;
    color: var(--lua-text-soft);
}
.meta-mini-form {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
}
.meta-summary {
    padding: 0.95rem;
    border: 1px solid var(--lua-border);
    border-radius: calc(var(--lua-radius) - 2px);
    background: var(--lua-surface);
    min-width: 0;
}
.meta-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.meta-stats div {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.meta-stats span {
    font-size: 0.725rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--lua-text-muted);
}
.meta-stats strong {
    font-size: 0.95rem;
    color: var(--lua-text);
    font-weight: 600;
}
.goal-progress {
    height: 16px;
    background: var(--lua-surface-muted);
    border-radius: 999px;
    overflow: hidden;
}
.goal-progress .progress-bar {
    border-radius: 999px;
}

.meta-bar-red { background-color: #dc2626 !important; }
.meta-bar-orange { background-color: #ea580c !important; }
.meta-bar-blue { background-color: #2563eb !important; }
.meta-bar-green { background-color: #16a34a !important; }

.meta-badge-red { background-color: #dc2626; color: #fff; }
.meta-badge-orange { background-color: #ea580c; color: #fff; }
.meta-badge-blue { background-color: #2563eb; color: #fff; }
.meta-badge-green { background-color: #16a34a; color: #fff; }

.meta-border-red { border-color: #dc2626 !important; box-shadow: 0 0 0 1px #dc262633; }
.meta-border-orange { border-color: #ea580c !important; box-shadow: 0 0 0 1px #ea580c33; }
.meta-border-blue { border-color: #2563eb !important; box-shadow: 0 0 0 1px #2563eb33; }
.meta-border-green { border-color: #16a34a !important; box-shadow: 0 0 0 1px #16a34a33; }

.meta-batida {
    border-color: #16a34a !important;
    box-shadow: 0 0 0 1.5px #16a34a40;
}

@media (max-width: 1100px) {
    .meta-toolbar,
    .meta-config-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .meta-mini-form,
    .meta-stats,
    .weekday-grid {
        grid-template-columns: 1fr;
    }
}

/* Sections */
.section-card { padding: 1.125rem 1.25rem; }
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}
.section-header-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.section-title {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 0.9375rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: var(--lua-text);
    margin: 0;
}
.section-subtitle {
    font-size: 0.8125rem;
    color: var(--lua-text-muted);
    margin: 0.15rem 0 0 0;
}

.chart-wrap { height: 320px; position: relative; }
.chart-wrap.small { height: 280px; }
.empty-state {
    color: var(--lua-text-muted);
    text-align: center;
    padding-top: 4rem;
    font-size: 0.875rem;
}
.empty-row {
    color: var(--lua-text-muted);
    text-align: center;
    padding: 1.25rem !important;
    font-size: 0.8125rem;
}

/* Row split */
.row-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.875rem;
}
.row-split > .card { min-width: 0; }
@media (max-width: 900px) { .row-split { grid-template-columns: 1fr; } }

/* Rank chips para top 10 */
.rank-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    border-radius: 6px;
    background: var(--lua-success-soft);
    color: var(--lua-success);
}
.rank-num.danger {
    background: var(--lua-danger-soft);
    color: var(--lua-danger);
}

.fw-medium { font-weight: 500; }
.row-total {
    font-weight: 600;
    background: var(--lua-surface-muted);
}
.row-total td { border-bottom: none; }
.row-pending { background: var(--lua-warning-soft); color: var(--lua-warning); }
.row-pending td { border-bottom: none; }

/* Fluxo Geral Acumulado */
.geral-panel { }
.geral-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.75rem;
    margin-top: 0.75rem;
}
@media (max-width: 1200px) { .geral-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .geral-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .geral-grid { grid-template-columns: 1fr; } }

.geral-card {
    background: var(--lua-surface-muted);
    border: 1px solid var(--lua-border);
    border-radius: var(--lua-radius);
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.geral-card.success { border-left: 3px solid #059669; }
.geral-card.danger  { border-left: 3px solid #dc2626; }
.geral-card.warning { border-left: 3px solid #d97706; }
.geral-card.info    { border-left: 3px solid #0284c7; }
.geral-card.primary { border-left: 3px solid var(--lua-primary); }

.geral-label {
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--lua-text-muted);
}
.geral-value {
    font-size: 1.125rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--lua-text);
}
.geral-formula {
    font-size: 0.7rem;
    color: var(--lua-text-muted);
    margin-top: 0.125rem;
}

/* Breakdown por forma */
.geral-section-label {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--lua-text-muted);
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--lua-border);
}
.geral-forma-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}
.geral-forma-card {
    background: var(--lua-surface-muted);
    border: 1px solid var(--lua-border);
    border-left-width: 3px;
    border-radius: var(--lua-radius);
    padding: 0.875rem 1rem;
}
.geral-forma-title {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--lua-text-soft);
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
.geral-forma-value {
    font-size: 1.25rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.geral-forma-value.positive { color: var(--lua-primary); }
.geral-forma-value.negative { color: #dc2626; }
.geral-forma-sub {
    font-size: 0.75rem;
    color: var(--lua-text-muted);
    margin-top: 0.3rem;
}
.geral-badge-inativo {
    font-size: 0.65rem;
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    border-radius: 4px;
    padding: 0 4px;
    color: var(--lua-text-muted);
    margin-left: 4px;
}
</style>
