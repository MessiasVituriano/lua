<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Descricao *</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': errors.descricao }" v-model="form.descricao" required>
                            <div v-if="errors.descricao" class="invalid-feedback">{{ errors.descricao }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoria *</label>
                            <select class="form-select" v-model="form.categoria" required>
                                <option value="">Selecione...</option>
                                <option v-for="(l, k) in categorias" :key="k" :value="k">{{ l }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valor Total *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.valor_total" required>
                        </div>
                        <div class="col-md-4" v-if="form.quantidade_parcelas === 1">
                            <label class="form-label">Data Vencimento *</label>
                            <input type="date" class="form-control" v-model="form.data_vencimento" required>
                        </div>
                        <div class="col-md-4" v-if="!isEdit">
                            <label class="form-label">Quantidade de Parcelas *</label>
                            <select class="form-select" v-model.number="form.quantidade_parcelas">
                                <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
                            </select>
                        </div>
                        <div class="col-md-4" v-if="!isEdit && form.quantidade_parcelas > 1">
                            <label class="form-label">Data do Primeiro Pagamento *</label>
                            <input type="date" class="form-control" v-model="form.data_primeiro_pagamento" required>
                        </div>
                        <div class="col-md-4" v-if="!isEdit && form.quantidade_parcelas > 1">
                            <label class="form-label">Recorrencia (dias) *</label>
                            <input type="number" min="1" class="form-control" v-model.number="form.recorrencia_dias" list="recorrencias-padrao" required>
                            <datalist id="recorrencias-padrao">
                                <option value="7">7 dias</option>
                                <option value="15">15 dias</option>
                                <option value="30">30 dias</option>
                            </datalist>
                        </div>
                        <div class="col-md-4" :class="{ 'mt-md-4': !isEdit && form.quantidade_parcelas > 1 }">
                            <label class="form-label">Fornecedor</label>
                            <select class="form-select" v-model="form.fornecedor_id">
                                <option value="">Nenhum</option>
                                <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
                            </select>
                        </div>

                        <div class="col-12" v-if="!isEdit && form.quantidade_parcelas > 1">
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                                    <div class="fw-semibold">Parcelas do cadastro em lote</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="redistribuirValoresParcelas">
                                        Redistribuir valores
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 120px;">Parcela</th>
                                                <th style="width: 180px;">Vencimento</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="p in parcelasLote" :key="p.numero">
                                                <td>{{ p.numero }}/{{ form.quantidade_parcelas }}</td>
                                                <td>
                                                    <input type="date" class="form-control form-control-sm" v-model="p.data_vencimento">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" v-model.number="p.valor_total">
                                                </td>
                                            </tr>
                                            <tr v-if="!parcelasLote.length">
                                                <td colspan="3" class="text-muted">Informe data do primeiro pagamento e recorrencia em dias para gerar as parcelas.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="small mt-2" :class="Math.abs(diferencaParcelas) > 0.009 ? 'text-warning' : 'text-muted'">
                                    Total das parcelas: R$ {{ totalParcelasLoteFmt }}
                                    <span v-if="Math.abs(diferencaParcelas) > 0.009">
                                        · Diferenca para o valor total: R$ {{ diferencaParcelasFmt }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observacao</label>
                            <textarea class="form-control" rows="2" v-model="form.observacao"></textarea>
                        </div>
                        <div class="col-md-4" v-if="form.quantidade_parcelas === 1 || isEdit">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="recorrente" v-model="form.recorrente">
                                <label class="form-check-label" for="recorrente">Pagamento recorrente</label>
                            </div>
                        </div>
                        <div class="col-md-4" v-if="(form.quantidade_parcelas === 1 || isEdit) && form.recorrente">
                            <label class="form-label">Dia do mes (recorrencia) *</label>
                            <input type="number" min="1" max="31" class="form-control" v-model="form.dia_recorrencia">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'pagamentos.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { swalSuccess } from '../../utils/swal';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const errors = reactive({});
const isEdit = computed(() => !!route.params.id);
const fornecedores = ref([]);
const categorias = { boleto: 'Boleto', imposto: 'Imposto', custo_fixo: 'Custo Fixo', funcionario: 'Funcionário', fornecedor: 'Fornecedor', pro_labore: 'Pró-labore', outros: 'Outros' };
const hojeStr = new Date().toISOString().slice(0, 10);

const form = reactive({
    descricao: '', categoria: '', valor_total: '', data_vencimento: hojeStr,
    fornecedor_id: '', observacao: '', recorrente: false, dia_recorrencia: '',
    quantidade_parcelas: 1, recorrencia_dias: 30, data_primeiro_pagamento: hojeStr,
});
const parcelasLote = ref([]);

function buildParcelasPadrao() {
    const quantidade = Number(form.quantidade_parcelas);
    const recorrencia = Number(form.recorrencia_dias);
    const valorTotal = Number(form.valor_total);
    const dataInicial = form.data_primeiro_pagamento;

    if (!quantidade || quantidade < 2 || !recorrencia || recorrencia < 1 || !valorTotal || valorTotal <= 0 || !dataInicial) {
        return [];
    }

    const baseCentavos = Math.floor((valorTotal * 100) / quantidade);
    let restoCentavos = Math.round(valorTotal * 100) - (baseCentavos * quantidade);
    const inicio = new Date(dataInicial + 'T00:00:00');
    const lista = [];

    for (let i = 0; i < quantidade; i++) {
        const dataParcela = new Date(inicio);
        dataParcela.setDate(dataParcela.getDate() + (i * recorrencia));

        let centavos = baseCentavos;
        if (restoCentavos > 0) {
            centavos += 1;
            restoCentavos -= 1;
        }

        const valorParcela = centavos / 100;

        lista.push({
            numero: i + 1,
            data_vencimento: dataParcela.toISOString().slice(0, 10),
            valor_total: valorParcela,
        });
    }

    return lista;
}

function distribuirValorEmParcelas(valorTotal, quantidade) {
    const totalCentavos = Math.round((Number(valorTotal) || 0) * 100);
    const baseCentavos = Math.floor(totalCentavos / quantidade);
    let restoCentavos = totalCentavos - (baseCentavos * quantidade);
    const valores = [];

    for (let i = 0; i < quantidade; i++) {
        let centavos = baseCentavos;
        if (restoCentavos > 0) {
            centavos += 1;
            restoCentavos -= 1;
        }
        valores.push(centavos / 100);
    }

    return valores;
}

function atualizarParcelasLote() {
    if (isEdit.value || Number(form.quantidade_parcelas) <= 1) {
        parcelasLote.value = [];
        return;
    }
    parcelasLote.value = buildParcelasPadrao();
}

function redistribuirValoresParcelas() {
    if (isEdit.value || Number(form.quantidade_parcelas) <= 1 || !parcelasLote.value.length) return;

    const quantidade = parcelasLote.value.length;
    const valores = distribuirValorEmParcelas(form.valor_total, quantidade);
    parcelasLote.value = parcelasLote.value.map((parcela, idx) => ({
        ...parcela,
        valor_total: valores[idx],
    }));
}

const totalParcelasLote = computed(() => {
    return parcelasLote.value.reduce((sum, p) => sum + (Number(p.valor_total) || 0), 0);
});

const diferencaParcelas = computed(() => {
    const total = Number(form.valor_total || 0);
    return Math.round((total - totalParcelasLote.value) * 100) / 100;
});

const totalParcelasLoteFmt = computed(() => totalParcelasLote.value.toFixed(2).replace('.', ','));
const diferencaParcelasFmt = computed(() => Math.abs(diferencaParcelas.value).toFixed(2).replace('.', ','));

watch(
    () => [form.quantidade_parcelas, form.recorrencia_dias, form.data_primeiro_pagamento],
    () => {
        atualizarParcelasLote();
    },
    { immediate: true }
);

watch(() => form.valor_total, () => {
    if (isEdit.value || Number(form.quantidade_parcelas) <= 1) return;
    if (!parcelasLote.value.length) {
        atualizarParcelasLote();
    }
});

watch(() => form.data_vencimento, (data) => {
    if (!isEdit.value && Number(form.quantidade_parcelas) === 1 && data) {
        form.data_primeiro_pagamento = data;
    }
});

watch(() => form.quantidade_parcelas, (qtd) => {
    if (isEdit.value) return;
    if (Number(qtd) <= 1) {
        form.recorrente = false;
        form.recorrencia_dias = 30;
        form.data_primeiro_pagamento = form.data_vencimento || hojeStr;
        parcelasLote.value = [];
    } else if (!form.data_primeiro_pagamento) {
        form.data_primeiro_pagamento = form.data_vencimento || hojeStr;
    }
});

onMounted(async () => {
    const { data } = await axios.get('/fornecedores?ativo=1');
    fornecedores.value = data.data;

    if (isEdit.value) {
        const { data: pg } = await axios.get('/pagamentos/' + route.params.id);
        Object.keys(form).forEach(k => { if (pg[k] !== null && pg[k] !== undefined) form[k] = pg[k]; });
        if (pg.data_vencimento) form.data_vencimento = pg.data_vencimento.slice(0, 10);
        form.quantidade_parcelas = 1;
        form.recorrencia_dias = 30;
        form.data_primeiro_pagamento = form.data_vencimento || hojeStr;
        parcelasLote.value = [];
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        const payload = { ...form };
        if (!payload.fornecedor_id) payload.fornecedor_id = null;

        if (isEdit.value) {
            payload.quantidade_parcelas = 1;
            payload.recorrencia_dias = null;
            payload.data_primeiro_pagamento = null;
        } else {
            payload.quantidade_parcelas = Number(payload.quantidade_parcelas || 1);
            if (payload.quantidade_parcelas > 1) {
                payload.recorrente = false;
                payload.dia_recorrencia = null;
                payload.data_primeiro_pagamento = payload.data_primeiro_pagamento || payload.data_vencimento || hojeStr;
                payload.data_vencimento = payload.data_primeiro_pagamento;
                payload.recorrencia_dias = Number(payload.recorrencia_dias || 30);
                payload.parcelas_lote = parcelasLote.value.map((p, idx) => ({
                    numero: idx + 1,
                    data_vencimento: p.data_vencimento,
                    valor_total: Number(p.valor_total || 0),
                }));
            } else {
                payload.recorrencia_dias = null;
                payload.data_primeiro_pagamento = null;
                payload.parcelas_lote = null;
                if (!payload.recorrente) payload.dia_recorrencia = null;
            }
        }

        if (isEdit.value) {
            await axios.put('/pagamentos/' + route.params.id, payload);
            swalSuccess('Pagamento atualizado.');
        } else {
            const { data } = await axios.post('/pagamentos', payload);
            if (data?.parcelado && data?.total_parcelas) {
                swalSuccess(`Pagamento criado em ${data.total_parcelas} parcelas.`);
            } else {
                swalSuccess('Pagamento criado.');
            }
        }
        router.push({ name: 'pagamentos.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        }
    } finally { loading.value = false; }
}
</script>
