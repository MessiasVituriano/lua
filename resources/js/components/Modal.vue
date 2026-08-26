<template>
    <div class="modal fade" tabindex="-1" ref="el">
        <div class="modal-dialog" :class="[sizeClass, scrollable ? 'modal-dialog-scrollable' : '']">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>
                <div class="modal-body">
                    <slot v-if="modelValue" />
                </div>
                <div v-if="$slots.footer" class="modal-footer">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: '' },        // '', 'lg', 'xl'
    scrollable: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const el = ref(null);
let instance = null;
const sizeClass = computed(() => (props.size ? `modal-${props.size}` : ''));

function onHidden() { emit('update:modelValue', false); }
function close() { emit('update:modelValue', false); }

onMounted(() => {
    instance = new window.bootstrap.Modal(el.value);
    el.value.addEventListener('hidden.bs.modal', onHidden);
    if (props.modelValue) instance.show();
});

onBeforeUnmount(() => {
    el.value?.removeEventListener('hidden.bs.modal', onHidden);
    const estavaAberto = props.modelValue;
    instance?.dispose();
    instance = null;

    // Sair da rota com o modal aberto deixaria o backdrop e o scroll-lock presos na tela.
    if (estavaAberto) {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('overflow');
    }
});

watch(() => props.modelValue, async (v) => {
    await nextTick();
    if (!instance) return;
    v ? instance.show() : instance.hide();
});
</script>
