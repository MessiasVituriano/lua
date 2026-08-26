<template>
    <Modal v-model="open" :title="isEdit ? 'Editar Fornecedor' : 'Novo Fornecedor'">
        <form @submit.prevent="save" id="formFornecedorModal">
            <div class="mb-3">
                <label class="form-label">Nome *</label>
                <input type="text" class="form-control" :class="{ 'is-invalid': errors.nome }" v-model="form.nome" required>
                <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Categoria *</label>
                <select class="form-select" :class="{ 'is-invalid': errors.categoria }" v-model="form.categoria" required>
                    <option value="">Selecione...</option>
                    <option v-for="(label, key) in categorias" :key="key" :value="key">{{ label }}</option>
                </select>
                <div v-if="errors.categoria" class="invalid-feedback">{{ errors.categoria }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Telefone</label>
                <input type="text" class="form-control" v-model="form.telefone">
            </div>

            <div v-if="isEdit">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="fornecedorAtivoModal" v-model="form.ativo">
                    <label class="form-check-label" for="fornecedorAtivoModal">Fornecedor ativo</label>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-outline-secondary" @click="open = false">Cancelar</button>
            <button type="submit" form="formFornecedorModal" class="btn btn-lua" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import axios from 'axios';
import Modal from '../../components/Modal.vue';
import { swalSuccess, swalError } from '../../utils/swal';
import { CATEGORIAS_FORNECEDOR } from '../../utils/estoque';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    fornecedorId: { type: [Number, String, null], default: null },
});
const emit = defineEmits(['update:modelValue', 'saved']);

const open = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});

const categorias = CATEGORIAS_FORNECEDOR;
const loading = ref(false);
const errors = reactive({});
const isEdit = computed(() => !!props.fornecedorId);
const form = reactive({ nome: '', categoria: '', telefone: '', ativo: true });

watch(() => props.modelValue, async (v) => {
    if (!v) return;
    Object.keys(errors).forEach(k => delete errors[k]);
    Object.assign(form, { nome: '', categoria: '', telefone: '', ativo: true });
    if (isEdit.value) {
        const { data } = await axios.get('/fornecedores/' + props.fornecedorId);
        Object.assign(form, { nome: data.nome, categoria: data.categoria, telefone: data.telefone, ativo: data.ativo });
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        if (isEdit.value) {
            await axios.put('/fornecedores/' + props.fornecedorId, form);
            swalSuccess('Fornecedor atualizado com sucesso.');
        } else {
            await axios.post('/fornecedores', form);
            swalSuccess('Fornecedor criado com sucesso.');
        }
        open.value = false;
        emit('saved');
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        } else {
            swalError('Erro ao salvar fornecedor.');
        }
    } finally {
        loading.value = false;
    }
}
</script>
