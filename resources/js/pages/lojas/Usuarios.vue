<template>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Usuarios vinculados - {{ loja?.nome }}</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th width="100">Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in usuarios" :key="u.id">
                                <td>{{ u.name }}</td>
                                <td>{{ u.email }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" @click="desvincular(u)">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="usuarios.length === 0">
                                <td colspan="3" class="text-center text-muted py-4">Nenhum usuario vinculado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4">
                <h6 class="mb-3">Vincular Usuario</h6>
                <div v-if="disponiveis.length > 0">
                    <div class="mb-3">
                        <select class="form-select" v-model="selectedUser">
                            <option value="">Selecione...</option>
                            <option v-for="u in disponiveis" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                    </div>
                    <button class="btn btn-lua w-100" @click="vincular" :disabled="!selectedUser">
                        <i class="bi bi-link-45deg"></i> Vincular
                    </button>
                </div>
                <p v-else class="text-muted small mb-0">Todos os usuarios ja estao vinculados.</p>
            </div>

            <router-link :to="{ name: 'lojas.index' }" class="btn btn-outline-secondary w-100 mt-3">
                <i class="bi bi-arrow-left"></i> Voltar para Lojas
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { swalSuccess, swalError, swalConfirmDanger } from '../../utils/swal';

const route = useRoute();
const lojaId = route.params.id;
const loja = ref(null);
const usuarios = ref([]);
const disponiveis = ref([]);
const selectedUser = ref('');

async function load() {
    const { data } = await axios.get('/lojas/' + lojaId + '/usuarios');
    loja.value = data.loja;
    usuarios.value = data.usuarios;
    disponiveis.value = data.disponiveis;
}

async function vincular() {
    try {
        await axios.post('/lojas/' + lojaId + '/vincular-usuario', { user_id: selectedUser.value });
        swalSuccess('Usuario vinculado com sucesso.');
        selectedUser.value = '';
        load();
    } catch {
        swalError('Erro ao vincular usuario.');
    }
}

async function desvincular(u) {
    if (!(await swalConfirmDanger('Desvincular usuario?', 'Deseja desvincular este usuario?'))) return;
    try {
        await axios.delete('/lojas/' + lojaId + '/desvincular-usuario/' + u.id);
        swalSuccess('Usuario desvinculado com sucesso.');
        load();
    } catch {
        swalError('Erro ao desvincular usuario.');
    }
}

onMounted(load);
</script>
