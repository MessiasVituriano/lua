<template>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div></div>
        <router-link :to="{ name: 'lojas.create' }" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Nova Loja
        </router-link>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Endereco</th>
                        <th>Telefone</th>
                        <th>Usuarios</th>
                        <th>Status</th>
                        <th width="180">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="loja in lojas" :key="loja.id">
                        <td class="fw-semibold">{{ loja.nome }}</td>
                        <td>{{ loja.endereco || '-' }}</td>
                        <td>{{ loja.telefone || '-' }}</td>
                        <td>
                            <router-link :to="{ name: 'lojas.usuarios', params: { id: loja.id } }" class="text-decoration-none">
                                {{ loja.usuarios_count }} <i class="bi bi-people"></i>
                            </router-link>
                        </td>
                        <td>
                            <span class="badge" :class="loja.ativa ? 'badge-ativo' : 'badge-inativo'">
                                {{ loja.ativa ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td>
                            <router-link :to="{ name: 'lojas.edit', params: { id: loja.id } }" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </router-link>
                            <button class="btn btn-sm btn-outline-danger" @click="destroy(loja)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="lojas.length === 0">
                        <td colspan="6" class="text-center text-muted py-4">Nenhuma loja cadastrada.</td>
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

const lojas = ref([]);

async function load() {
    const { data } = await axios.get('/lojas');
    lojas.value = data.data;
}

async function destroy(loja) {
    if (!(await swalConfirmDanger('Remover loja?', 'Tem certeza que deseja remover esta loja?'))) return;
    try {
        await axios.delete('/lojas/' + loja.id);
        swalSuccess('Loja removida com sucesso.');
        load();
    } catch {
        swalError('Erro ao remover loja.');
    }
}

onMounted(load);
</script>
