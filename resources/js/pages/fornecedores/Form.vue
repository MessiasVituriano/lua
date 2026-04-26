<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" :class="{ 'is-invalid': errors.nome }" id="nome" v-model="form.nome" required>
                        <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoria *</label>
                        <select class="form-select" :class="{ 'is-invalid': errors.categoria }" id="categoria" v-model="form.categoria" required>
                            <option value="">Selecione...</option>
                            <option v-for="(label, key) in categorias" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <div v-if="errors.categoria" class="invalid-feedback">{{ errors.categoria }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" v-model="form.telefone">
                    </div>

                    <div v-if="isEdit" class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" v-model="form.ativo">
                            <label class="form-check-label" for="ativo">Fornecedor ativo</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'fornecedores.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { swalSuccess } from '../../utils/swal';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const errors = reactive({});
const isEdit = computed(() => !!route.params.id);
const categorias = { racao: 'Ração', medicamento: 'Medicamento', acessorio: 'Acessório', higiene: 'Higiene', petisco: 'Petisco', outros: 'Outros' };
const form = reactive({ nome: '', categoria: '', telefone: '', ativo: true });

onMounted(async () => {
    if (isEdit.value) {
        const { data } = await axios.get('/fornecedores/' + route.params.id);
        Object.assign(form, data);
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        if (isEdit.value) {
            await axios.put('/fornecedores/' + route.params.id, form);
            swalSuccess('Fornecedor atualizado com sucesso.');
        } else {
            await axios.post('/fornecedores', form);
            swalSuccess('Fornecedor criado com sucesso.');
        }
        router.push({ name: 'fornecedores.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        }
    } finally {
        loading.value = false;
    }
}
</script>
