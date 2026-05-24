<template>
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <!-- Show Mode -->
            <div v-if="isShow">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <span class="badge fs-6 me-2" :class="statusClass(pedido.status)">{{ statusLabel(pedido.status) }}</span>
                        <span v-if="pedido.atrasado" class="badge bg-danger">Entrega atrasada</span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <router-link v-if="pedido.status === 'pendente'" :to="{ name: 'pedidos-compra.edit', params: { id: pedido.id } }" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Editar
                        </router-link>
                        <button v-if="pedido.status === 'pendente'" class="btn btn-sm btn-primary" @click="confirmar">
                            <i class="bi bi-check-lg"></i> Confirmar Pedido
                        </button>
                        <button v-if="pedido.status === 'confirmado'" class="btn btn-sm btn-success" @click="confirmarEntrega">
                            <i class="bi bi-box-seam"></i> Confirmar Entrega
                        </button>
                        <button v-if="pedido.status === 'pendente' || pedido.status === 'confirmado'" class="btn btn-sm btn-outline-danger" @click="cancelar">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </div>
                </div>

                <div class="card p-4 mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Fornecedor</div>
                            <div class="fw-semibold">{{ pedido.fornecedor?.nome }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Estimativa de Entrega</div>
                            <div class="fw-semibold">{{ fmtDate(pedido.data_estimativa_entrega) }}</div>
                        </div>
                        <div v-if="pedido.data_entrega" class="col-md-4">
                            <div class="text-muted small">Data de Entrega</div>
                            <div class="fw-semibold">{{ fmtDate(pedido.data_entrega) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Valor Total</div>
                            <div class="fw-semibold fs-5">R$ {{ fmt(pedido.valor_total) }}</div>
                        </div>
                        <div v-if="pedido.data_vencimento" class="col-md-4">
                            <div class="text-muted small">Vencimento Pagamento</div>
                            <div>{{ fmtDate(pedido.data_vencimento) }}</div>
                        </div>
                        <div v-if="pedido.quantidade_parcelas > 1" class="col-md-4">
                            <div class="text-muted small">Parcelamento</div>
                            <div>{{ pedido.quantidade_parcelas }}x a cada {{ pedido.recorrencia_dias }} dias</div>
                        </div>
                        <div v-if="pedido.forma_pagamento" class="col-md-4">
                            <div class="text-muted small">Forma de Pagamento</div>
                            <div>{{ formaLabel(pedido.forma_pagamento) }}</div>
                        </div>
                        <div v-if="!pedido.data_vencimento && pedido.status !== 'entregue' && pedido.status !== 'cancelado'" class="col-12">
                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Dados de pagamento não preenchidos</span>
                        </div>
                        <div v-if="pedido.observacao" class="col-12">
                            <div class="text-muted small">Observação</div>
                            <div>{{ pedido.observacao }}</div>
                        </div>
                    </div>
                </div>

                <!-- Itens -->
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Produtos do Pedido</div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Qtd</th>
                                    <th>Valor Unit.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in pedido.itens" :key="item.id">
                                    <td>{{ item.produto?.nome }}</td>
                                    <td>{{ item.quantidade }}</td>
                                    <td>R$ {{ fmt(item.valor_unitario) }}</td>
                                    <td>R$ {{ fmt(item.valor_total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Dados de Pagamento editável -->
                <div v-if="pedido.status === 'pendente' || pedido.status === 'confirmado'" class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Dados do Pagamento</span>
                        <span v-if="pedido.status === 'confirmado'" class="text-muted small">Alterar irá regenerar os pagamentos pendentes</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Forma de Pagamento</label>
                                <select class="form-select" v-model="pagForm.forma_pagamento">
                                    <option value="">Selecione...</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="pix">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="transferencia">Transferência</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Banco</label>
                                <select class="form-select" v-model="pagForm.banco_id">
                                    <option value="">Nenhum</option>
                                    <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Parcelas</label>
                                <select class="form-select" v-model.number="pagForm.quantidade_parcelas">
                                    <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
                                </select>
                            </div>
                            <div v-if="pagForm.quantidade_parcelas > 1" class="col-md-4">
                                <label class="form-label">Recorrência (dias)</label>
                                <input type="number" min="1" class="form-control" v-model.number="pagForm.recorrencia_dias">
                            </div>
                            <!-- 1 parcela: único input de data -->
                            <div v-if="pagForm.quantidade_parcelas === 1" class="col-md-4">
                                <label class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" v-model="pagForm.datas_parcelas[0]">
                            </div>
                            <!-- N parcelas: um input por parcela -->
                            <template v-else>
                                <div class="col-12">
                                    <div class="row g-2">
                                        <div v-for="i in pagForm.quantidade_parcelas" :key="i" class="col-md-3 col-sm-4 col-6">
                                            <label class="form-label small">Parcela {{ i }}</label>
                                            <input type="date" class="form-control form-control-sm" v-model="pagForm.datas_parcelas[i - 1]">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary btn-sm" @click="savePagamento" :disabled="savingPag">
                                <span v-if="savingPag" class="spinner-border spinner-border-sm me-1"></span>
                                Salvar Dados de Pagamento
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagamentos gerados -->
                <div v-if="pedido.pagamentos?.length" class="card mb-3">
                    <div class="card-header fw-semibold">Pagamentos Gerados</div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Vencimento</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="pg in pedido.pagamentos" :key="pg.id">
                                    <td>{{ pg.descricao }}</td>
                                    <td>{{ fmtDate(pg.data_vencimento) }}</td>
                                    <td>R$ {{ fmt(pg.valor_total) }}</td>
                                    <td><span class="badge" :class="pgStatusClass(pg.status)">{{ pgStatusLabel(pg.status) }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <router-link :to="{ name: 'pedidos-compra.index' }" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Voltar
                </router-link>
            </div>

            <!-- Form Mode (create / edit) -->
            <div v-else>
                <form @submit.prevent="save">
                    <div class="card p-4 mb-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fornecedor *</label>
                                <select class="form-select" :class="{ 'is-invalid': errors.fornecedor_id }" v-model="form.fornecedor_id" required @change="onFornecedorChange">
                                    <option value="">Selecione...</option>
                                    <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
                                </select>
                                <div v-if="errors.fornecedor_id" class="invalid-feedback">{{ errors.fornecedor_id }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estimativa de Entrega *</label>
                                <input type="date" class="form-control" :class="{ 'is-invalid': errors.data_estimativa_entrega }" v-model="form.data_estimativa_entrega" required>
                                <div v-if="errors.data_estimativa_entrega" class="invalid-feedback">{{ errors.data_estimativa_entrega }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observação</label>
                                <textarea class="form-control" rows="2" v-model="form.observacao"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Itens -->
                    <div class="card p-4 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="fw-semibold">Produtos</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem">
                                <i class="bi bi-plus-lg"></i> Adicionar Produto
                            </button>
                        </div>

                        <div v-if="errors.itens" class="alert alert-danger py-2 small">{{ errors.itens }}</div>

                        <div v-for="(item, idx) in form.itens" :key="idx" class="row g-2 align-items-end mb-2">
                            <div class="col-md-5">
                                <label class="form-label small">Produto *</label>
                                <select class="form-select form-select-sm" v-model="item.produto_id" required @change="onProdutoChange(item)">
                                    <option value="">Selecione...</option>
                                    <option v-for="p in produtosFiltrados" :key="p.id" :value="p.id">{{ p.nome }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Quantidade *</label>
                                <input type="number" min="1" class="form-control form-control-sm" v-model.number="item.quantidade" required @input="calcItem(item)">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Valor Unit. (R$) *</label>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" v-model.number="item.valor_unitario" required @input="calcItem(item)">
                            </div>
                            <div class="col-md-1 text-muted small pt-4">
                                = R$ {{ fmt(item.valor_total) }}
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-3" @click="removeItem(idx)" :disabled="form.itens.length === 1">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="text-end mt-2 fw-semibold">
                            Total: R$ {{ fmt(valorTotal) }}
                        </div>
                    </div>

                    <!-- Dados de Pagamento -->
                    <div class="card p-4 mb-3">
                        <div class="fw-semibold mb-3">Dados do Pagamento</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Forma de Pagamento</label>
                                <select class="form-select" v-model="form.forma_pagamento">
                                    <option value="">Selecione...</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="pix">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="transferencia">Transferência</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Banco</label>
                                <select class="form-select" v-model="form.banco_id">
                                    <option value="">Nenhum</option>
                                    <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Parcelas</label>
                                <select class="form-select" v-model.number="form.quantidade_parcelas">
                                    <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
                                </select>
                            </div>
                            <div v-if="form.quantidade_parcelas > 1" class="col-md-4">
                                <label class="form-label">Recorrência (dias)</label>
                                <input type="number" min="1" class="form-control" v-model.number="form.recorrencia_dias">
                            </div>
                            <!-- 1 parcela: único input de data -->
                            <div v-if="form.quantidade_parcelas === 1" class="col-md-4">
                                <label class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" v-model="form.datas_parcelas[0]">
                            </div>
                            <!-- N parcelas: um input por parcela -->
                            <template v-else>
                                <div class="col-12">
                                    <div class="row g-2">
                                        <div v-for="i in form.quantidade_parcelas" :key="i" class="col-md-3 col-sm-4 col-6">
                                            <label class="form-label small">Parcela {{ i }}</label>
                                            <input type="date" class="form-control form-control-sm" v-model="form.datas_parcelas[i - 1]">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            {{ isEdit ? 'Salvar Alterações' : 'Criar Pedido' }}
                        </button>
                        <router-link :to="{ name: 'pedidos-compra.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

function todayStr() {
    return new Date().toISOString().split('T')[0];
}

function todayPlusDays(days) {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return d.toISOString().split('T')[0];
}

function buildDatas(n, base, interval) {
    const b = base || todayStr();
    const iv = interval || 30;
    return Array.from({ length: n }, (_, i) => {
        if (i === 0) return b;
        const d = new Date(b);
        d.setDate(d.getDate() + i * iv);
        return d.toISOString().split('T')[0];
    });
}

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id && route.name === 'pedidos-compra.edit');
const isShow = computed(() => route.name === 'pedidos-compra.show');

const pedido = ref({});
const fornecedores = ref([]);
const produtos = ref([]);
const bancos = ref([]);
const saving = ref(false);
const savingPag = ref(false);
const errors = ref({});

const pagForm = ref({
    data_vencimento: todayStr(),
    datas_parcelas: [todayStr()],
    forma_pagamento: '',
    banco_id: '',
    quantidade_parcelas: 1,
    recorrencia_dias: 30,
});

const form = ref({
    fornecedor_id: '',
    data_estimativa_entrega: todayPlusDays(15),
    observacao: '',
    data_vencimento: todayStr(),
    datas_parcelas: [todayStr()],
    forma_pagamento: '',
    banco_id: '',
    quantidade_parcelas: 1,
    recorrencia_dias: 30,
    itens: [{ produto_id: '', quantidade: 1, valor_unitario: 0, valor_total: 0 }],
});

watch(() => form.value.quantidade_parcelas, (n) => {
    const base = form.value.datas_parcelas?.[0] || todayStr();
    form.value.datas_parcelas = buildDatas(n, base, form.value.recorrencia_dias);
});

watch(() => form.value.recorrencia_dias, (iv) => {
    if (form.value.quantidade_parcelas <= 1) return;
    const base = form.value.datas_parcelas?.[0] || todayStr();
    form.value.datas_parcelas = buildDatas(form.value.quantidade_parcelas, base, iv);
});

watch(() => pagForm.value.quantidade_parcelas, (n) => {
    const base = pagForm.value.datas_parcelas?.[0] || todayStr();
    pagForm.value.datas_parcelas = buildDatas(n, base, pagForm.value.recorrencia_dias);
});

watch(() => pagForm.value.recorrencia_dias, (iv) => {
    if (pagForm.value.quantidade_parcelas <= 1) return;
    const base = pagForm.value.datas_parcelas?.[0] || todayStr();
    pagForm.value.datas_parcelas = buildDatas(pagForm.value.quantidade_parcelas, base, iv);
});

const produtosFiltrados = computed(() => {
    if (!form.value.fornecedor_id) return produtos.value;
    return produtos.value.filter(p => !p.fornecedor_id || p.fornecedor_id == form.value.fornecedor_id);
});

const valorTotal = computed(() =>
    form.value.itens.reduce((acc, i) => acc + (parseFloat(i.valor_total) || 0), 0)
);

function addItem() {
    form.value.itens.push({ produto_id: '', quantidade: 1, valor_unitario: 0, valor_total: 0 });
}

function removeItem(idx) {
    form.value.itens.splice(idx, 1);
}

function onProdutoChange(item) {
    const produto = produtos.value.find(p => p.id == item.produto_id);
    if (produto) {
        item.valor_unitario = parseFloat(produto.valor_custo);
        calcItem(item);
    }
}

function onFornecedorChange() {
    form.value.itens.forEach(item => {
        item.produto_id = '';
        item.valor_unitario = 0;
        item.valor_total = 0;
    });
}

function calcItem(item) {
    item.valor_total = Math.round(((item.valor_unitario || 0) * (item.quantidade || 0)) * 100) / 100;
}

async function save() {
    errors.value = {};
    if (form.value.itens.some(i => !i.produto_id)) {
        errors.value.itens = 'Selecione o produto de cada linha.';
        return;
    }
    saving.value = true;
    try {
        const payload = { ...form.value };
        payload.data_vencimento = payload.datas_parcelas?.[0] || payload.data_vencimento;
        if (payload.datas_parcelas?.length >= 2) {
            const d1 = new Date(payload.datas_parcelas[0]);
            const d2 = new Date(payload.datas_parcelas[1]);
            payload.recorrencia_dias = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
        }
        delete payload.datas_parcelas;
        if (isEdit.value) {
            await axios.put(`/pedidos-compra/${route.params.id}`, payload);
        } else {
            await axios.post('/pedidos-compra', payload);
        }
        router.push({ name: 'pedidos-compra.index' });
    } catch (e) {
        if (e.response?.data?.errors) {
            const flat = {};
            Object.entries(e.response.data.errors).forEach(([k, v]) => { flat[k] = v[0]; });
            errors.value = flat;
        }
    } finally {
        saving.value = false;
    }
}

async function confirmar() {
    if (!confirm('Confirmar o pedido? Os pagamentos serão gerados automaticamente.')) return;
    await axios.post(`/pedidos-compra/${route.params.id}/confirmar`);
    await loadPedido();
}

async function confirmarEntrega() {
    if (!confirm('Confirmar a entrega? O estoque dos produtos será atualizado.')) return;
    await axios.post(`/pedidos-compra/${route.params.id}/confirmar-entrega`);
    await loadPedido();
}

async function cancelar() {
    if (!confirm('Cancelar este pedido?')) return;
    await axios.post(`/pedidos-compra/${route.params.id}/cancelar`);
    await loadPedido();
}

async function savePagamento() {
    savingPag.value = true;
    try {
        const payload = { ...pagForm.value };
        payload.data_vencimento = payload.datas_parcelas?.[0] || payload.data_vencimento;
        if (payload.datas_parcelas?.length >= 2) {
            const d1 = new Date(payload.datas_parcelas[0]);
            const d2 = new Date(payload.datas_parcelas[1]);
            payload.recorrencia_dias = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
        }
        delete payload.datas_parcelas;
        // converte empty string para null
        if (!payload.banco_id) payload.banco_id = null;
        if (!payload.forma_pagamento) payload.forma_pagamento = null;
        if (!payload.data_vencimento) payload.data_vencimento = null;
        const { data } = await axios.patch(`/pedidos-compra/${route.params.id}/pagamento`, payload);
        pedido.value = data;
    } catch (e) {
        alert(e.response?.data?.message ?? 'Erro ao salvar dados de pagamento.');
    } finally {
        savingPag.value = false;
    }
}

async function loadPedido() {
    const { data } = await axios.get(`/pedidos-compra/${route.params.id}`);
    pedido.value = data;

    // Inicializa pagForm sempre (para show e edit)
    const pagQtd = data.quantidade_parcelas ?? 1;
    const pagBase = data.data_vencimento ?? todayStr();
    const pagInterval = data.recorrencia_dias || 30;
    pagForm.value = {
        data_vencimento: pagBase,
        datas_parcelas: buildDatas(pagQtd, pagBase, pagInterval),
        forma_pagamento: data.forma_pagamento ?? '',
        banco_id: data.banco_id ?? '',
        quantidade_parcelas: pagQtd,
        recorrencia_dias: pagInterval,
    };

    if (!isShow.value) {
        const fQtd = data.quantidade_parcelas ?? 1;
        const fBase = data.data_vencimento ?? todayStr();
        const fInterval = data.recorrencia_dias || 30;
        form.value = {
            fornecedor_id: data.fornecedor_id,
            data_estimativa_entrega: data.data_estimativa_entrega,
            observacao: data.observacao ?? '',
            data_vencimento: fBase,
            datas_parcelas: buildDatas(fQtd, fBase, fInterval),
            forma_pagamento: data.forma_pagamento ?? '',
            banco_id: data.banco_id ?? '',
            quantidade_parcelas: fQtd,
            recorrencia_dias: fInterval,
            itens: data.itens.map(i => ({
                produto_id: i.produto_id,
                quantidade: i.quantidade,
                valor_unitario: parseFloat(i.valor_unitario),
                valor_total: parseFloat(i.valor_total),
            })),
        };
    }
}

function fmt(v) {
    return Number(v ?? 0).toFixed(2).replace('.', ',');
}

function fmtDate(d) {
    if (!d) return '—';
    const [y, m, day] = d.split('T')[0].split('-');
    return `${day}/${m}/${y}`;
}

function statusLabel(s) {
    return { pendente: 'Pendente', confirmado: 'Confirmado', entregue: 'Entregue', cancelado: 'Cancelado' }[s] ?? s;
}

function statusClass(s) {
    return { pendente: 'bg-secondary', confirmado: 'bg-primary', entregue: 'bg-success', cancelado: 'bg-danger' }[s] ?? 'bg-secondary';
}

function formaLabel(f) {
    return { dinheiro: 'Dinheiro', pix: 'PIX', boleto: 'Boleto', transferencia: 'Transferência' }[f] ?? f;
}

function pgStatusLabel(s) {
    return { pendente: 'Pendente', pago: 'Pago', atrasado: 'Atrasado', parcial: 'Parcial' }[s] ?? s;
}

function pgStatusClass(s) {
    return { pendente: 'bg-warning text-dark', pago: 'bg-success', atrasado: 'bg-danger', parcial: 'bg-info' }[s] ?? 'bg-secondary';
}

onMounted(async () => {
    const [fRes, pRes, bRes] = await Promise.all([
        axios.get('/fornecedores'),
        axios.get('/produtos', { params: { sem_paginacao: true, per_page: 200 } }),
        axios.get('/bancos'),
    ]);
    fornecedores.value = fRes.data.data ?? fRes.data;
    produtos.value = pRes.data.data ?? pRes.data;
    bancos.value = bRes.data.data ?? bRes.data;

    if (route.params.id) {
        await loadPedido();
    }
});
</script>
