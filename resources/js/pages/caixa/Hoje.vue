<template>
    <div>
        <!-- Caixa nao aberto -->
        <div v-if="!caixa" class="text-center py-5">
            <div class="card p-5 d-inline-block">
                <i class="bi bi-cash-register fs-1 text-muted"></i>
                <h5 class="mt-3">Caixa de Hoje</h5>
                <p class="text-muted">{{ dataHoje }}</p>
                <button class="btn btn-lua btn-lg" @click="abrirCaixa" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-unlock-fill"></i> Abrir Caixa
                </button>
            </div>
        </div>

        <!-- Caixa aberto -->
        <div v-else>
            <!-- Status bar -->
            <div v-if="caixa.status !== 'fechado'" class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    <i class="bi bi-circle-fill" :class="caixa.status === 'aberto' ? 'text-success' : 'text-warning'" style="font-size:0.5rem"></i>
                    {{ caixa.status === 'aberto' ? 'Caixa aberto' : 'Aguardando aprovacao' }} — sincroniza a cada 15s
                    <span v-if="lastSync" class="ms-1">({{ lastSync }})</span>
                </small>
                <button class="btn btn-sm btn-outline-secondary" @click="load" title="Atualizar agora">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>

            <!-- Resumo -->
            <div v-if="userIsAdmin" class="row g-3 mb-4">
                <div class="col-6 col-lg-4">
                    <div class="card p-3 border-start border-success border-4">
                        <div class="text-muted small">Total Entradas</div>
                        <div class="fs-4 fw-bold text-success">R$ {{ fmt(totalEntradas) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="card p-3 border-start border-danger border-4">
                        <div class="text-muted small">Total Saidas</div>
                        <div class="fs-4 fw-bold text-danger">R$ {{ fmt(caixa.total_saidas) }}</div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card p-3 border-start border-primary border-4">
                        <div class="text-muted small">Saldo</div>
                        <div class="fs-4 fw-bold" :class="saldo >= 0 ? 'text-primary' : 'text-danger'">
                            R$ {{ fmt(saldo) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totais por forma -->
            <div v-if="userIsAdmin" class="row g-3 mb-4">
                <div class="col-6 col-md-3" v-for="(label, key) in formas" :key="key">
                    <div class="card p-2 text-center">
                        <div class="text-muted small">{{ label }}</div>
                        <div class="fw-bold">R$ {{ fmt(totaisFormaCalc[key] || 0) }}</div>
                    </div>
                </div>
            </div>

            <!-- Formulario de entrada (se aberto) -->
            <div v-if="caixa.status === 'aberto'" class="card p-4 mb-4">
                <h6 class="mb-3">Adicionar Entrada</h6>
                <form @submit.prevent="adicionarEntrada">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-2">
                            <label class="form-label small">Forma *</label>
                            <select class="form-select" v-model="form.forma_recebimento" required @change="onFormaChange">
                                <option value="">Selecione...</option>
                                <option v-for="(label, key) in formas" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small">Banco</label>
                            <select class="form-select" v-model="form.banco_id" :disabled="form.forma_recebimento === 'dinheiro' || !form.forma_recebimento">
                                <option :value="null">-</option>
                                <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small">
                                {{ ehCartao ? 'Valor bruto *' : 'Valor *' }}
                                <span v-if="form.itens.length" class="text-muted" style="font-size:0.7rem"> (auto)</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.valor" :required="!form.itens.length" ref="valorInput">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Desconto</label>
                            <input type="number" step="0.01" min="0" class="form-control" v-model.number="form.desconto" placeholder="0,00">
                        </div>
                        <div v-if="ehCartao" class="col-12 col-md-2">
                            <label class="form-label small">Bandeira *</label>
                            <select class="form-select" v-model="form.bandeira_id" required>
                                <option :value="null">Selecione...</option>
                                <option v-for="b in bandeirasDisponiveis" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div v-if="form.forma_recebimento === 'cartao_credito'" class="col-12 col-md-1">
                            <label class="form-label small">Parcelas *</label>
                            <select class="form-select" v-model.number="form.parcelas" required>
                                <option v-for="n in 12" :key="n" :value="n">{{ n }}x</option>
                            </select>
                        </div>
                        <div class="col-12 col-md">
                            <label class="form-label small">Descricao</label>
                            <input type="text" class="form-control" v-model="form.descricao" placeholder="Opcional">
                        </div>
                    </div>
                    <div v-if="ehCartao" class="mt-2">
                        <div v-if="!planoAtivo" class="alert alert-warning py-2 mb-0 small">
                            Nenhum plano de maquininha ativo. <router-link :to="{ name: 'planos-maquininha.create' }">Cadastrar plano</router-link>.
                        </div>
                        <div v-else-if="previewCalc" class="alert py-2 mb-0 small" :class="previewCalc.erro ? 'alert-danger' : 'alert-info'">
                            <div v-if="previewCalc.erro">{{ previewCalc.erro }}</div>
                            <div v-else>
                                Taxa aplicada: <strong>{{ fmtPct(previewCalc.taxa_total) }}</strong>
                                <span v-if="previewCalc.com_antecipacao" class="text-muted">
                                    (taxa {{ fmtPct(previewCalc.taxa) }} + antecipacao {{ fmtPct(previewCalc.taxa_antecipacao) }})
                                </span>
                                — Valor liquido: <strong>R$ {{ fmt(previewCalc.valor_liquido) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <label class="form-label small mb-0">Itens (opcional)</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="mostrarNovoClientePet = !mostrarNovoClientePet">
                                    <i class="bi bi-person-plus"></i> Novo cliente/pet
                                </button>
                            </div>
                        </div>

                        <div v-if="mostrarNovoClientePet" class="border rounded p-2 mb-2 bg-light-subtle">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small">Cliente *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="novoClientePet.nome" placeholder="Nome do cliente">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">Telefone</label>
                                    <input type="text" class="form-control form-control-sm" v-model="novoClientePet.telefone" placeholder="(11) 99999-9999">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">Pet *</label>
                                    <input type="text" class="form-control form-control-sm" v-model="novoClientePet.pet_nome" placeholder="Nome do pet">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small">Tipo</label>
                                    <select class="form-select form-select-sm" v-model="novoClientePet.pet_tipo">
                                        <option :value="null">-</option>
                                        <option v-for="(label, key) in tiposPetCadastro" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small">Porte</label>
                                    <select class="form-select form-select-sm" v-model="novoClientePet.pet_porte">
                                        <option :value="null">-</option>
                                        <option value="pequeno">Pequeno</option>
                                        <option value="medio">Médio</option>
                                        <option value="grande">Grande</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">Raça</label>
                                    <input type="text" class="form-control form-control-sm" v-model="novoClientePet.pet_raca" placeholder="Ex: Labrador">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small">Idade (meses)</label>
                                    <input type="number" min="0" max="400" class="form-control form-control-sm" v-model.number="novoClientePet.pet_idade_meses" placeholder="Ex: 24">
                                </div>
                                <div class="col-6 col-md-1">
                                    <button type="button" class="btn btn-sm btn-success w-100" :disabled="salvandoNovoClientePet" @click="criarClientePetRapido">
                                        <span v-if="salvandoNovoClientePet" class="spinner-border spinner-border-sm"></span>
                                        <i v-else class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label small">Cliente (nome + telefone)</label>
                                <div class="position-relative">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        v-model="clienteBusca"
                                        placeholder="Digite nome ou telefone"
                                        @focus="abrirAutocompleteCliente"
                                        @blur="fecharAutocompleteCliente"
                                        @input="onClienteBuscaInput"
                                        @keydown.enter.prevent="selecionarPrimeiroClienteFiltrado"
                                    >
                                    <div v-if="autocompleteClienteAberto" class="list-group autocomplete-clientes shadow-sm">
                                        <button
                                            v-for="c in clientesFiltrados"
                                            :key="c.id"
                                            type="button"
                                            class="list-group-item list-group-item-action py-1"
                                            @mousedown.prevent="selecionarCliente(c)"
                                        >
                                            <div class="small fw-semibold">{{ c.nome }}</div>
                                            <div class="small text-muted">{{ c.telefone || 'Sem telefone' }}</div>
                                        </button>
                                        <div v-if="!clientesFiltrados.length" class="list-group-item small text-muted py-1">
                                            Nenhum cliente encontrado
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small">Pet</label>
                                <select class="form-select form-select-sm" v-model.number="petSelecionadoId" @change="onPetSelecionadoChange" :disabled="!petsClienteSelecionado.length">
                                    <option :value="null">Selecione...</option>
                                    <option v-for="pet in petsClienteSelecionado" :key="pet.id" :value="pet.id">
                                        {{ pet.nome }}{{ pet.tipo ? ' - ' + (perfisPet[pet.tipo] || pet.tipo) : '' }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div v-if="produtosMaisVendidosTop.length" class="mb-2">
                            <small class="text-muted d-block mb-1">Mais vendidos</small>
                            <div class="d-flex gap-2 flex-wrap">
                                <button
                                    v-for="p in produtosMaisVendidosTop"
                                    :key="p.id"
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    @click="adicionarItemRapidoRacao(p)"
                                >
                                    {{ p.nome }}
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" @click="adicionarItem()">
                                <i class="bi bi-plus-circle"></i> + Adicionar item
                            </button>
                        </div>

                        <div v-if="form.itens.length" class="mt-2">
                            <div v-for="(item, idx) in form.itens" :key="idx" class="border rounded p-2 mb-2 position-relative">
                                <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1" @click="removerItem(idx)">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Produto</label>
                                        <div class="position-relative">
                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                v-model="item._busca"
                                                placeholder="Buscar produto..."
                                                @focus="abrirAutocompleteProduto(item)"
                                                @blur="fecharAutocompleteProduto(item)"
                                                @input="onItemProdutoBuscaInput(item)"
                                                @keydown.enter.prevent="selecionarPrimeiroProdutoFiltrado(item)"
                                                autocomplete="off"
                                            >
                                            <div v-if="item._aberto" class="list-group autocomplete-produtos shadow-sm">
                                                <button
                                                    v-for="p in produtosFiltradosPorItem(item._busca)"
                                                    :key="p.id"
                                                    type="button"
                                                    class="list-group-item list-group-item-action py-1"
                                                    @mousedown.prevent="selecionarProduto(item, p)"
                                                >
                                                    <div class="small fw-semibold">{{ p.nome }}</div>
                                                    <div class="small text-muted">R$ {{ fmt(p.valor_venda) }}{{ p.total_vendas ? ' · ' + p.total_vendas + 'x vendido' : '' }}</div>
                                                </button>
                                                <div v-if="!produtosFiltradosPorItem(item._busca).length" class="list-group-item small text-muted py-1">
                                                    Nenhum produto encontrado
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!itemEhRacao(item)" class="col-4">
                                        <label class="form-label small mb-1">Unidade</label>
                                        <input
                                            type="number"
                                            step="1"
                                            min="1"
                                            class="form-control form-control-sm"
                                            v-model.number="item.quantidade"
                                            @input="recalcularSubtotal(item)"
                                        >
                                    </div>
                                    <input v-else type="hidden" v-model.number="item.quantidade">
                                    <div class="col-4">
                                        <label class="form-label small mb-1">Preco Unit.</label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="item.preco_unitario" @input="onPrecoUnitarioInput(item, $event.target.value)">
                                    </div>
                                    <div v-if="itemEhRacao(item)" class="col-4">
                                        <label class="form-label small mb-1">Peso (kg)</label>
                                        <input type="number" step="0.001" min="0.001" class="form-control form-control-sm" :value="pesoKgFromGramas(item.peso_gramas)" @input="onPesoKgInput(item, $event.target.value)" placeholder="Ex: 1,5">
                                    </div>
                                    <div v-else class="col-4">
                                        <label class="form-label small mb-1">Tipo</label>
                                        <input type="text" class="form-control form-control-sm" value="Venda por unidade" disabled>
                                    </div>
                                    <div v-if="itemEhRacao(item)" class="col-4">
                                        <label class="form-label small mb-1">Valor R$</label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" :value="item.subtotal || ''" @input="onValorInput(item, $event.target.value)" placeholder="Ex: 50,00">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Perfil (auto)</label>
                                        <select class="form-select form-select-sm" v-model="item.perfil_pet_tipo" disabled>
                                            <option :value="null">-</option>
                                            <option v-for="(label, key) in perfisPet" :key="key" :value="key">{{ label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Pet</label>
                                        <select class="form-select form-select-sm" v-model.number="item.pet_id" @change="onItemPetChange(item)">
                                            <option :value="null">-</option>
                                            <option v-for="pet in petsClienteSelecionado" :key="pet.id" :value="pet.id">{{ pet.nome }}</option>
                                        </select>
                                    </div>
                                    <div class="col-12 text-end">
                                        <span class="small fw-semibold">Subtotal: R$ {{ fmt(item.subtotal || 0) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-1">
                                <span class="badge bg-light text-dark">Total itens: R$ {{ fmt(totalItens) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-lua w-100" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-plus-lg"></i> Adicionar
                        </button>
                    </div>

                </form>
            </div>

            <!-- Lista de entradas -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0">
                        Entradas do Dia
                        <span class="badge bg-secondary ms-1">{{ entradas.length }}</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <button v-if="caixa.status === 'aberto'" class="btn btn-sm btn-outline-danger" @click="fecharCaixa">
                            <i class="bi bi-lock-fill"></i> Fechar Caixa
                        </button>

                        <template v-else-if="caixa.status === 'pendente'">
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-hourglass-split"></i> Pendente ({{ caixa.fechado_por?.name }})
                            </span>
                            <button v-if="userIsAdmin" class="btn btn-sm btn-success" @click="autorizarCaixa">
                                <i class="bi bi-check-lg"></i> Aprovar
                            </button>
                            <button v-if="userIsAdmin" class="btn btn-sm btn-outline-primary" @click="reabrirCaixa">
                                <i class="bi bi-unlock-fill"></i> Reabrir
                            </button>
                        </template>

                        <template v-else>
                            <span class="badge bg-secondary">
                                <i class="bi bi-lock-fill"></i> Fechado por {{ caixa.fechado_por?.name }} em {{ fmtDt(caixa.fechado_em) }}
                            </span>
                            <button v-if="userIsAdmin" class="btn btn-sm btn-outline-primary" @click="reabrirCaixa">
                                <i class="bi bi-unlock-fill"></i> Reabrir
                            </button>
                        </template>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Forma</th>
                                <th>Banco</th>
                                <th>Valor</th>
                                <th>Descricao</th>
                                <th v-if="caixa.status === 'aberto'" width="60"></th>
                            </tr>
                        </thead>
                        <TransitionGroup tag="tbody" name="row">
                            <tr v-for="e in entradasOrdenadas" :key="e.id" :class="{ 'table-success': e._new }">
                                <td class="text-muted small">{{ fmtHora(e.created_at) }}</td>
                                <td>
                                    <span class="badge" :class="formaBadge(e.forma_recebimento)">{{ formas[e.forma_recebimento] }}</span>
                                    <div v-if="e.bandeira_id || e.bandeira" class="small text-muted mt-1">
                                        {{ e.bandeira?.nome || '' }}
                                        <span v-if="e.parcelas">· {{ e.parcelas }}x</span>
                                        <span v-if="e.taxa_aplicada">· taxa {{ fmtPct(e.taxa_aplicada) }}</span>
                                    </div>
                                </td>
                                <td>{{ bancoNome(e.banco_id) }}</td>
                                <td class="fw-semibold text-success">
                                    R$ {{ fmt(e.valor) }}
                                    <div v-if="e.valor_bruto && parseFloat(e.valor_bruto) !== parseFloat(e.valor)" class="small text-muted fw-normal">
                                        bruto R$ {{ fmt(e.valor_bruto) }}
                                    </div>
                                    <div v-if="parseFloat(e.desconto || 0) > 0" class="small text-warning fw-normal">
                                        desc. R$ {{ fmt(e.desconto) }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ e.descricao || '-' }}</div>
                                    <div v-if="e.itens && e.itens.length" class="small text-muted mt-1">
                                        <span v-for="(it, i) in e.itens" :key="it.id || i" class="me-2">
                                            {{ it.produto?.nome || 'Item rapido' }}
                                            <span v-if="it.peso_gramas">({{ it.peso_gramas }}g)</span>
                                            <span v-if="it.pet"> · {{ it.pet.nome }}</span>
                                            <span v-if="it.pet?.cliente"> ({{ it.pet.cliente.nome }})</span>
                                            <span v-else-if="it.cliente"> ({{ it.cliente.nome }})</span>
                                        </span>
                                    </div>
                                </td>
                                <td v-if="caixa.status === 'aberto'">
                                    <button class="btn btn-sm btn-outline-danger" @click="removerEntrada(e)" :disabled="e._removing">
                                        <i class="bi" :class="e._removing ? 'spinner-border spinner-border-sm' : 'bi-x'"></i>
                                    </button>
                                </td>
                            </tr>
                        </TransitionGroup>
                        <tbody v-if="!entradas.length">
                            <tr>
                                <td :colspan="caixa.status === 'aberto' ? 6 : 5" class="text-center text-muted py-4">
                                    Nenhuma entrada registrada.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalWarning, swalInfo, swalConfirmDanger, swalConfirmSuccess, swalConfirmInfo } from '../../utils/swal';
const auth = useAuthStore();
const userIsAdmin = computed(() => auth.user?.role === 'admin');
const loading = ref(false);
const saving = ref(false);
const caixa = ref(null);
const entradas = ref([]);
const bancos = ref([]);
const bancosMap = ref({});
const valorInput = ref(null);
const lastSync = ref('');
let pollTimer = null;

const formas = { dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartao Debito', cartao_credito: 'Cartao Credito' };
const perfisPet = {
    cao_pequeno: 'Cao Pequeno',
    cao_medio: 'Cao Medio',
    cao_grande: 'Cao Grande',
    gato: 'Gato',
    outros: 'Outros',
};
const tiposPetCadastro = {
    cao: 'Cão',
    gato: 'Gato',
    outros: 'Outros',
};
const dataHoje = new Date().toLocaleDateString('pt-BR');
const form = reactive({
    forma_recebimento: '',
    banco_id: null,
    valor: '',
    desconto: 0,
    descricao: '',
    bandeira_id: null,
    parcelas: 1,
    itens: [],
});
const produtosRacaoFavoritos = ref([]);
const produtosMaisVendidosTop = computed(() => produtosRacaoFavoritos.value.slice(0, 10));
const clientesComPets = ref([]);
const clienteSelecionadoId = ref(null);
const petSelecionadoId = ref(null);
const clienteBusca = ref('');
const autocompleteClienteAberto = ref(false);
const mostrarNovoClientePet = ref(false);
const salvandoNovoClientePet = ref(false);
const novoClientePet = reactive({
    nome: '',
    telefone: '',
    pet_nome: '',
    pet_tipo: null,
    pet_porte: null,
    pet_raca: '',
    pet_idade_meses: null,
});

const planoAtivo = ref(null); // { plano, bandeiras: [{ id, nome, taxas: { debito, credito_avista, ... } }] }

const ehCartao = computed(() => ['cartao_debito', 'cartao_credito'].includes(form.forma_recebimento));

const bandeirasDisponiveis = computed(() => {
    if (!planoAtivo.value) return [];
    const mod = modalidadeAtual.value;
    return planoAtivo.value.bandeiras.filter(b => {
        return !mod || b.taxas?.[mod] !== null && b.taxas?.[mod] !== undefined;
    });
});

const modalidadeAtual = computed(() => {
    if (form.forma_recebimento === 'cartao_debito') return 'debito';
    if (form.forma_recebimento !== 'cartao_credito' || !form.parcelas) return null;
    if (form.parcelas === 1) return 'credito_avista';
    if (form.parcelas >= 2 && form.parcelas <= 6) return 'credito_2_6';
    if (form.parcelas >= 7 && form.parcelas <= 12) return 'credito_7_12';
    return null;
});

const previewCalc = computed(() => {
    if (!ehCartao.value || !planoAtivo.value || !form.bandeira_id || !form.valor) return null;
    const mod = modalidadeAtual.value;
    if (!mod) return null;

    const bandeira = planoAtivo.value.bandeiras.find(b => b.id === form.bandeira_id);
    if (!bandeira) return null;

    const taxa = bandeira.taxas?.[mod];
    if (taxa === null || taxa === undefined) {
        return { erro: 'Esta bandeira nao aceita esta modalidade neste plano.' };
    }

    const isCredito = form.forma_recebimento === 'cartao_credito';
    const taxaAnt = isCredito && planoAtivo.value.plano.taxa_antecipacao ? parseFloat(planoAtivo.value.plano.taxa_antecipacao) : 0;
    const taxaTotal = parseFloat(taxa) + taxaAnt;
    const desconto = parseFloat(form.desconto || 0) || 0;
    const bruto = Math.round((parseFloat(form.valor) - desconto) * 100) / 100;
    if (bruto <= 0) {
        return { erro: 'Valor apos desconto deve ser maior que zero.' };
    }
    const liquido = Math.round(bruto * (1 - taxaTotal / 100) * 100) / 100;

    return {
        taxa: parseFloat(taxa),
        taxa_antecipacao: taxaAnt,
        taxa_total: taxaTotal,
        valor_bruto_pos_desconto: bruto,
        valor_liquido: liquido,
        com_antecipacao: taxaAnt > 0,
    };
});

// Totais calculados localmente (instantaneo)
const totalEntradas = computed(() => entradas.value.reduce((s, e) => s + parseFloat(e.valor || 0), 0));
const saldo = computed(() => totalEntradas.value - parseFloat(caixa.value?.total_saidas || 0));
const totaisFormaCalc = computed(() => {
    const t = {};
    entradas.value.forEach(e => {
        t[e.forma_recebimento] = (t[e.forma_recebimento] || 0) + parseFloat(e.valor || 0);
    });
    return t;
});
const entradasOrdenadas = computed(() => {
    const lista = userIsAdmin.value
        ? entradas.value
        : entradas.value.filter(e => e.user_id === auth.user?.id);
    return [...lista].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});
const totalItens = computed(() => {
    return form.itens.reduce((sum, item) => sum + parseFloat(item.subtotal || 0), 0);
});
const clienteSelecionado = computed(() => {
    return clientesComPets.value.find(c => Number(c.id) === Number(clienteSelecionadoId.value)) || null;
});
const petsClienteSelecionado = computed(() => {
    return clienteSelecionado.value?.pets || [];
});
const clientesFiltrados = computed(() => {
    const termo = String(clienteBusca.value || '').trim().toLowerCase();
    if (!termo) return clientesComPets.value.slice(0, 8);

    return clientesComPets.value
        .filter(c => {
            const nome = String(c.nome || '').toLowerCase();
            const tel = String(c.telefone || '').toLowerCase();
            return nome.includes(termo) || tel.includes(termo);
        })
        .slice(0, 8);
});

function formatarClienteLabel(cliente) {
    if (!cliente) return '';
    return cliente.telefone ? `${cliente.nome} - ${cliente.telefone}` : cliente.nome;
}

function petById(id) {
    if (!id) return null;
    return petsClienteSelecionado.value.find(p => Number(p.id) === Number(id)) || null;
}

function itemBase() {
    const petPadrao = petById(petSelecionadoId.value) || null;

    return {
        produto_id: null,
        _busca: '',
        _aberto: false,
        quantidade: 1,
        preco_unitario: null,
        subtotal: 0,
        peso_gramas: null,
        perfil_pet_tipo: resolverPerfilAutomaticoPorPet(petPadrao),
        pet_id: petPadrao?.id || null,
        cliente_id: clienteSelecionado.value?.id || null,
    };
}

// Sincroniza form.valor automaticamente com a soma dos itens
watch(totalItens, (novo) => {
    if (form.itens.length > 0) {
        form.valor = novo > 0 ? novo : null;
    }
});

function adicionarItem() {
    form.itens.push(itemBase());
}

function removerItem(idx) {
    form.itens.splice(idx, 1);
}

function aplicarPetPadraoNosItens() {
    const petPadrao = petById(petSelecionadoId.value) || null;
    form.itens.forEach(item => {
        if (!item.cliente_id && clienteSelecionado.value?.id) {
            item.cliente_id = clienteSelecionado.value.id;
        }
        if (!item.pet_id && petPadrao?.id) {
            item.pet_id = petPadrao.id;
        }
        if (!item.perfil_pet_tipo || item.perfil_pet_tipo === 'outros') {
            item.perfil_pet_tipo = resolverPerfilAutomaticoPorPet(petPadrao);
        }
    });
}

function onClienteChange() {
    clienteBusca.value = formatarClienteLabel(clienteSelecionado.value);
    petSelecionadoId.value = null;
    onPetSelecionadoChange();
    aplicarPetPadraoNosItens();
}

function selecionarCliente(cliente) {
    if (!cliente) return;
    clienteSelecionadoId.value = Number(cliente.id);
    onClienteChange();
    autocompleteClienteAberto.value = false;
}

function abrirAutocompleteCliente() {
    if (clienteSelecionado.value) {
        // Ao clicar novamente, abre lista completa sem perder o cliente selecionado.
        clienteBusca.value = '';
    }
    autocompleteClienteAberto.value = true;
}

function fecharAutocompleteCliente() {
    setTimeout(() => {
        autocompleteClienteAberto.value = false;
        if (clienteSelecionado.value) {
            clienteBusca.value = formatarClienteLabel(clienteSelecionado.value);
        }
    }, 120);
}

function onClienteBuscaInput() {
    autocompleteClienteAberto.value = true;
    if (!clienteBusca.value) {
        clienteSelecionadoId.value = null;
        petSelecionadoId.value = null;
    }
}

function selecionarPrimeiroClienteFiltrado() {
    if (!clientesFiltrados.value.length) return;
    selecionarCliente(clientesFiltrados.value[0]);
}

function onPetSelecionadoChange() {
    const petSelecionado = petById(petSelecionadoId.value);
    const tipoNormalizado = normalizarTipoPet(petSelecionado?.tipo);
    if (tipoNormalizado) {
        novoClientePet.pet_tipo = tipoNormalizado;
    }
    novoClientePet.pet_porte = normalizarPortePet(petSelecionado?.porte);
    novoClientePet.pet_raca = petSelecionado?.raca || '';
    novoClientePet.pet_idade_meses = Number.isFinite(Number(petSelecionado?.idade_meses))
        ? Number(petSelecionado.idade_meses)
        : null;

    aplicarPetPadraoNosItens();
}

function normalizarTipoPet(tipo) {
    return {
        cao: 'cao',
        cao_pequeno: 'cao',
        cao_medio: 'cao',
        cao_grande: 'cao',
        gato: 'gato',
        outros: 'outros',
    }[tipo] || null;
}

function normalizarPortePet(porte) {
    return {
        pequeno: 'pequeno',
        medio: 'medio',
        grande: 'grande',
    }[porte] || null;
}

function onItemPetChange(item) {
    const pet = petById(item.pet_id);
    if (pet?.cliente_id) {
        item.cliente_id = pet.cliente_id;
    }
    item.perfil_pet_tipo = resolverPerfilAutomaticoPorPet(pet);
}

function resolverPerfilAutomaticoPorPet(pet) {
    if (!pet) return null;

    const tipo = normalizarTipoPet(pet.tipo);
    if (tipo === 'gato') return 'gato';
    if (tipo === 'outros') return 'outros';
    if (tipo !== 'cao') return null;

    const porte = normalizarPortePet(pet.porte);
    if (porte === 'pequeno') return 'cao_pequeno';
    if (porte === 'grande') return 'cao_grande';
    return 'cao_medio';
}

async function criarClientePetRapido() {
    if (!novoClientePet.nome || !novoClientePet.pet_nome) {
        swalWarning('Informe nome do cliente e nome do pet.');
        return;
    }

    salvandoNovoClientePet.value = true;
    try {
        const { data } = await axios.post('/caixa/clientes-com-pets', {
            nome: novoClientePet.nome,
            telefone: novoClientePet.telefone || null,
            pet_nome: novoClientePet.pet_nome,
            pet_tipo: novoClientePet.pet_tipo || null,
            pet_porte: novoClientePet.pet_porte || null,
            pet_raca: novoClientePet.pet_raca || null,
            pet_idade_meses: Number.isFinite(Number(novoClientePet.pet_idade_meses))
                ? Number(novoClientePet.pet_idade_meses)
                : null,
        });

        await loadClientesComPets();

        const clienteCriado = clientesComPets.value.find(c => Number(c.id) === Number(data?.cliente?.id));
        if (clienteCriado) {
            selecionarCliente(clienteCriado);
        }

        novoClientePet.nome = '';
        novoClientePet.telefone = '';
        novoClientePet.pet_nome = '';
        novoClientePet.pet_tipo = null;
        novoClientePet.pet_porte = null;
        novoClientePet.pet_raca = '';
        novoClientePet.pet_idade_meses = null;
        mostrarNovoClientePet.value = false;
        swalSuccess('Cliente e pet criados com sucesso.');
    } catch (e) {
        const msg = e.response?.data?.errors
            ? Object.values(e.response.data.errors).flat().join('. ')
            : (e.response?.data?.message || 'Erro ao criar cliente/pet.');
        swalError(msg);
    } finally {
        salvandoNovoClientePet.value = false;
    }
}

function onItemProdutoChange(item) {
    const produto = produtosRacaoFavoritos.value.find(p => p.id === item.produto_id);
    const ehRacao = produto?.categoria === 'racao';

    if (produto) {
        item.preco_unitario = parseFloat(produto.valor_venda || 0);
    }

    if (ehRacao) {
        item.quantidade = 1;
        item.peso_gramas = null;
        item.subtotal = 0;
    }

    // Produtos nao-racao (ex.: medicamento) nao usam peso em kg.
    if (!ehRacao) {
        item.peso_gramas = null;
    }

    recalcularSubtotal(item);
}

function itemEhRacao(item) {
    const produto = produtosRacaoFavoritos.value.find(p => p.id === item?.produto_id);
    return produto?.categoria === 'racao';
}

function pesoKgFromGramas(g) {
    const n = Number(g);
    if (!Number.isFinite(n) || n <= 0) return '';
    return Math.round((n / 1000) * 1000) / 1000;
}

function parseDecimalInput(raw) {
    const norm = String(raw ?? '').replace(',', '.').trim();
    if (norm === '') return null;
    const parsed = parseFloat(norm);
    return Number.isFinite(parsed) ? parsed : null;
}

function recalcularRacaoPorPeso(item) {
    const pu = parseFloat(item.preco_unitario || 0);
    const pesoGramas = Number(item.peso_gramas || 0);

    if (pu <= 0 || pesoGramas <= 0) {
        item.subtotal = 0;
        return;
    }

    item.subtotal = Math.round((pesoGramas / 1000) * pu * 100) / 100;
}

function recalcularRacaoPorValor(item) {
    const pu = parseFloat(item.preco_unitario || 0);
    const valor = parseFloat(item.subtotal || 0);

    if (pu <= 0 || valor <= 0) {
        item.peso_gramas = null;
        if (valor <= 0) item.subtotal = 0;
        return;
    }

    item.peso_gramas = Math.round((valor / pu) * 1000);
}

function onPesoKgInput(item, raw) {
    const kg = parseDecimalInput(raw);
    if (kg === null) {
        item.peso_gramas = null;
        item.subtotal = 0;
        return;
    }

    if (kg <= 0) {
        item.peso_gramas = null;
        item.subtotal = 0;
    } else {
        item.peso_gramas = Math.round(kg * 1000);
        recalcularRacaoPorPeso(item);
    }
}

function onValorInput(item, raw) {
    const valor = parseDecimalInput(raw);
    if (valor === null) {
        item.subtotal = 0;
        item.peso_gramas = null;
        return;
    }

    if (valor < 0) {
        return;
    }

    item.subtotal = Math.round(valor * 100) / 100;

    if (itemEhRacao(item)) {
        recalcularRacaoPorValor(item);
    }
}

function onPrecoUnitarioInput(item, raw) {
    const preco = parseDecimalInput(raw);
    if (preco === null || preco < 0) {
        item.preco_unitario = null;
        item.subtotal = 0;
        item.peso_gramas = null;
        return;
    }

    item.preco_unitario = Math.round(preco * 100) / 100;

    if (itemEhRacao(item)) {
        if (Number(item.peso_gramas) > 0) {
            recalcularRacaoPorPeso(item);
            return;
        }
        if (Number(item.subtotal) > 0) {
            recalcularRacaoPorValor(item);
            return;
        }
        item.subtotal = 0;
        return;
    }

    recalcularSubtotal(item);
}

function recalcularSubtotal(item) {
    const pu = parseFloat(item.preco_unitario || 0);
    if (itemEhRacao(item) && Number(item.peso_gramas) > 0) {
        // Em vendas por peso, considera preco_unitario como preco por kg.
        item.subtotal = Math.round((item.peso_gramas / 1000) * pu * 100) / 100;
    } else {
        const qtd = parseFloat(item.quantidade || 0);
        item.subtotal = Math.round(qtd * pu * 100) / 100;
    }
}

function adicionarItemRapidoRacao(produto) {
    const petPadrao = petById(petSelecionadoId.value) || null;
    const item = {
        ...itemBase(),
        produto_id: produto.id,
        _busca: produto.nome,
        quantidade: 1,
        preco_unitario: parseFloat(produto.valor_venda || 0),
        peso_gramas: null,
        perfil_pet_tipo: resolverPerfilAutomaticoPorPet(petPadrao) || 'outros',
        pet_id: petPadrao?.id || null,
        cliente_id: clienteSelecionado.value?.id || null,
    };
    recalcularSubtotal(item);
    form.itens.push(item);
}

function produtosFiltradosPorItem(busca) {
    const termo = String(busca || '').trim().toLowerCase();
    if (!termo) return produtosRacaoFavoritos.value.slice(0, 8);
    return produtosRacaoFavoritos.value
        .filter(p => p.nome.toLowerCase().includes(termo))
        .slice(0, 10);
}

function abrirAutocompleteProduto(item) {
    if (item.produto_id) item._busca = '';
    item._aberto = true;
}

function fecharAutocompleteProduto(item) {
    setTimeout(() => {
        item._aberto = false;
        if (item.produto_id) {
            const p = produtosRacaoFavoritos.value.find(p => p.id === item.produto_id);
            item._busca = p?.nome || '';
        }
    }, 120);
}

function onItemProdutoBuscaInput(item) {
    item._aberto = true;
    if (!item._busca) {
        item.produto_id = null;
    }
}

function selecionarProduto(item, produto) {
    item.produto_id = produto.id;
    item._busca = produto.nome;
    item._aberto = false;
    onItemProdutoChange(item);
}

function selecionarPrimeiroProdutoFiltrado(item) {
    const lista = produtosFiltradosPorItem(item._busca);
    if (lista.length) selecionarProduto(item, lista[0]);
}

function onFormaChange() {
    if (form.forma_recebimento === 'dinheiro') {
        form.banco_id = null;
    }
    if (!ehCartao.value) {
        form.bandeira_id = null;
        form.parcelas = 1;
    } else if (form.forma_recebimento === 'cartao_debito') {
        form.parcelas = 1;
    }
}

function formaBadge(forma) {
    return {
        dinheiro: 'bg-success', pix: 'bg-primary',
        cartao_debito: 'bg-warning text-dark', cartao_credito: 'bg-danger',
    }[forma] || 'bg-secondary';
}

function bancoNome(id) {
    return bancosMap.value[id] || '-';
}

// ── Load do servidor ──
async function load() {
    const { data } = await axios.get('/caixa/hoje');
    caixa.value = data.caixa;
    if (data.caixa) {
        entradas.value = data.caixa.entradas || [];
    }
    lastSync.value = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// ── Polling silencioso (nao reseta form, nao trava UI) ──
async function silentSync() {
    if (!caixa.value) return;
    try {
        const { data } = await axios.get('/caixa/hoje');
        if (data.caixa) {
            const oldStatus = caixa.value.status;
            const newStatus = data.caixa.status;

            // Detectar mudanca de status (outro usuario fechou/reabriu)
            if (oldStatus !== newStatus) {
                caixa.value.status = newStatus;
                caixa.value.fechado_por = data.caixa.fechado_por;
                caixa.value.fechado_em = data.caixa.fechado_em;
                caixa.value.autorizado_por = data.caixa.autorizado_por;
                caixa.value.autorizado_em = data.caixa.autorizado_em;

                if (newStatus === 'pendente') {
                    swalWarning('O caixa foi enviado para aprovacao por ' + (data.caixa.fechado_por?.name || 'outro usuario') + '.');
                } else if (newStatus === 'fechado' && oldStatus === 'pendente') {
                    swalSuccess('O caixa foi aprovado e fechado.');
                } else if (newStatus === 'aberto' && oldStatus !== 'aberto') {
                    swalSuccess('O caixa foi reaberto por um administrador.');
                }
            }

            caixa.value.total_saidas = data.caixa.total_saidas;

            // Merge entradas apenas se aberto
            if (newStatus === 'aberto') {
                const localIds = new Set(entradas.value.map(e => e.id));
                const serverEntradas = data.caixa.entradas || [];

                serverEntradas.forEach(se => {
                    if (!localIds.has(se.id)) {
                        se._new = true;
                        entradas.value.push(se);
                        setTimeout(() => { se._new = false; }, 2000);
                    }
                });

                const serverIds = new Set(serverEntradas.map(e => e.id));
                entradas.value = entradas.value.filter(e => serverIds.has(e.id));
            } else {
                // Se nao esta aberto, sincronizar entradas do servidor direto
                entradas.value = data.caixa.entradas || [];
            }

            lastSync.value = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    } catch {}
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(silentSync, 15000);
}

function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

// ── Abrir caixa ──
async function abrirCaixa() {
    loading.value = true;
    try {
        await axios.post('/caixa/abrir');
        await load();
        startPolling();
        swalSuccess('Caixa aberto com sucesso.');
    } catch { swalError('Erro ao abrir caixa.'); }
    finally { loading.value = false; }
}

// ── Adicionar entrada (otimista) ──
async function adicionarEntrada() {
    if (!form.forma_recebimento || (!form.valor && !form.itens.length)) return;
    if (ehCartao.value && !form.bandeira_id) {
        swalWarning('Selecione a bandeira do cartao.');
        return;
    }
    if (form.forma_recebimento === 'cartao_credito' && !form.parcelas) {
        swalWarning('Selecione a quantidade de parcelas.');
        return;
    }
    saving.value = true;

    // Para cartao: exibir valor liquido na entrada local (consistente com o que o backend vai salvar)
    const isCartao = ehCartao.value;
    const desconto = parseFloat(form.desconto || 0) || 0;
    const valorReferencia = form.valor ? parseFloat(form.valor) : totalItens.value;
    const valorPosDesconto = Math.round((valorReferencia - desconto) * 100) / 100;
    const valorLocal = isCartao && previewCalc.value && !previewCalc.value.erro
        ? previewCalc.value.valor_liquido
        : valorPosDesconto;

    // Criar entrada temporaria local
    const tempId = 'temp_' + Date.now();
    const entradaLocal = {
        id: tempId,
        forma_recebimento: form.forma_recebimento,
        banco_id: form.banco_id,
        bandeira_id: form.bandeira_id,
        parcelas: isCartao ? form.parcelas : null,
        valor_bruto: isCartao ? valorPosDesconto : null,
        taxa_aplicada: isCartao && previewCalc.value ? previewCalc.value.taxa_total : null,
        valor: valorLocal,
        desconto: desconto,
        descricao: form.descricao || null,
        itens: form.itens,
        bandeira: isCartao ? planoAtivo.value?.bandeiras.find(b => b.id === form.bandeira_id) : null,
        created_at: new Date().toISOString(),
        _new: true,
        _saving: true,
    };

    entradas.value.push(entradaLocal);

    // Limpar form e focar no valor para proxima entrada
    const payload = {
        forma_recebimento: form.forma_recebimento,
        banco_id: form.banco_id || null,
        valor: form.valor || null,
        desconto: desconto || 0,
        descricao: form.descricao || null,
        bandeira_id: isCartao ? form.bandeira_id : null,
        parcelas: isCartao ? form.parcelas : null,
        itens: form.itens.length
            ? form.itens.map(item => ({
                produto_id: item.produto_id || null,
                quantidade: item.quantidade,
                preco_unitario: item.preco_unitario || null,
                subtotal: item.subtotal || null,
                peso_gramas: item.peso_gramas || null,
                perfil_pet_tipo: item.perfil_pet_tipo || null,
                cliente_id: item.cliente_id ? Number(item.cliente_id) : (clienteSelecionado.value?.id || null),
                pet_id: item.pet_id ? Number(item.pet_id) : null,
            }))
            : null,
    };
    form.valor = '';
    form.desconto = 0;
    form.descricao = '';
    form.itens = [];

    try {
        const { data: entradaReal } = await axios.post('/caixa/' + caixa.value.id + '/entrada', payload);

        // Substituir entrada temporaria pela real
        const idx = entradas.value.findIndex(e => e.id === tempId);
        if (idx !== -1) {
            entradaReal._new = true;
            entradas.value.splice(idx, 1, entradaReal);
            setTimeout(() => { entradaReal._new = false; }, 2000);
        }

        // Focar no campo valor para proxima entrada rapida
        await nextTick();
        valorInput.value?.focus();
    } catch (e) {
        // Remover entrada temporaria em caso de erro
        entradas.value = entradas.value.filter(en => en.id !== tempId);
        const msg = e.response?.data?.errors
            ? Object.values(e.response.data.errors).flat().join('. ')
            : (e.response?.data?.message || 'Erro ao adicionar entrada.');
        swalError(msg);
    } finally {
        saving.value = false;
    }
}

// ── Remover entrada (otimista) ──
async function removerEntrada(entrada) {
    if (!(await swalConfirmDanger('Remover entrada?', 'Esta acao nao pode ser desfeita.'))) return;
    entrada._removing = true;

    // Remover localmente
    const backup = [...entradas.value];
    entradas.value = entradas.value.filter(e => e.id !== entrada.id);

    try {
        await axios.delete('/caixa/' + caixa.value.id + '/entrada/' + entrada.id);
    } catch {
        // Restaurar se falhou
        entradas.value = backup;
        swalError('Erro ao remover entrada.');
    }
}

// ── Fechar caixa ──
async function fecharCaixa() {
    const msg = userIsAdmin.value
        ? 'Fechar o caixa definitivamente?'
        : 'Enviar o caixa para aprovacao do administrador?';
    if (!(await swalConfirmSuccess('Fechar Caixa', msg))) return;
    try {
        const { data } = await axios.post('/caixa/' + caixa.value.id + '/fechar');
        caixa.value = data;
        entradas.value = data.entradas || entradas.value;
        if (data.status === 'fechado') {
            stopPolling();
        }
        swalSuccess(data.status === 'pendente'
            ? 'Caixa enviado para autorizacao do administrador.'
            : 'Caixa fechado com sucesso.'
        );
    } catch { swalError('Erro ao fechar caixa.'); }
}

// ── Reabrir caixa (admin) ──
async function reabrirCaixa() {
    if (!(await swalConfirmInfo('Reabrir Caixa?', 'As entradas serao preservadas.'))) return;
    try {
        const { data } = await axios.post('/caixa/' + caixa.value.id + '/reabrir');
        caixa.value = data;
        entradas.value = data.entradas || [];
        startPolling();
        swalSuccess('Caixa reaberto com sucesso.');
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao reabrir caixa.');
    }
}

// ── Autorizar caixa (admin) ──
async function autorizarCaixa() {
    if (!(await swalConfirmSuccess('Aprovar Fechamento?', 'O caixa sera fechado definitivamente.'))) return;
    try {
        const { data } = await axios.post('/caixa/' + caixa.value.id + '/autorizar');
        caixa.value = data;
        stopPolling();
        swalSuccess('Caixa aprovado e fechado com sucesso.');
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao autorizar caixa.');
    }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtPct(v) { return Number(v || 0).toFixed(2).replace('.', ',') + ' %'; }
function fmtDt(d) { return d ? new Date(d).toLocaleString('pt-BR') : ''; }
function fmtHora(d) {
    if (!d) return '';
    return new Date(d).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

async function loadPlanoAtivo() {
    try {
        const { data } = await axios.get('/planos-maquininha/ativo');
        planoAtivo.value = data;
    } catch {
        planoAtivo.value = null;
    }
}

async function loadProdutosRacaoFavoritos() {
    try {
        const { data } = await axios.get('/caixa/produtos-racao-favoritos');
        produtosRacaoFavoritos.value = data || [];
    } catch {
        produtosRacaoFavoritos.value = [];
    }
}

async function loadClientesComPets() {
    try {
        const { data } = await axios.get('/caixa/clientes-com-pets');
        clientesComPets.value = data || [];

        if (clienteSelecionadoId.value) {
            clienteBusca.value = formatarClienteLabel(clienteSelecionado.value);
        }
    } catch {
        clientesComPets.value = [];
        clienteSelecionadoId.value = null;
        petSelecionadoId.value = null;
    }
}

onMounted(async () => {
    const [, bancosRes] = await Promise.all([
        load(),
        axios.get('/bancos'),
        loadPlanoAtivo(),
        loadProdutosRacaoFavoritos(),
        loadClientesComPets(),
    ]);
    bancos.value = bancosRes.data.filter(b => b.ativo);
    bancosMap.value = Object.fromEntries(bancosRes.data.map(b => [b.id, b.nome]));

    aplicarPetPadraoNosItens();

    // Polling ativo enquanto caixa nao estiver definitivamente fechado
    if (caixa.value && caixa.value.status !== 'fechado') {
        startPolling();
    }
});

onUnmounted(stopPolling);
</script>

<style scoped>
.row-enter-active { transition: all 0.3s ease; }
.row-leave-active { transition: all 0.2s ease; }
.row-enter-from { opacity: 0; transform: translateY(-10px); }
.row-leave-to { opacity: 0; transform: translateX(20px); }

.autocomplete-clientes,
.autocomplete-produtos {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 20;
    max-height: 220px;
    overflow-y: auto;
}
</style>
