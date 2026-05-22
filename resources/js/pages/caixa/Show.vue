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
                        <span class="badge" :class="caixa.status === 'fechado' ? 'bg-secondary' : 'bg-success'">
                            {{ caixa.status === 'fechado' ? 'Fechado' : 'Aberto' }}
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
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Forma</th>
                            <th>Banco</th>
                            <th>Valor</th>
                            <th>Descricao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in caixa.entradas" :key="e.id">
                            <td><span class="badge bg-secondary">{{ formas[e.forma_recebimento] }}</span></td>
                            <td>{{ e.banco?.nome || '-' }}</td>
                            <td class="fw-semibold text-success">R$ {{ fmt(e.valor) }}</td>
                            <td>{{ e.descricao || '-' }}</td>
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
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalConfirmSuccess, swalConfirmInfo } from '../../utils/swal';

const route = useRoute();
const auth = useAuthStore();
const userIsAdmin = computed(() => auth.user?.role === 'admin');
const caixa = ref(null);
const totaisPorForma = ref({});
const movimentacoesDia = ref([]);
const formas = { dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartão Débito', cartao_credito: 'Cartão Crédito' };

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }

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
    if (!(await swalConfirmInfo('Reabrir Caixa?', 'As entradas serao preservadas.'))) return;
    try {
        await axios.post('/caixa/' + caixa.value.id + '/reabrir');
        swalSuccess('Caixa reaberto com sucesso.');
        await loadCaixa();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao reabrir caixa.');
    }
}

onMounted(loadCaixa);
</script>
