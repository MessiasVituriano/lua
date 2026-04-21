<template>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="text-muted">{{ planos.length }} plano(s) — 1 ativo por loja</span>
        <router-link :to="{ name: 'planos-maquininha.create' }" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Novo Plano
        </router-link>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Taxa Antecipacao</th>
                        <th>Status</th>
                        <th width="160">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in planos" :key="p.id">
                        <td class="fw-semibold">{{ p.nome }}</td>
                        <td>{{ p.taxa_antecipacao !== null ? fmtPct(p.taxa_antecipacao) : '—' }}</td>
                        <td>
                            <span class="badge" :class="p.ativo ? 'badge-ativo' : 'badge-inativo'">
                                {{ p.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <router-link :to="{ name: 'planos-maquininha.edit', params: { id: p.id } }" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </router-link>
                            <button class="btn btn-sm btn-outline-danger" @click="destroy(p)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="planos.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">Nenhum plano cadastrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { swalSuccess, swalError, swalConfirmDanger } from '../../utils/swal';

const planos = ref([]);

async function load() {
    const { data } = await axios.get('/planos-maquininha');
    planos.value = data;
}

function fmtPct(v) {
    return Number(v).toFixed(2).replace('.', ',') + ' %';
}

async function destroy(p) {
    if (!(await swalConfirmDanger('Remover plano?', 'As taxas cadastradas serao perdidas.'))) return;
    try {
        await axios.delete('/planos-maquininha/' + p.id);
        swalSuccess('Plano removido com sucesso.');
        load();
    } catch {
        swalError('Erro ao remover plano.');
    }
}

onMounted(load);
</script>
