<template>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="text-muted">{{ bancos.length }}/5 bancos cadastrados</span>
        <router-link v-if="bancos.length < 5" :to="{ name: 'bancos.create' }" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Novo Banco
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
                    <tr v-for="banco in bancos" :key="banco.id">
                        <td class="fw-semibold">{{ banco.nome }}</td>
                        <td>
                            <span class="badge" :class="banco.ativo ? 'badge-ativo' : 'badge-inativo'">
                                {{ banco.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <router-link :to="{ name: 'bancos.edit', params: { id: banco.id } }" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </router-link>
                            <button class="btn btn-sm btn-outline-danger" @click="destroy(banco)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="bancos.length === 0">
                        <td colspan="3" class="text-center text-muted py-4">Nenhum banco cadastrado.</td>
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

const bancos = ref([]);

async function load() {
    const { data } = await axios.get('/bancos');
    bancos.value = data;
}

async function destroy(banco) {
    if (!(await swalConfirmDanger('Remover banco?', 'Tem certeza que deseja remover este banco?'))) return;
    try {
        await axios.delete('/bancos/' + banco.id);
        swalSuccess('Banco removido com sucesso.');
        load();
    } catch {
        swalError('Erro ao remover banco.');
    }
}

onMounted(load);
</script>
