import { ref, reactive, computed, watch } from 'vue';
import axios from 'axios';
import { swalSuccess, swalError } from '../utils/swal';
import { isRacao } from '../utils/estoque';

/**
 * Recebimento de mercadoria: acumula as quantidades digitadas linha a linha,
 * pede confirmacao e lanca todas em uma unica requisicao transacional.
 *
 * @param {() => Array} getProdutos  produtos atualmente visiveis na lista
 * @param {() => void}  onSalvo      chamado apos gravar com sucesso
 */
export function useRecebimento(getProdutos, onSalvo) {
    const recebido = reactive({});
    const motivo = ref('');
    const salvando = ref(false);
    const confirmando = ref(false);

    // Guarda todo produto que ja passou pela tela: trocar de pagina, refiltrar ou
    // recolher um fornecedor nao pode descartar uma quantidade ja digitada.
    const conhecidos = new Map();
    watch(getProdutos, (lista) => {
        (lista || []).forEach(p => conhecidos.set(String(p.id), p));
    }, { immediate: true });

    /** Linhas com quantidade valida, ja convertidas para a unidade do banco (gramas na ração). */
    const itens = computed(() =>
        Object.entries(recebido)
            .map(([id, valor]) => {
                const produto = conhecidos.get(String(id));
                const bruto = parseFloat(valor);
                if (!produto || !bruto || bruto <= 0) return null;
                const quantidade = isRacao(produto) ? Math.round(bruto * 1000) : Math.round(bruto);
                return quantidade >= 1 ? { produto, quantidade } : null;
            })
            .filter(Boolean)
    );

    function limpar() {
        Object.keys(recebido).forEach(k => delete recebido[k]);
        motivo.value = '';
    }

    /** Tira uma linha do lote direto da tela de confirmacao. */
    function remover(produtoId) {
        delete recebido[produtoId];
        if (itens.value.length === 0) confirmando.value = false;
    }

    function pedirConfirmacao() {
        if (itens.value.length === 0 || salvando.value) return;
        confirmando.value = true;
    }

    async function confirmar() {
        if (itens.value.length === 0 || salvando.value) return;
        salvando.value = true;
        try {
            const { data } = await axios.post('/produtos-movimentacoes-lote', {
                tipo: 'entrada',
                motivo: motivo.value || null,
                itens: itens.value.map(({ produto, quantidade }) => ({
                    produto_id: produto.id,
                    quantidade,
                })),
            });
            confirmando.value = false;
            limpar();
            swalSuccess(`${data.total} ${data.total === 1 ? 'entrada registrada' : 'entradas registradas'}.`);
            onSalvo?.();
        } catch (e) {
            swalError(e.response?.data?.message || 'Erro ao registrar as entradas.');
        } finally {
            salvando.value = false;
        }
    }

    return { recebido, motivo, salvando, confirmando, itens, limpar, remover, pedirConfirmacao, confirmar };
}
