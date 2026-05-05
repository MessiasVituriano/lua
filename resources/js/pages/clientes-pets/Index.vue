<template>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h6 class="mb-0 d-flex align-items-center gap-2 section-title">
            <i class="bi bi-people"></i>
            <span>Gestão de Clientes & Pets</span>
        </h6>
        <router-link :to="{ name: 'clientes-pets.create' }" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Novo Pet
        </router-link>
    </div>

    <div class="card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small"><i class="bi bi-search me-1"></i>Busca</label>
                <input type="text" class="form-control form-control-sm" v-model="filters.busca" placeholder="Cliente, telefone, pet ou raça" @keyup.enter="load">
            </div>
            <div class="col-md-2">
                <label class="form-label small"><i class="bi bi-heart me-1"></i>Tipo</label>
                <select class="form-select form-select-sm" v-model="filters.tipo">
                    <option value="">Todos</option>
                    <option value="cao">Cão</option>
                    <option value="gato">Gato</option>
                    <option value="outros">Outros</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small"><i class="bi bi-rulers me-1"></i>Porte</label>
                <select class="form-select form-select-sm" v-model="filters.porte">
                    <option value="">Todos</option>
                    <option value="pequeno">Pequeno</option>
                    <option value="medio">Médio</option>
                    <option value="grande">Grande</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small"><i class="bi bi-toggle2-on me-1"></i>Status</label>
                <select class="form-select form-select-sm" v-model="filters.ativo">
                    <option value="">Todos</option>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-lua" @click="load">
                    <i class="bi bi-search"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" @click="clearFilters">
                    <i class="bi bi-eraser me-1"></i>Limpar
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Pet</th>
                        <th>Tipo</th>
                        <th>Porte</th>
                        <th>Raça</th>
                        <th>Idade (meses)</th>
                        <th>Status</th>
                        <th width="150">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="pet in pets" :key="pet.id">
                        <td class="fw-semibold">{{ pet.cliente?.nome || '-' }}</td>
                        <td>{{ pet.cliente?.telefone || '-' }}</td>
                        <td>{{ pet.nome }}</td>
                        <td>{{ tipoLabel(pet.tipo) }}</td>
                        <td>{{ porteLabel(pet.porte) }}</td>
                        <td>{{ pet.raca || '-' }}</td>
                        <td>{{ pet.idade_meses ?? '-' }}</td>
                        <td>
                            <span class="badge" :class="pet.ativo ? 'badge-ativo' : 'badge-inativo'">
                                {{ pet.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <router-link :to="{ name: 'clientes-pets.edit', params: { id: pet.id } }" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </router-link>
                            <button class="btn btn-sm btn-outline-danger" @click="destroyPet(pet)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="pets.length === 0">
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox me-1"></i>Nenhum cadastro encontrado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalConfirmDanger, swalError, swalSuccess } from '../../utils/swal';

const pets = ref([]);
const filters = reactive({
    busca: '',
    tipo: '',
    porte: '',
    ativo: '',
});

async function load() {
    const params = {};
    Object.entries(filters).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) params[k] = v;
    });

    const { data } = await axios.get('/clientes-pets', { params });
    pets.value = data.data || [];
}

function clearFilters() {
    filters.busca = '';
    filters.tipo = '';
    filters.porte = '';
    filters.ativo = '';
    load();
}

function tipoLabel(v) {
    return { cao: 'Cão', gato: 'Gato', outros: 'Outros' }[v] || '-';
}

function porteLabel(v) {
    return { pequeno: 'Pequeno', medio: 'Médio', grande: 'Grande' }[v] || '-';
}

async function destroyPet(pet) {
    if (!(await swalConfirmDanger('Remover pet?', 'Essa ação remove o cadastro do pet.'))) return;
    try {
        await axios.delete('/clientes-pets/' + pet.id);
        swalSuccess('Pet removido com sucesso.');
        load();
    } catch {
        swalError('Erro ao remover pet.');
    }
}

onMounted(load);
</script>

<style scoped>
.section-title {
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    background: #f3f7ff;
    color: #1f3f73;
    border: 1px solid #dbe8ff;
    width: fit-content;
}
</style>
