<template>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="text-muted">{{ bandeiras.length }} bandeira(s)</span>
        <router-link :to="{ name: 'bandeiras.create' }" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Nova Bandeira
        </router-link>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th width="160">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in bandeiras" :key="b.id">
                        <td class="fw-semibold">{{ b.nome }}</td>
                        <td>
                            <span class="badge" :class="b.ativo ? 'badge-ativo' : 'badge-inativo'">
                                {{ b.ativo ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td>
                            <router-link :to="{ name: 'bandeiras.edit', params: { id: b.id } }" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </router-link>
                            <button class="btn btn-sm btn-outline-danger" @click="destroy(b)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="bandeiras.length === 0">
                        <td colspan="3" class="text-center text-muted py-4">Nenhuma bandeira cadastrada.</td>
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

const bandeiras = ref([]);

async function load() {
    const { data } = await axios.get('/bandeiras');
    bandeiras.value = data;
}

async function destroy(b) {
    if (!(await swalConfirmDanger('Remover bandeira?', 'As taxas associadas tambem serao removidas.'))) return;
    try {
        await axios.delete('/bandeiras/' + b.id);
        swalSuccess('Bandeira removida com sucesso.');
        load();
    } catch {
        swalError('Erro ao remover bandeira.');
    }
}

onMounted(load);
</script>
