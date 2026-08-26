<template>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="input-group input-group-sm" style="max-width: 280px">
            <span class="input-group-text"><i class="bi bi-receipt"></i></span>
            <input type="text" class="form-control" :value="motivo" @input="emit('update:motivo', $event.target.value)"
                placeholder="Nota fiscal / motivo (opcional)">
        </div>
        <button class="btn btn-sm btn-lua" :disabled="total === 0 || salvando" @click="emit('registrar')">
            <span v-if="salvando" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-box-arrow-in-down"></i>
            Registrar {{ total || '' }} {{ total === 1 ? 'entrada' : 'entradas' }}
        </button>
        <button v-if="total" class="btn btn-sm btn-outline-secondary" :disabled="salvando" @click="emit('limpar')">
            Limpar
        </button>
        <small v-if="total === 0" class="text-muted">
            Informe as quantidades recebidas e registre tudo de uma vez.
        </small>
    </div>
</template>

<script setup>
defineProps({
    motivo: { type: String, default: '' },
    total: { type: Number, default: 0 },
    salvando: { type: Boolean, default: false },
});
const emit = defineEmits(['update:motivo', 'registrar', 'limpar']);
</script>
