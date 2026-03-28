<template>
    <div v-if="fornecedor" class="row">
        <div class="col-md-4">
            <div class="card p-4">
                <h6 class="text-muted mb-3">Dados do Fornecedor</h6>

                <div class="mb-2">
                    <span class="text-muted small">Nome</span>
                    <div class="fw-semibold">{{ fornecedor.nome }}</div>
                </div>

                <div class="mb-2">
                    <span class="text-muted small">Categoria</span>
                    <div><span class="badge bg-secondary">{{ categorias[fornecedor.categoria] }}</span></div>
                </div>

                <div class="mb-2">
                    <span class="text-muted small">Telefone</span>
                    <div>{{ fornecedor.telefone || '-' }}</div>
                </div>

                <div class="mb-3">
                    <span class="text-muted small">Status</span>
                    <div>
                        <span class="badge" :class="fornecedor.ativo ? 'badge-ativo' : 'badge-inativo'">
                            {{ fornecedor.ativo ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <router-link :to="{ name: 'fornecedores.edit', params: { id: fornecedor.id } }" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </router-link>
                    <router-link :to="{ name: 'fornecedores.index' }" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </router-link>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card p-4">
                <h6 class="text-muted mb-3">Historico de Pagamentos</h6>
                <p class="text-muted small mb-0">O historico de pagamentos estara disponivel apos a implementacao do modulo de pagamentos.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const fornecedor = ref(null);
const categorias = { racao: 'Ração', medicamento: 'Medicamento', acessorio: 'Acessório', higiene: 'Higiene', outros: 'Outros' };

onMounted(async () => {
    const { data } = await axios.get('/fornecedores/' + route.params.id);
    fornecedor.value = data;
});
</script>
