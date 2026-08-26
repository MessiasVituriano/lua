<template>
    <div>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link" :class="{ active: tab === 'produtos' }" @click="tab = 'produtos'">
                    <i class="bi bi-box-seam me-1"></i> Produtos
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: tab === 'fornecedores' }" @click="tab = 'fornecedores'">
                    <i class="bi bi-truck me-1"></i> Fornecedores
                </button>
            </li>
        </ul>

        <!-- v-show em vez de v-if: cada aba guarda os proprios filtros ao alternar -->
        <ProdutosTab
            v-show="tab === 'produtos'"
            ref="produtosTab"
            :fornecedores="fornecedores"
            @detalhe="abrirDetalhe"
            @editar="abrirEdicaoProduto"
            @novo="abrirNovoProduto"
            @changed="recarregarTudo" />

        <FornecedoresTab
            v-show="tab === 'fornecedores'"
            ref="fornecedoresTab"
            @detalhe="abrirDetalhe"
            @editar="abrirEdicaoProduto"
            @novo-produto="abrirNovoProduto"
            @novo-fornecedor="abrirNovoFornecedor"
            @editar-fornecedor="abrirEdicaoFornecedor"
            @changed="recarregarTudo" />

        <ProdutoDetalheModal
            v-model="detalheAberto"
            :produto-id="produtoSelecionado"
            @editar="trocarParaEdicao"
            @changed="recarregarTudo" />

        <ProdutoFormModal
            v-model="formProdutoAberto"
            :produto-id="produtoSelecionado"
            :fornecedores="fornecedores"
            :fornecedor-padrao="fornecedorPadrao"
            @saved="onProdutoSalvo" />

        <FornecedorFormModal
            v-model="formFornecedorAberto"
            :fornecedor-id="fornecedorSelecionado"
            @saved="onFornecedorSalvo" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ProdutosTab from './ProdutosTab.vue';
import FornecedoresTab from './FornecedoresTab.vue';
import ProdutoDetalheModal from './ProdutoDetalheModal.vue';
import ProdutoFormModal from './ProdutoFormModal.vue';
import FornecedorFormModal from './FornecedorFormModal.vue';

const tab = ref('produtos');
const produtosTab = ref(null);
const fornecedoresTab = ref(null);

// Lista usada no select de fornecedor do form de produto e no filtro da aba Produtos.
const fornecedores = ref([]);

const detalheAberto = ref(false);
const formProdutoAberto = ref(false);
const formFornecedorAberto = ref(false);
const produtoSelecionado = ref(null);
const fornecedorSelecionado = ref(null);
const fornecedorPadrao = ref(null);

async function carregarFornecedores() {
    const { data } = await axios.get('/fornecedores', { params: { ativo: '1', per_page: 200 } });
    fornecedores.value = data.data ?? data;
}

function abrirDetalhe(id) {
    produtoSelecionado.value = id;
    detalheAberto.value = true;
}

function abrirEdicaoProduto(id) {
    produtoSelecionado.value = id;
    fornecedorPadrao.value = null;
    formProdutoAberto.value = true;
}

function abrirNovoProduto(fornecedorId = null) {
    produtoSelecionado.value = null;
    fornecedorPadrao.value = fornecedorId;
    formProdutoAberto.value = true;
}

function abrirNovoFornecedor() {
    fornecedorSelecionado.value = null;
    formFornecedorAberto.value = true;
}

function abrirEdicaoFornecedor(id) {
    fornecedorSelecionado.value = id;
    formFornecedorAberto.value = true;
}

// Do detalhe para a edicao: fecha um modal antes de abrir o outro.
function trocarParaEdicao(id) {
    detalheAberto.value = false;
    setTimeout(() => abrirEdicaoProduto(id), 200);
}

function recarregarTudo() {
    produtosTab.value?.load();
    fornecedoresTab.value?.load();
}

function onProdutoSalvo() {
    recarregarTudo();
}

async function onFornecedorSalvo() {
    await carregarFornecedores();
    recarregarTudo();
}

onMounted(carregarFornecedores);
</script>
