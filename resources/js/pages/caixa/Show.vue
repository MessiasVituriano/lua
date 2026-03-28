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
            <div class="card-header bg-white">
                <h6 class="mb-0">Entradas</h6>
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const caixa = ref(null);
const totaisPorForma = ref({});
const formas = { dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartão Débito', cartao_credito: 'Cartão Crédito' };

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }

onMounted(async () => {
    const { data } = await axios.get('/caixa/' + route.params.id);
    caixa.value = data.caixa;
    totaisPorForma.value = data.totais_por_forma || {};
});
</script>
