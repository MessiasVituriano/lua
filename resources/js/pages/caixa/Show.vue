<template>
    <div v-if="caixa">
        <!-- Resumo -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 border-start border-success border-4">
                    <div class="text-muted small">Entradas</div>
                    <div class="fs-4 fw-bold text-success">R$ {{ fmt(caixa.total_entradas) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-danger border-4">
                    <div class="text-muted small">Saidas</div>
                    <div class="fs-4 fw-bold text-danger">R$ {{ fmt(caixa.total_saidas) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-primary border-4">
                    <div class="text-muted small">Saldo</div>
                    <div class="fs-4 fw-bold">R$ {{ fmt(caixa.saldo) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="text-muted small">Status</div>
                    <div class="fw-bold">
                        <span class="badge" :class="caixa.status === 'fechado' ? 'bg-secondary' : caixa.status === 'pendente' ? 'bg-warning text-dark' : 'bg-success'">
                            {{ caixa.status === 'fechado' ? 'Fechado' : caixa.status === 'pendente' ? 'Pendente' : 'Aberto' }}
                        </span>
                    </div>
                    <div v-if="caixa.fechado_por" class="text-muted small mt-1">
                        por {{ caixa.fechado_por.name }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Totais por forma -->
        <div class="row g-3 mb-4">
            <div class="col-md-3" v-for="(label, key) in formas" :key="key">
                <div class="card p-2 text-center">
                    <div class="text-muted small">{{ label }}</div>
                    <div class="fw-bold">R$ {{ fmt(totaisPorForma[key] || 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Entradas -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0">Entradas</h6>
                <div class="d-flex align-items-center gap-2">
                    <button
                        v-if="caixa.status === 'aberto' && userIsAdmin"
                        class="btn btn-sm btn-outline-success"
                        @click="mostrarAddForm = !mostrarAddForm"
                    >
                        <i class="bi" :class="mostrarAddForm ? 'bi-dash-circle' : 'bi-plus-circle'"></i>
                        {{ mostrarAddForm ? 'Cancelar' : 'Adicionar Entrada' }}
                    </button>
                    <button v-if="caixa.status === 'aberto'" class="btn btn-sm btn-outline-danger" @click="fecharCaixa">
                        <i class="bi bi-lock-fill"></i> Fechar Caixa
                    </button>
                    <template v-else-if="caixa.status === 'pendente'">
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-hourglass-split"></i> Pendente ({{ caixa.fechado_por?.name }})
                        </span>
                        <button v-if="userIsAdmin" class="btn btn-sm btn-success" @click="autorizarCaixa">
                            <i class="bi bi-check-lg"></i> Aprovar
                        </button>
                        <button v-if="userIsAdmin" class="btn btn-sm btn-outline-primary" @click="reabrirCaixa">
                            <i class="bi bi-unlock-fill"></i> Reabrir
                        </button>
                    </template>
                    <template v-else>
                        <button v-if="userIsAdmin" class="btn btn-sm btn-outline-primary" @click="reabrirCaixa">
                            <i class="bi bi-unlock-fill"></i> Reabrir
                        </button>
                    </template>
                </div>
            </div>

            <!-- Formulario de adicionar entrada (apenas admin + caixa aberto) -->
            <div v-if="caixa.status === 'aberto' && userIsAdmin && mostrarAddForm" class="p-3 border-bottom bg-light-subtle">
                <h6 class="mb-3 small text-muted">Nova Entrada</h6>
                <form @submit.prevent="adicionarEntradaForm">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-2">
                            <label class="form-label small">Forma *</label>
                            <select class="form-select form-select-sm" v-model="addForm.forma_recebimento" required @change="onAddFormaChange">
                                <option value="">Selecione...</option>
                                <option v-for="(label, key) in formas" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small">
                                Banco
                                <span v-if="['pix','cartao_debito','cartao_credito'].includes(addForm.forma_recebimento)" class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select form-select-sm"
                                v-model="addForm.banco_id"
                                :disabled="addForm.forma_recebimento === 'dinheiro' || !addForm.forma_recebimento"
                            >
                                <option :value="null">-</option>
                                <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small">{{ addEhCartao ? 'Valor bruto *' : 'Valor *' }}</label>
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" v-model="addForm.valor" required>
                        </div>
                        <div class="col-6 col-md-1">
                            <label class="form-label small">Desconto</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="addForm.desconto" placeholder="0,00">
                        </div>
                        <div v-if="addEhCartao" class="col-12 col-md-2">
                            <label class="form-label small">Bandeira *</label>
                            <select class="form-select form-select-sm" v-model="addForm.bandeira_id" required>
                                <option :value="null">Selecione...</option>
                                <option v-for="b in bandeiras" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div v-if="addForm.forma_recebimento === 'cartao_credito'" class="col-6 col-md-1">
                            <label class="form-label small">Parcelas *</label>
                            <select class="form-select form-select-sm" v-model.number="addForm.parcelas" required>
                                <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
                            </select>
                        </div>
                        <div class="col-12 col-md">
                            <label class="form-label small">Descricao</label>
                            <input type="text" class="form-control form-control-sm" v-model="addForm.descricao" placeholder="Opcional">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-success" :disabled="addLoading">
                                <span v-if="addLoading" class="spinner-border spinner-border-sm"></span>
                                <i v-else class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div v-if="addEhCartao && addPreviewCalc" class="mt-2">
                        <div class="alert py-1 mb-0 small" :class="addPreviewCalc.erro ? 'alert-danger' : 'alert-info'">
                            <span v-if="addPreviewCalc.erro">{{ addPreviewCalc.erro }}</span>
                            <span v-else>
                                Taxa: <strong>{{ fmtPct(addPreviewCalc.taxa_total) }}</strong>
                                — Valor liquido: <strong>R$ {{ fmt(addPreviewCalc.valor_liquido) }}</strong>
                            </span>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Forma</th>
                            <th>Banco</th>
                            <th>Valor</th>
                            <th>Descricao</th>
                            <th v-if="caixa.status === 'aberto' && userIsAdmin" width="90"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in caixa.entradas" :key="e.id">
                            <td>
                                <span class="badge bg-secondary">{{ formas[e.forma_recebimento] }}</span>
                                <div v-if="e.bandeira" class="small text-muted mt-1">
                                    {{ e.bandeira.nome }}
                                    <span v-if="e.parcelas"> · {{ e.parcelas }}x</span>
                                    <span v-if="e.taxa_aplicada"> · taxa {{ fmtPct(e.taxa_aplicada) }}</span>
                                </div>
                            </td>
                            <td>{{ e.banco?.nome || '-' }}</td>
                            <td class="fw-semibold text-success">
                                R$ {{ fmt(e.valor) }}
                                <div v-if="e.valor_bruto && parseFloat(e.valor_bruto) !== parseFloat(e.valor)" class="small text-muted fw-normal">
                                    bruto R$ {{ fmt(e.valor_bruto) }}
                                </div>
                                <div v-if="parseFloat(e.desconto || 0) > 0" class="small text-warning fw-normal">
                                    desc. R$ {{ fmt(e.desconto) }}
                                </div>
                            </td>
                            <td>
                                <div>{{ e.descricao || '-' }}</div>
                                <div v-if="e.itens && e.itens.length" class="small text-muted mt-1">
                                    <span v-for="(it, i) in e.itens" :key="it.id || i" class="me-2">
                                        {{ it.produto?.nome || 'Item' }}
                                        <span v-if="it.peso_gramas">({{ it.peso_gramas }}g)</span>
                                        <span v-if="it.pet"> · {{ it.pet.nome }}</span>
                                        <span v-if="it.pet?.cliente"> ({{ it.pet.cliente.nome }})</span>
                                        <span v-else-if="it.cliente"> ({{ it.cliente.nome }})</span>
                                    </span>
                                </div>
                            </td>
                            <td v-if="caixa.status === 'aberto' && userIsAdmin">
                                <div class="d-flex gap-1">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        title="Editar"
                                        @click="abrirEditarEntrada(e)"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        title="Remover"
                                        :disabled="deletandoId === e.id"
                                        @click="removerEntradaConfirm(e)"
                                    >
                                        <span v-if="deletandoId === e.id" class="spinner-border spinner-border-sm"></span>
                                        <i v-else class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!caixa.entradas?.length">
                            <td :colspan="caixa.status === 'aberto' && userIsAdmin ? 5 : 4" class="text-center text-muted py-4">
                                Nenhuma entrada registrada.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <router-link :to="{ name: 'caixa.historico' }" class="btn btn-outline-secondary mt-3">
            <i class="bi bi-arrow-left"></i> Voltar ao Historico
        </router-link>

        <!-- Movimentacoes do dia (sangrias/aportes) -->
        <div v-if="movimentacoesDia.length" class="card mt-3">
            <div class="card-header bg-white">
                <h6 class="mb-0">Movimentacoes do Dia (Sangrias / Aportes)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Descricao</th>
                            <th>Banco</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in movimentacoesDia" :key="m.id">
                            <td>
                                <span class="badge" :class="m.tipo === 'sangria' ? 'bg-danger' : 'bg-success'">
                                    {{ m.tipo === 'sangria' ? 'Sangria' : 'Aporte' }}
                                </span>
                            </td>
                            <td>{{ m.descricao || '-' }}</td>
                            <td>{{ m.banco_origem?.nome || m.banco_destino?.nome || '-' }}</td>
                            <td :class="m.tipo === 'sangria' ? 'fw-semibold text-danger' : 'fw-semibold text-success'">
                                {{ m.tipo === 'sangria' ? '-' : '+' }}R$ {{ fmt(m.valor) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de edicao de entrada -->
        <div v-if="editandoEntrada" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Entrada</h5>
                        <button type="button" class="btn-close" @click="editandoEntrada = null" :disabled="editLoading"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Forma de Recebimento</label>
                            <input type="text" class="form-control" :value="formas[editandoEntrada.forma_recebimento]" disabled>
                        </div>
                        <div v-if="editandoEntrada.valor_bruto && parseFloat(editandoEntrada.valor_bruto) !== parseFloat(editandoEntrada.valor)" class="mb-3">
                            <label class="form-label">Valor Bruto</label>
                            <input type="text" class="form-control" :value="'R$ ' + fmt(editandoEntrada.valor_bruto)" disabled>
                            <div class="form-text">Taxa aplicada: {{ fmtPct(editandoEntrada.taxa_aplicada) }}. O valor liquido sera recalculado automaticamente ao salvar.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Valor *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="form-control"
                                    v-model="editForm.valor"
                                    required
                                    autofocus
                                >
                            </div>
                            <div v-if="editandoEntrada.taxa_aplicada" class="form-text text-warning">
                                <i class="bi bi-exclamation-triangle"></i> Esta entrada tem taxa de cartao. O valor sera salvo diretamente sem recalculo da taxa.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descricao</label>
                            <input type="text" class="form-control" v-model="editForm.descricao" placeholder="Opcional" maxlength="255">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="editandoEntrada = null" :disabled="editLoading">Cancelar</button>
                        <button class="btn btn-primary" @click="salvarEditarEntrada" :disabled="editLoading || !editForm.valor">
                            <span v-if="editLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalConfirmSuccess, swalConfirmInfo, swalConfirmDanger } from '../../utils/swal';

const route = useRoute();
const auth = useAuthStore();
const userIsAdmin = computed(() => auth.user?.role === 'admin');
const caixa = ref(null);
const totaisPorForma = ref({});
const movimentacoesDia = ref([]);
const bancos = ref([]);
const bandeiras = ref([]);
const planoAtivo = ref(null);

const formas = { dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartão Débito', cartao_credito: 'Cartão Crédito' };

// ── Add form ──
const mostrarAddForm = ref(false);
const addLoading = ref(false);
const addForm = ref({
    forma_recebimento: '',
    banco_id: null,
    valor: '',
    desconto: 0,
    bandeira_id: null,
    parcelas: 1,
    descricao: '',
});

const addEhCartao = computed(() => ['cartao_debito', 'cartao_credito'].includes(addForm.value.forma_recebimento));

const addModalidadeAtual = computed(() => {
    if (addForm.value.forma_recebimento === 'cartao_debito') return 'debito';
    if (addForm.value.forma_recebimento !== 'cartao_credito' || !addForm.value.parcelas) return null;
    if (addForm.value.parcelas === 1) return 'credito_avista';
    if (addForm.value.parcelas >= 2 && addForm.value.parcelas <= 6) return 'credito_2_6';
    if (addForm.value.parcelas >= 7 && addForm.value.parcelas <= 12) return 'credito_7_12';
    return null;
});

const addPreviewCalc = computed(() => {
    if (!addEhCartao.value || !planoAtivo.value || !addForm.value.bandeira_id || !addForm.value.valor) return null;
    const mod = addModalidadeAtual.value;
    if (!mod) return null;

    const bandeira = planoAtivo.value.bandeiras?.find(b => b.id === addForm.value.bandeira_id);
    if (!bandeira) return null;

    const taxa = bandeira.taxas?.[mod];
    if (taxa === null || taxa === undefined) {
        return { erro: 'Esta bandeira nao aceita esta modalidade neste plano.' };
    }

    const isCredito = addForm.value.forma_recebimento === 'cartao_credito';
    const taxaAnt = isCredito && planoAtivo.value.plano?.taxa_antecipacao
        ? parseFloat(planoAtivo.value.plano.taxa_antecipacao)
        : 0;
    const taxaTotal = parseFloat(taxa) + taxaAnt;
    const desconto = parseFloat(addForm.value.desconto || 0) || 0;
    const bruto = Math.round((parseFloat(addForm.value.valor) - desconto) * 100) / 100;
    if (bruto <= 0) return { erro: 'Valor apos desconto deve ser maior que zero.' };
    const liquido = Math.round(bruto * (1 - taxaTotal / 100) * 100) / 100;

    return { taxa_total: taxaTotal, valor_liquido: liquido };
});

// ── Edit modal ──
const editandoEntrada = ref(null);
const editLoading = ref(false);
const editForm = ref({ valor: '', descricao: '' });

// ── Delete ──
const deletandoId = ref(null);

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtPct(v) { return Number(v || 0).toFixed(2).replace('.', ',') + '%'; }

async function loadCaixa() {
    const { data } = await axios.get('/caixa/' + route.params.id);
    caixa.value = data.caixa;
    totaisPorForma.value = data.totais_por_forma || {};

    if (data.caixa?.data) {
        try {
            const dataStr = data.caixa.data.slice(0, 10);
            const movRes = await axios.get('/movimentacoes-internas', {
                params: { data_inicio: dataStr, data_fim: dataStr, status: 'aprovada' },
            });
            movimentacoesDia.value = (movRes.data.data || []).filter(m => ['sangria', 'aporte'].includes(m.tipo));
        } catch {
            movimentacoesDia.value = [];
        }
    }
}

async function loadBancos() {
    try {
        const { data } = await axios.get('/bancos');
        bancos.value = Array.isArray(data) ? data : (data.data || []);
    } catch { bancos.value = []; }
}

async function loadBandeiras() {
    try {
        const { data } = await axios.get('/bandeiras');
        bandeiras.value = Array.isArray(data) ? data : (data.data || []);
    } catch { bandeiras.value = []; }
}

async function loadPlanoAtivo() {
    try {
        const { data } = await axios.get('/planos-maquininha/ativo');
        planoAtivo.value = data;
    } catch { planoAtivo.value = null; }
}

function onAddFormaChange() {
    if (addForm.value.forma_recebimento === 'dinheiro') {
        addForm.value.banco_id = null;
    }
    if (!addEhCartao.value) {
        addForm.value.bandeira_id = null;
        addForm.value.parcelas = 1;
    } else if (addForm.value.forma_recebimento === 'cartao_debito') {
        addForm.value.parcelas = 1;
    }
}

async function adicionarEntradaForm() {
    addLoading.value = true;
    try {
        const payload = {
            forma_recebimento: addForm.value.forma_recebimento,
            banco_id: addForm.value.banco_id || null,
            valor: parseFloat(addForm.value.valor),
            desconto: parseFloat(addForm.value.desconto || 0),
            bandeira_id: addEhCartao.value ? addForm.value.bandeira_id : null,
            parcelas: addEhCartao.value ? addForm.value.parcelas : null,
            descricao: addForm.value.descricao || null,
        };
        await axios.post('/caixa/' + caixa.value.id + '/entrada', payload);
        swalSuccess('Entrada adicionada com sucesso.');
        mostrarAddForm.value = false;
        addForm.value = { forma_recebimento: '', banco_id: null, valor: '', desconto: 0, bandeira_id: null, parcelas: 1, descricao: '' };
        await loadCaixa();
    } catch (e) {
        const erros = e.response?.data?.errors;
        const msg = erros
            ? Object.values(erros).flat().join('. ')
            : (e.response?.data?.message || 'Erro ao adicionar entrada.');
        swalError(msg);
    } finally {
        addLoading.value = false;
    }
}

function abrirEditarEntrada(entrada) {
    editandoEntrada.value = entrada;
    editForm.value = {
        valor: parseFloat(entrada.valor),
        descricao: entrada.descricao || '',
    };
}

async function salvarEditarEntrada() {
    if (!editForm.value.valor || parseFloat(editForm.value.valor) <= 0) return;
    editLoading.value = true;
    try {
        await axios.put(
            '/caixa/' + caixa.value.id + '/entrada/' + editandoEntrada.value.id,
            { valor: parseFloat(editForm.value.valor), descricao: editForm.value.descricao || null }
        );
        swalSuccess('Entrada atualizada.');
        editandoEntrada.value = null;
        await loadCaixa();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao atualizar entrada.');
    } finally {
        editLoading.value = false;
    }
}

async function removerEntradaConfirm(entrada) {
    if (!(await swalConfirmDanger('Remover Entrada?', 'Esta acao nao pode ser desfeita. O estoque sera revertido se houver itens.'))) return;
    deletandoId.value = entrada.id;
    try {
        await axios.delete('/caixa/' + caixa.value.id + '/entrada/' + entrada.id);
        swalSuccess('Entrada removida.');
        await loadCaixa();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao remover entrada.');
    } finally {
        deletandoId.value = null;
    }
}

async function fecharCaixa() {
    const msg = userIsAdmin.value
        ? 'Fechar o caixa definitivamente?'
        : 'Enviar o caixa para aprovacao do administrador?';
    if (!(await swalConfirmSuccess('Fechar Caixa', msg))) return;
    try {
        const { data } = await axios.post('/caixa/' + caixa.value.id + '/fechar');
        swalSuccess(data.status === 'pendente'
            ? 'Caixa enviado para autorizacao do administrador.'
            : 'Caixa fechado com sucesso.'
        );
        await loadCaixa();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao fechar caixa.');
    }
}

async function autorizarCaixa() {
    if (!(await swalConfirmSuccess('Aprovar Fechamento?', 'O caixa sera fechado definitivamente.'))) return;
    try {
        await axios.post('/caixa/' + caixa.value.id + '/autorizar');
        swalSuccess('Caixa aprovado e fechado com sucesso.');
        await loadCaixa();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao autorizar caixa.');
    }
}

async function reabrirCaixa() {
    if (!(await swalConfirmInfo('Reabrir Caixa?', 'As entradas serao preservadas e poderao ser editadas.'))) return;
    try {
        await axios.post('/caixa/' + caixa.value.id + '/reabrir');
        swalSuccess('Caixa reaberto com sucesso.');
        await loadCaixa();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao reabrir caixa.');
    }
}

onMounted(async () => {
    await loadCaixa();
    loadBancos();
    loadBandeiras();
    loadPlanoAtivo();
});
</script>
