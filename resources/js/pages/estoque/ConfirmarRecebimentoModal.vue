<template>
    <Modal v-model="open" title="Confirmar recebimento" size="lg" scrollable>
        <div class="mb-3">
            <label class="form-label small">Nota fiscal / motivo</label>
            <input
                type="text"
                class="form-control form-control-sm"
                :value="motivo"
                @input="emit('update:motivo', $event.target.value)"
                placeholder="Opcional — ex: NF 12345">
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>Produto</th>
                        <th class="text-end">Recebido</th>
                        <th class="text-end">Estoque atual</th>
                        <th class="text-end">Fica com</th>
                        <th width="40"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="{ produto, quantidade } in itens" :key="produto.id">
                        <td class="fw-semibold">{{ produto.nome }}</td>
                        <td class="text-end text-success fw-bold">+{{ fmtQtd(produto, quantidade) }}</td>
                        <td class="text-end text-muted">{{ fmtQtd(produto, produto.estoque_atual) }}</td>
                        <td class="text-end fw-bold">{{ fmtQtd(produto, produto.estoque_atual + quantidade) }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger border-0" @click="emit('remover', produto.id)" title="Tirar do lançamento">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-muted small mb-0 mt-3">
            <i class="bi bi-info-circle"></i>
            {{ itens.length }} {{ itens.length === 1 ? 'produto' : 'produtos' }} —
            o lançamento é feito de uma vez só: ou tudo entra, ou nada entra.
        </p>

        <template #footer>
            <button type="button" class="btn btn-outline-secondary" :disabled="salvando" @click="open = false">
                Voltar e editar
            </button>
            <button type="button" class="btn btn-lua" :disabled="salvando || itens.length === 0" @click="emit('confirmar')">
                <span v-if="salvando" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-check-lg"></i>
                Confirmar entrada
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { computed } from 'vue';
import Modal from '../../components/Modal.vue';
import { fmtQtd } from '../../utils/estoque';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    itens: { type: Array, default: () => [] },
    motivo: { type: String, default: '' },
    salvando: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'update:motivo', 'confirmar', 'remover']);

const open = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});
</script>
