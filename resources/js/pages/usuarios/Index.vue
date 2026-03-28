<template>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div></div>
        <router-link :to="{ name: 'usuarios.create' }" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Novo Usuario
        </router-link>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Loja Ativa</th>
                        <th>Status</th>
                        <th width="160">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in usuarios" :key="u.id">
                        <td class="fw-semibold">{{ u.name }}</td>
                        <td>{{ u.email }}</td>
                        <td>
                            <span class="badge" :class="u.role === 'admin' ? 'bg-primary' : 'bg-info'">
                                {{ u.role === 'admin' ? 'Admin' : 'Atendente' }}
                            </span>
                        </td>
                        <td>{{ u.loja_ativa?.nome || '-' }}</td>
                        <td>
                            <span class="badge" :class="u.ativo ? 'badge-ativo' : 'badge-inativo'">
                                {{ u.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <router-link :to="{ name: 'usuarios.edit', params: { id: u.id } }" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </router-link>
                            <button v-if="u.id !== currentUserId" class="btn btn-sm btn-outline-danger" @click="destroy(u)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="usuarios.length === 0">
                        <td colspan="6" class="text-center text-muted py-4">Nenhum usuario cadastrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalConfirmDanger } from '../../utils/swal';

const auth = useAuthStore();
const usuarios = ref([]);
const currentUserId = computed(() => auth.user?.id);

async function load() {
    const { data } = await axios.get('/usuarios');
    usuarios.value = data.data;
}

async function destroy(u) {
    if (!(await swalConfirmDanger('Remover usuario?', 'Tem certeza que deseja remover este usuario?'))) return;
    try {
        await axios.delete('/usuarios/' + u.id);
        swalSuccess('Usuario removido com sucesso.');
        load();
    } catch {
        swalError('Erro ao remover usuario.');
    }
}

onMounted(load);
</script>
