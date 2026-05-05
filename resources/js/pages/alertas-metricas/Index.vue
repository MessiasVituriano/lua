<template>
    <div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-1">Alertas e Métricas</h5>
                <p class="text-muted mb-0 small">Monitoramento de recompra, estoque e performance de vendas por pet</p>
            </div>
        </div>

        <div class="card p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small">Início</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Fim</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_fim">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn btn-lua btn-sm" @click="load" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                        Atualizar
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" @click="setMesAtual">Mês atual</button>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 h-100">
                    <div class="text-muted small">Recompras (3 dias)</div>
                    <div class="fs-4 fw-bold">{{ data.cards.recompras_3_dias }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 h-100">
                    <div class="text-muted small">Recompras atrasadas</div>
                    <div class="fs-4 fw-bold text-danger">{{ data.cards.recompras_atrasadas }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card p-3 h-100">
                    <div class="text-muted small">Estoque baixo</div>
                    <div class="fs-4 fw-bold text-warning">{{ data.cards.estoque_baixo }}</div>
                </div>
            </div>
            <div class="col-6 col-md-6 col-xl-3">
                <div class="card p-3 h-100">
                    <div class="text-muted small">Ticket médio por cliente</div>
                    <div class="fs-4 fw-bold text-success">R$ {{ fmt(data.cards.ticket_medio_cliente) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card p-3 h-100">
                    <div class="text-muted small">Volume de ração</div>
                    <div class="fs-4 fw-bold">{{ fmtInt(data.cards.volume_racao_gramas) }}g</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-white"><strong>Alertas de Recompra (3 dias)</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Pet</th>
                                    <th>Produto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in data.alertas_recompra" :key="item.id">
                                    <td>{{ fmtDate(item.data_proxima_compra_estimada) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{ clienteNome(item) }}</span>
                                            <a
                                                v-if="clienteTelefone(item)"
                                                class="btn btn-link p-0 text-success"
                                                :href="whatsappLink(item)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="Enviar mensagem no WhatsApp"
                                            >
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ item.pet?.nome || '-' }}</td>
                                    <td>{{ item.produto?.nome || '-' }}</td>
                                </tr>
                                <tr v-if="!data.alertas_recompra.length">
                                    <td colspan="4" class="text-center text-muted py-3">Sem alertas no período.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-white"><strong>Estoque Baixo</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Atual</th>
                                    <th>Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in data.alertas_estoque_baixo" :key="p.id">
                                    <td>{{ p.nome }}</td>
                                    <td>{{ p.categoria }}</td>
                                    <td class="text-danger fw-semibold">{{ fmtInt(p.estoque_atual) }}</td>
                                    <td>{{ fmtInt(p.estoque_min) }}</td>
                                </tr>
                                <tr v-if="!data.alertas_estoque_baixo.length">
                                    <td colspan="4" class="text-center text-muted py-3">Nenhum produto em alerta.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-white"><strong>Faturamento por Perfil</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Perfil</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="linha in data.faturamento_por_perfil" :key="linha.perfil_pet_tipo">
                                    <td>{{ perfilLabel(linha.perfil_pet_tipo) }}</td>
                                    <td class="fw-semibold text-success">R$ {{ fmt(linha.total) }}</td>
                                </tr>
                                <tr v-if="!data.faturamento_por_perfil.length">
                                    <td colspan="2" class="text-center text-muted py-3">Sem dados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-white"><strong>Top Produtos por Receita</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Qtd</th>
                                    <th>Receita</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="linha in data.top_produtos_receita" :key="linha.produto_nome">
                                    <td>{{ linha.produto_nome }}</td>
                                    <td>{{ fmtQty(linha.quantidade) }}</td>
                                    <td class="fw-semibold text-success">R$ {{ fmt(linha.receita) }}</td>
                                </tr>
                                <tr v-if="!data.top_produtos_receita.length">
                                    <td colspan="3" class="text-center text-muted py-3">Sem dados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalError } from '../../utils/swal';

const hoje = new Date();
const inicioMesAtual = new Date(hoje.getFullYear(), hoje.getMonth(), 1).toISOString().slice(0, 10);
const fimMesAtual = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0).toISOString().slice(0, 10);

const loading = ref(false);
const filters = reactive({
    data_inicio: inicioMesAtual,
    data_fim: fimMesAtual,
});

const data = reactive({
    cards: {
        recompras_3_dias: 0,
        recompras_atrasadas: 0,
        estoque_baixo: 0,
        ticket_medio_cliente: 0,
        volume_racao_gramas: 0,
    },
    faturamento_por_perfil: [],
    top_produtos_receita: [],
    alertas_recompra: [],
    alertas_estoque_baixo: [],
});

const perfis = {
    cao_pequeno: 'Cão pequeno',
    cao_medio: 'Cão médio',
    cao_grande: 'Cão grande',
    gato: 'Gato',
    outros: 'Outros',
};

function perfilLabel(v) {
    return perfis[v] || v || 'Outros';
}

function fmt(v) {
    return Number(v || 0).toFixed(2).replace('.', ',');
}

function fmtQty(v) {
    return Number(v || 0).toFixed(3).replace('.', ',');
}

function fmtInt(v) {
    return Number(v || 0).toLocaleString('pt-BR');
}

function fmtDate(v) {
    if (!v) return '-';
    const raw = String(v);

    // Aceita tanto "YYYY-MM-DD" quanto datetime/ISO retornado pela API
    const d = raw.length <= 10
        ? new Date(raw + 'T12:00:00')
        : new Date(raw);

    return Number.isNaN(d.getTime()) ? '-' : d.toLocaleDateString('pt-BR');
}

function clienteNome(item) {
    return item?.cliente?.nome || item?.pet?.cliente?.nome || '-';
}

function clienteTelefone(item) {
    return item?.cliente?.telefone || item?.pet?.cliente?.telefone || '';
}

function telefoneParaWhatsapp(telefone) {
    const raw = String(telefone || '').trim();
    const digits = raw.replace(/\D/g, '');

    // Se vier número BR local (10/11 dígitos), prefixa com 55 para o link do WhatsApp
    if (digits.length === 10 || digits.length === 11) {
        return '55' + digits;
    }

    return digits;
}

function mensagemWhatsapp(item) {
    const cliente = clienteNome(item);
    const pet = item?.pet?.nome || 'seu pet';
    const produto = item?.produto?.nome || 'produto';
    const data = fmtDate(item?.data_proxima_compra_estimada);

    return `Oi, ${cliente}! Tudo bem? Aqui é da LUA PetShop. ` +
        `Percebemos que a reposição de ${produto} para ${pet} ficou em atraso desde ${data}. ` +
        `Se quiser, já deixamos separado para você. Posso te ajudar com isso?`;
}

function whatsappLink(item) {
    const telefone = clienteTelefone(item);
    const numero = telefoneParaWhatsapp(telefone);
    const texto = encodeURIComponent(mensagemWhatsapp(item));
    return `https://wa.me/${numero}?text=${texto}`;
}

function setMesAtual() {
    filters.data_inicio = inicioMesAtual;
    filters.data_fim = fimMesAtual;
    load();
}

async function load() {
    loading.value = true;
    try {
        const { data: resp } = await axios.get('/alertas-metricas', { params: filters });

        data.cards = resp.cards || data.cards;
        data.faturamento_por_perfil = resp.faturamento_por_perfil || [];
        data.top_produtos_receita = resp.top_produtos_receita || [];
        data.alertas_recompra = resp.alertas_recompra || [];
        data.alertas_estoque_baixo = resp.alertas_estoque_baixo || [];
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao carregar alertas e métricas.');
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>
