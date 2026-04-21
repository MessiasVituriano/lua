<template>
    <div>
        <!-- Caixa nao aberto -->
        <div v-if="!caixa" class="text-center py-5">
            <div class="card p-5 d-inline-block">
                <i class="bi bi-cash-register fs-1 text-muted"></i>
                <h5 class="mt-3">Caixa de Hoje</h5>
                <p class="text-muted">{{ dataHoje }}</p>
                <button class="btn btn-lua btn-lg" @click="abrirCaixa" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-unlock-fill"></i> Abrir Caixa
                </button>
            </div>
        </div>

        <!-- Caixa aberto -->
        <div v-else>
            <!-- Status bar -->
            <div v-if="caixa.status !== 'fechado'" class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    <i class="bi bi-circle-fill" :class="caixa.status === 'aberto' ? 'text-success' : 'text-warning'" style="font-size:0.5rem"></i>
                    {{ caixa.status === 'aberto' ? 'Caixa aberto' : 'Aguardando aprovacao' }} — sincroniza a cada 15s
                    <span v-if="lastSync" class="ms-1">({{ lastSync }})</span>
                </small>
                <button class="btn btn-sm btn-outline-secondary" @click="load" title="Atualizar agora">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>

            <!-- Resumo -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-4">
                    <div class="card p-3 border-start border-success border-4">
                        <div class="text-muted small">Total Entradas</div>
                        <div class="fs-4 fw-bold text-success">R$ {{ fmt(totalEntradas) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="card p-3 border-start border-danger border-4">
                        <div class="text-muted small">Total Saidas</div>
                        <div class="fs-4 fw-bold text-danger">R$ {{ fmt(caixa.total_saidas) }}</div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card p-3 border-start border-primary border-4">
                        <div class="text-muted small">Saldo</div>
                        <div class="fs-4 fw-bold" :class="saldo >= 0 ? 'text-primary' : 'text-danger'">
                            R$ {{ fmt(saldo) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totais por forma -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3" v-for="(label, key) in formas" :key="key">
                    <div class="card p-2 text-center">
                        <div class="text-muted small">{{ label }}</div>
                        <div class="fw-bold">R$ {{ fmt(totaisFormaCalc[key] || 0) }}</div>
                    </div>
                </div>
            </div>

            <!-- Formulario de entrada (se aberto) -->
            <div v-if="caixa.status === 'aberto'" class="card p-4 mb-4">
                <h6 class="mb-3">Adicionar Entrada</h6>
                <form @submit.prevent="adicionarEntrada">
                    <div class="row g-3 align-items-end">
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Forma *</label>
                            <select class="form-select" v-model="form.forma_recebimento" required @change="onFormaChange">
                                <option value="">Selecione...</option>
                                <option v-for="(label, key) in formas" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Banco</label>
                            <select class="form-select" v-model="form.banco_id" :disabled="form.forma_recebimento === 'dinheiro' || !form.forma_recebimento">
                                <option :value="null">-</option>
                                <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">{{ ehCartao ? 'Valor bruto *' : 'Valor *' }}</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.valor" required ref="valorInput">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Descricao</label>
                            <input type="text" class="form-control" v-model="form.descricao" placeholder="Opcional">
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-lua w-100" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-plus-lg"></i> Adicionar
                            </button>
                        </div>
                    </div>

                    <!-- Campos especificos de cartao -->
                    <div v-if="ehCartao" class="row g-3 align-items-end mt-1">
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Bandeira *</label>
                            <select class="form-select" v-model="form.bandeira_id" required>
                                <option :value="null">Selecione...</option>
                                <option v-for="b in bandeirasDisponiveis" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div v-if="form.forma_recebimento === 'cartao_credito'" class="col-6 col-md-2">
                            <label class="form-label small">Parcelas *</label>
                            <select class="form-select" v-model.number="form.parcelas" required>
                                <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <div v-if="!planoAtivo" class="alert alert-warning py-2 mb-0 small">
                                Nenhum plano de maquininha ativo. <router-link :to="{ name: 'planos-maquininha.create' }">Cadastrar plano</router-link>.
                            </div>
                            <div v-else-if="previewCalc" class="alert py-2 mb-0 small" :class="previewCalc.erro ? 'alert-danger' : 'alert-info'">
                                <div v-if="previewCalc.erro">{{ previewCalc.erro }}</div>
                                <div v-else>
                                    Taxa aplicada: <strong>{{ fmtPct(previewCalc.taxa_total) }}</strong>
                                    <span v-if="previewCalc.com_antecipacao" class="text-muted">
                                        (taxa {{ fmtPct(previewCalc.taxa) }} + antecipacao {{ fmtPct(previewCalc.taxa_antecipacao) }})
                                    </span>
                                    — Valor liquido: <strong>R$ {{ fmt(previewCalc.valor_liquido) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Lista de entradas -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0">
                        Entradas do Dia
                        <span class="badge bg-secondary ms-1">{{ entradas.length }}</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
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
                            <span class="badge bg-secondary">
                                <i class="bi bi-lock-fill"></i> Fechado por {{ caixa.fechado_por?.name }} em {{ fmtDt(caixa.fechado_em) }}
                            </span>
                            <button v-if="userIsAdmin" class="btn btn-sm btn-outline-primary" @click="reabrirCaixa">
                                <i class="bi bi-unlock-fill"></i> Reabrir
                            </button>
                        </template>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Forma</th>
                                <th>Banco</th>
                                <th>Valor</th>
                                <th>Descricao</th>
                                <th v-if="caixa.status === 'aberto'" width="60"></th>
                            </tr>
                        </thead>
                        <TransitionGroup tag="tbody" name="row">
                            <tr v-for="e in entradasOrdenadas" :key="e.id" :class="{ 'table-success': e._new }">
                                <td class="text-muted small">{{ fmtHora(e.created_at) }}</td>
                                <td>
                                    <span class="badge" :class="formaBadge(e.forma_recebimento)">{{ formas[e.forma_recebimento] }}</span>
                                    <div v-if="e.bandeira_id || e.bandeira" class="small text-muted mt-1">
                                        {{ e.bandeira?.nome || '' }}
                                        <span v-if="e.parcelas">· {{ e.parcelas }}x</span>
                                        <span v-if="e.taxa_aplicada">· taxa {{ fmtPct(e.taxa_aplicada) }}</span>
                                    </div>
                                </td>
                                <td>{{ bancoNome(e.banco_id) }}</td>
                                <td class="fw-semibold text-success">
                                    R$ {{ fmt(e.valor) }}
                                    <div v-if="e.valor_bruto && parseFloat(e.valor_bruto) !== parseFloat(e.valor)" class="small text-muted fw-normal">
                                        bruto R$ {{ fmt(e.valor_bruto) }}
                                    </div>
                                </td>
                                <td>{{ e.descricao || '-' }}</td>
                                <td v-if="caixa.status === 'aberto'">
                                    <button class="btn btn-sm btn-outline-danger" @click="removerEntrada(e)" :disabled="e._removing">
                                        <i class="bi" :class="e._removing ? 'spinner-border spinner-border-sm' : 'bi-x'"></i>
                                    </button>
                                </td>
                            </tr>
                        </TransitionGroup>
                        <tbody v-if="!entradas.length">
                            <tr>
                                <td :colspan="caixa.status === 'aberto' ? 6 : 5" class="text-center text-muted py-4">
                                    Nenhuma entrada registrada.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalWarning, swalInfo, swalConfirmDanger, swalConfirmSuccess, swalConfirmInfo } from '../../utils/swal';
const auth = useAuthStore();
const userIsAdmin = computed(() => auth.user?.role === 'admin');
const loading = ref(false);
const saving = ref(false);
const caixa = ref(null);
const entradas = ref([]);
const bancos = ref([]);
const bancosMap = ref({});
const valorInput = ref(null);
const lastSync = ref('');
let pollTimer = null;

const formas = { dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartao Debito', cartao_credito: 'Cartao Credito' };
const dataHoje = new Date().toLocaleDateString('pt-BR');
const form = reactive({
    forma_recebimento: '',
    banco_id: null,
    valor: '',
    descricao: '',
    bandeira_id: null,
    parcelas: 1,
});

const planoAtivo = ref(null); // { plano, bandeiras: [{ id, nome, taxas: { debito, credito_avista, ... } }] }

const ehCartao = computed(() => ['cartao_debito', 'cartao_credito'].includes(form.forma_recebimento));

const bandeirasDisponiveis = computed(() => {
    if (!planoAtivo.value) return [];
    const mod = modalidadeAtual.value;
    return planoAtivo.value.bandeiras.filter(b => {
        return !mod || b.taxas?.[mod] !== null && b.taxas?.[mod] !== undefined;
    });
});

const modalidadeAtual = computed(() => {
    if (form.forma_recebimento === 'cartao_debito') return 'debito';
    if (form.forma_recebimento !== 'cartao_credito' || !form.parcelas) return null;
    if (form.parcelas === 1) return 'credito_avista';
    if (form.parcelas >= 2 && form.parcelas <= 6) return 'credito_2_6';
    if (form.parcelas >= 7 && form.parcelas <= 12) return 'credito_7_12';
    return null;
});

const previewCalc = computed(() => {
    if (!ehCartao.value || !planoAtivo.value || !form.bandeira_id || !form.valor) return null;
    const mod = modalidadeAtual.value;
    if (!mod) return null;

    const bandeira = planoAtivo.value.bandeiras.find(b => b.id === form.bandeira_id);
    if (!bandeira) return null;

    const taxa = bandeira.taxas?.[mod];
    if (taxa === null || taxa === undefined) {
        return { erro: 'Esta bandeira nao aceita esta modalidade neste plano.' };
    }

    const isCredito = form.forma_recebimento === 'cartao_credito';
    const taxaAnt = isCredito && planoAtivo.value.plano.taxa_antecipacao ? parseFloat(planoAtivo.value.plano.taxa_antecipacao) : 0;
    const taxaTotal = parseFloat(taxa) + taxaAnt;
    const bruto = parseFloat(form.valor);
    const liquido = Math.round(bruto * (1 - taxaTotal / 100) * 100) / 100;

    return {
        taxa: parseFloat(taxa),
        taxa_antecipacao: taxaAnt,
        taxa_total: taxaTotal,
        valor_liquido: liquido,
        com_antecipacao: taxaAnt > 0,
    };
});

// Totais calculados localmente (instantaneo)
const totalEntradas = computed(() => entradas.value.reduce((s, e) => s + parseFloat(e.valor || 0), 0));
const saldo = computed(() => totalEntradas.value - parseFloat(caixa.value?.total_saidas || 0));
const totaisFormaCalc = computed(() => {
    const t = {};
    entradas.value.forEach(e => {
        t[e.forma_recebimento] = (t[e.forma_recebimento] || 0) + parseFloat(e.valor || 0);
    });
    return t;
});
const entradasOrdenadas = computed(() => [...entradas.value].sort((a, b) => {
    return new Date(b.created_at) - new Date(a.created_at);
}));

function onFormaChange() {
    if (form.forma_recebimento === 'dinheiro') {
        form.banco_id = null;
    }
    if (!ehCartao.value) {
        form.bandeira_id = null;
        form.parcelas = 1;
    } else if (form.forma_recebimento === 'cartao_debito') {
        form.parcelas = 1;
    }
}

function formaBadge(forma) {
    return {
        dinheiro: 'bg-success', pix: 'bg-primary',
        cartao_debito: 'bg-warning text-dark', cartao_credito: 'bg-danger',
    }[forma] || 'bg-secondary';
}

function bancoNome(id) {
    return bancosMap.value[id] || '-';
}

// ── Load do servidor ──
async function load() {
    const { data } = await axios.get('/caixa/hoje');
    caixa.value = data.caixa;
    if (data.caixa) {
        entradas.value = data.caixa.entradas || [];
    }
    lastSync.value = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// ── Polling silencioso (nao reseta form, nao trava UI) ──
async function silentSync() {
    if (!caixa.value) return;
    try {
        const { data } = await axios.get('/caixa/hoje');
        if (data.caixa) {
            const oldStatus = caixa.value.status;
            const newStatus = data.caixa.status;

            // Detectar mudanca de status (outro usuario fechou/reabriu)
            if (oldStatus !== newStatus) {
                caixa.value.status = newStatus;
                caixa.value.fechado_por = data.caixa.fechado_por;
                caixa.value.fechado_em = data.caixa.fechado_em;
                caixa.value.autorizado_por = data.caixa.autorizado_por;
                caixa.value.autorizado_em = data.caixa.autorizado_em;

                if (newStatus === 'pendente') {
                    swalWarning('O caixa foi enviado para aprovacao por ' + (data.caixa.fechado_por?.name || 'outro usuario') + '.');
                } else if (newStatus === 'fechado' && oldStatus === 'pendente') {
                    swalSuccess('O caixa foi aprovado e fechado.');
                } else if (newStatus === 'aberto' && oldStatus !== 'aberto') {
                    swalSuccess('O caixa foi reaberto por um administrador.');
                }
            }

            caixa.value.total_saidas = data.caixa.total_saidas;

            // Merge entradas apenas se aberto
            if (newStatus === 'aberto') {
                const localIds = new Set(entradas.value.map(e => e.id));
                const serverEntradas = data.caixa.entradas || [];

                serverEntradas.forEach(se => {
                    if (!localIds.has(se.id)) {
                        se._new = true;
                        entradas.value.push(se);
                        setTimeout(() => { se._new = false; }, 2000);
                    }
                });

                const serverIds = new Set(serverEntradas.map(e => e.id));
                entradas.value = entradas.value.filter(e => serverIds.has(e.id));
            } else {
                // Se nao esta aberto, sincronizar entradas do servidor direto
                entradas.value = data.caixa.entradas || [];
            }

            lastSync.value = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    } catch {}
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(silentSync, 15000);
}

function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

// ── Abrir caixa ──
async function abrirCaixa() {
    loading.value = true;
    try {
        await axios.post('/caixa/abrir');
        await load();
        startPolling();
        swalSuccess('Caixa aberto com sucesso.');
    } catch { swalError('Erro ao abrir caixa.'); }
    finally { loading.value = false; }
}

// ── Adicionar entrada (otimista) ──
async function adicionarEntrada() {
    if (!form.forma_recebimento || !form.valor) return;
    saving.value = true;

    // Para cartao: exibir valor liquido na entrada local (consistente com o que o backend vai salvar)
    const isCartao = ehCartao.value;
    const valorLocal = isCartao && previewCalc.value && !previewCalc.value.erro
        ? previewCalc.value.valor_liquido
        : parseFloat(form.valor);

    // Criar entrada temporaria local
    const tempId = 'temp_' + Date.now();
    const entradaLocal = {
        id: tempId,
        forma_recebimento: form.forma_recebimento,
        banco_id: form.banco_id,
        bandeira_id: form.bandeira_id,
        parcelas: isCartao ? form.parcelas : null,
        valor_bruto: isCartao ? parseFloat(form.valor) : null,
        taxa_aplicada: isCartao && previewCalc.value ? previewCalc.value.taxa_total : null,
        valor: valorLocal,
        descricao: form.descricao || null,
        bandeira: isCartao ? planoAtivo.value?.bandeiras.find(b => b.id === form.bandeira_id) : null,
        created_at: new Date().toISOString(),
        _new: true,
        _saving: true,
    };

    entradas.value.push(entradaLocal);

    // Limpar form e focar no valor para proxima entrada
    const payload = {
        forma_recebimento: form.forma_recebimento,
        banco_id: form.banco_id || null,
        valor: form.valor,
        descricao: form.descricao || null,
        bandeira_id: isCartao ? form.bandeira_id : null,
        parcelas: isCartao ? form.parcelas : null,
    };
    form.valor = '';
    form.descricao = '';

    try {
        const { data: entradaReal } = await axios.post('/caixa/' + caixa.value.id + '/entrada', payload);

        // Substituir entrada temporaria pela real
        const idx = entradas.value.findIndex(e => e.id === tempId);
        if (idx !== -1) {
            entradaReal._new = true;
            entradas.value.splice(idx, 1, entradaReal);
            setTimeout(() => { entradaReal._new = false; }, 2000);
        }

        // Focar no campo valor para proxima entrada rapida
        await nextTick();
        valorInput.value?.focus();
    } catch (e) {
        // Remover entrada temporaria em caso de erro
        entradas.value = entradas.value.filter(en => en.id !== tempId);
        const msg = e.response?.data?.errors
            ? Object.values(e.response.data.errors).flat().join('. ')
            : (e.response?.data?.message || 'Erro ao adicionar entrada.');
        swalError(msg);
    } finally {
        saving.value = false;
    }
}

// ── Remover entrada (otimista) ──
async function removerEntrada(entrada) {
    if (!(await swalConfirmDanger('Remover entrada?', 'Esta acao nao pode ser desfeita.'))) return;
    entrada._removing = true;

    // Remover localmente
    const backup = [...entradas.value];
    entradas.value = entradas.value.filter(e => e.id !== entrada.id);

    try {
        await axios.delete('/caixa/' + caixa.value.id + '/entrada/' + entrada.id);
    } catch {
        // Restaurar se falhou
        entradas.value = backup;
        swalError('Erro ao remover entrada.');
    }
}

// ── Fechar caixa ──
async function fecharCaixa() {
    const msg = userIsAdmin.value
        ? 'Fechar o caixa definitivamente?'
        : 'Enviar o caixa para aprovacao do administrador?';
    if (!(await swalConfirmSuccess('Fechar Caixa', msg))) return;
    try {
        const { data } = await axios.post('/caixa/' + caixa.value.id + '/fechar');
        caixa.value = data;
        entradas.value = data.entradas || entradas.value;
        if (data.status === 'fechado') {
            stopPolling();
        }
        swalSuccess(data.status === 'pendente'
            ? 'Caixa enviado para autorizacao do administrador.'
            : 'Caixa fechado com sucesso.'
        );
    } catch { swalError('Erro ao fechar caixa.'); }
}

// ── Reabrir caixa (admin) ──
async function reabrirCaixa() {
    if (!(await swalConfirmInfo('Reabrir Caixa?', 'As entradas serao preservadas.'))) return;
    try {
        const { data } = await axios.post('/caixa/' + caixa.value.id + '/reabrir');
        caixa.value = data;
        entradas.value = data.entradas || [];
        startPolling();
        swalSuccess('Caixa reaberto com sucesso.');
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao reabrir caixa.');
    }
}

// ── Autorizar caixa (admin) ──
async function autorizarCaixa() {
    if (!(await swalConfirmSuccess('Aprovar Fechamento?', 'O caixa sera fechado definitivamente.'))) return;
    try {
        const { data } = await axios.post('/caixa/' + caixa.value.id + '/autorizar');
        caixa.value = data;
        stopPolling();
        swalSuccess('Caixa aprovado e fechado com sucesso.');
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao autorizar caixa.');
    }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtPct(v) { return Number(v || 0).toFixed(2).replace('.', ',') + ' %'; }
function fmtDt(d) { return d ? new Date(d).toLocaleString('pt-BR') : ''; }
function fmtHora(d) {
    if (!d) return '';
    return new Date(d).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

async function loadPlanoAtivo() {
    try {
        const { data } = await axios.get('/planos-maquininha/ativo');
        planoAtivo.value = data;
    } catch {
        planoAtivo.value = null;
    }
}

onMounted(async () => {
    const [, bancosRes] = await Promise.all([load(), axios.get('/bancos'), loadPlanoAtivo()]);
    bancos.value = bancosRes.data.filter(b => b.ativo);
    bancosMap.value = Object.fromEntries(bancosRes.data.map(b => [b.id, b.nome]));

    // Polling ativo enquanto caixa nao estiver definitivamente fechado
    if (caixa.value && caixa.value.status !== 'fechado') {
        startPolling();
    }
});

onUnmounted(stopPolling);
</script>

<style scoped>
.row-enter-active { transition: all 0.3s ease; }
.row-leave-active { transition: all 0.2s ease; }
.row-enter-from { opacity: 0; transform: translateY(-10px); }
.row-leave-to { opacity: 0; transform: translateX(20px); }
</style>
