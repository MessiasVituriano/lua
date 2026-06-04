<template>
    <div class="pg-wrapper">

        <!-- Brand -->
        <div class="pg-brand">
            <div class="brand-mark">🌙</div>
            <div>
                <div class="brand-name">LUA</div>
                <div class="brand-tagline">Agendamento · PetShop</div>
            </div>
        </div>
        <div v-if="loja.nome" class="pg-loja-nome">{{ loja.nome }}</div>

        <!-- Carregando -->
        <div v-if="state === 'loading'" class="pg-card text-center py-5 text-muted">
            <div class="spinner-border mb-3" role="status"></div>
            <div>Carregando...</div>
        </div>

        <!-- Inválido -->
        <div v-else-if="state === 'invalido'" class="pg-card text-center py-5">
            <div class="fs-1 mb-3">⛔</div>
            <h5 class="fw-bold">Link inválido ou expirado</h5>
            <p class="text-muted small">Este link não está mais disponível.<br>Solicite um novo link ao petshop.</p>
        </div>

        <!-- Sucesso -->
        <div v-else-if="state === 'sucesso'" class="pg-card text-center py-4">
            <div class="fs-1 mb-2">🎉</div>
            <h5 class="fw-bold text-success mb-3">Agendamento solicitado!</h5>
            <div class="confirm-box">
                <div class="confirm-row"><span>Serviço</span><strong>{{ confirmacao.servico }}</strong></div>
                <div class="confirm-row"><span>Data</span><strong>{{ confirmacao.data }}</strong></div>
                <div class="confirm-row"><span>Horário</span><strong>{{ confirmacao.horario_inicio }} – {{ confirmacao.horario_fim }}</strong></div>
            </div>
            <p class="text-muted small mt-3">Em breve o petshop entrará em contato para confirmar.</p>
        </div>

        <!-- Formulário -->
        <div v-else-if="state === 'form'" class="pg-card">

            <!-- PASSO 1: Pet (só se houver mais de um) -->
            <div v-if="pets.length > 1" class="form-section">
                <div class="section-label">
                    <span class="step-dot">{{ stepNums.pet }}</span> Pet
                </div>
                <div class="choice-grid">
                    <button
                        v-for="p in pets" :key="p.id"
                        class="choice-btn"
                        :class="{ 'choice-btn--on': form.pet_id === p.id }"
                        @click="form.pet_id = p.id"
                    >
                        <span class="choice-main">{{ p.nome }}</span>
                        <span v-if="p.raca || p.porte" class="choice-sub">
                            {{ [p.raca, porteLabel(p.porte)].filter(Boolean).join(' · ') }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- PASSO 2: Serviço -->
            <div v-if="petResolvido" class="form-section">
                <div class="section-label">
                    <span class="step-dot">{{ stepNums.servico }}</span> Serviço
                </div>
                <div class="servicos-list">
                    <button
                        v-for="s in servicos" :key="s.id"
                        class="servico-btn"
                        :class="{ 'servico-btn--on': form.servico_id === s.id }"
                        @click="selecionarServico(s)"
                    >
                        <span class="servico-nome">{{ s.nome }}</span>
                        <span class="servico-meta">{{ s.duracao_minutos }}min · {{ fmtMoney(s.preco_base) }}</span>
                    </button>
                </div>
            </div>

            <!-- PASSO 3: Data (calendário) -->
            <div v-if="form.servico_id" class="form-section">
                <div class="section-label">
                    <span class="step-dot">{{ stepNums.data }}</span> Data
                </div>

                <div class="cal-nav">
                    <button class="cal-arrow" @click="mesAnterior" :disabled="!podeMesAnterior">&#8249;</button>
                    <span class="cal-titulo">{{ labelMesAtual }}</span>
                    <button class="cal-arrow" @click="mesProximo" :disabled="!podeMesProximo">&#8250;</button>
                </div>

                <div class="cal-grid">
                    <div v-for="d in DIAS_SEMANA" :key="d" class="cal-head">{{ d }}</div>
                    <template v-for="(cell, i) in calendarCells" :key="i">
                        <div v-if="cell === null" class="cal-cell cal-cell--vazio"></div>
                        <button
                            v-else
                            class="cal-cell"
                            :class="{
                                'cal-cell--disponivel': cell.disponivel && !cell.selected,
                                'cal-cell--selected':   cell.selected,
                                'cal-cell--today':      cell.isToday && !cell.selected,
                                'cal-cell--off':        !cell.disponivel,
                            }"
                            :disabled="!cell.disponivel"
                            @click="selecionarData(cell.dateStr)"
                        >{{ cell.day }}</button>
                    </template>
                </div>
            </div>

            <!-- PASSO 4: Horário -->
            <div v-if="form.data" class="form-section">
                <div class="section-label">
                    <span class="step-dot">{{ stepNums.horario }}</span> Horário
                    <span class="section-sub">{{ fmtDataCurta(form.data) }}</span>
                </div>
                <div v-if="loadingSlots" class="text-center text-muted py-3 small">
                    <span class="spinner-border spinner-border-sm me-1"></span>Carregando horários...
                </div>
                <div v-else-if="slots.length === 0" class="aviso-vazio">
                    Nenhum horário disponível. Escolha outra data.
                </div>
                <div v-else class="slots-grid">
                    <button
                        v-for="s in slots" :key="s"
                        class="slot-btn"
                        :class="{ 'slot-btn--on': form.horario_inicio === s }"
                        @click="form.horario_inicio = s"
                    >{{ s }}</button>
                </div>
            </div>

            <!-- PASSO 5: Observação + Confirmar -->
            <div v-if="form.horario_inicio" class="form-section">
                <div class="section-label">
                    <span class="step-dot">{{ stepNums.obs }}</span> Observação
                    <span class="section-sub">(opcional)</span>
                </div>
                <textarea
                    class="form-control form-control-sm"
                    v-model="form.observacao"
                    rows="2"
                    placeholder="Alguma informação importante sobre o pet..."
                    maxlength="500"
                ></textarea>

                <div class="resumo-box mt-3">
                    <div class="resumo-row"><span>📋</span><span>{{ servicoSelecionado?.nome }}</span></div>
                    <div class="resumo-row"><span>📅</span><span>{{ fmtDataCompleta(form.data) }}</span></div>
                    <div class="resumo-row"><span>🕐</span><span>{{ form.horario_inicio }}</span></div>
                    <div v-if="petSelecionado" class="resumo-row"><span>🐾</span><span>{{ petSelecionado.nome }}</span></div>
                </div>

                <button class="btn-confirmar mt-3" @click="confirmar" :disabled="confirmando">
                    <span v-if="confirmando" class="spinner-border spinner-border-sm me-2"></span>
                    <span v-else>📅 </span>Confirmar agendamento
                </button>
                <div v-if="erroConfirmar" class="aviso-erro mt-2">{{ erroConfirmar }}</div>
            </div>

        </div>

        <div class="pg-footer">&copy; {{ new Date().getFullYear() }} LUA · Gestão para petshops</div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const token = route.params.token;

// ── Dados da API ──────────────────────────────────────────────────────────────
const state    = ref('loading');
const loja     = ref({});
const cliente  = ref(null);
const servicos = ref([]);
const dias     = ref([]);

const pets = computed(() => cliente.value?.pets?.filter(p => p.ativo !== false) ?? []);

// ── Formulário ────────────────────────────────────────────────────────────────
const form = ref({ pet_id: null, servico_id: null, data: '', horario_inicio: '', observacao: '' });

const slots         = ref([]);
const loadingSlots  = ref(false);
const confirmando   = ref(false);
const erroConfirmar = ref('');
const confirmacao   = ref({});

const petResolvido       = computed(() => pets.value.length <= 1 || form.value.pet_id !== null);
const servicoSelecionado = computed(() => servicos.value.find(s => s.id === form.value.servico_id));
const petSelecionado     = computed(() => pets.value.find(p => p.id === form.value.pet_id));

const stepNums = computed(() => {
    const hp = pets.value.length > 1;
    return { pet: 1, servico: hp ? 2 : 1, data: hp ? 3 : 2, horario: hp ? 4 : 3, obs: hp ? 5 : 4 };
});

// ── Calendário ────────────────────────────────────────────────────────────────
const DIAS_SEMANA = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
const NOMES_MESES = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

const today     = new Date();
const todayStr  = today.toISOString().slice(0, 10);
const viewYear  = ref(today.getFullYear());
const viewMonth = ref(today.getMonth());

const diasSet       = computed(() => new Set(dias.value.map(d => d.data)));
const labelMesAtual = computed(() => `${NOMES_MESES[viewMonth.value]} ${viewYear.value}`);

const minMonth = computed(() => ({ year: today.getFullYear(), month: today.getMonth() }));
const maxMonth = computed(() => {
    if (!dias.value.length) return minMonth.value;
    const last = dias.value[dias.value.length - 1].data;
    const [y, m] = last.split('-').map(Number);
    return { year: y, month: m - 1 };
});

const podeMesAnterior = computed(() =>
    viewYear.value > minMonth.value.year ||
    (viewYear.value === minMonth.value.year && viewMonth.value > minMonth.value.month)
);
const podeMesProximo = computed(() =>
    viewYear.value < maxMonth.value.year ||
    (viewYear.value === maxMonth.value.year && viewMonth.value < maxMonth.value.month)
);

function mesAnterior() {
    if (!podeMesAnterior.value) return;
    if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value--; } else viewMonth.value--;
}
function mesProximo() {
    if (!podeMesProximo.value) return;
    if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++; } else viewMonth.value++;
}

const calendarCells = computed(() => {
    const y = viewYear.value, m = viewMonth.value;
    const firstDow    = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const cells       = [];
    for (let i = 0; i < firstDow; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) {
        const mm = String(m + 1).padStart(2, '0'), dd = String(d).padStart(2, '0');
        const dateStr = `${y}-${mm}-${dd}`;
        cells.push({ day: d, dateStr, disponivel: diasSet.value.has(dateStr), selected: form.value.data === dateStr, isToday: dateStr === todayStr });
    }
    return cells;
});

// ── Inicialização ─────────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        const { data } = await axios.get(`/publico/agendar/${token}`);
        loja.value     = data.loja;
        cliente.value  = data.cliente;
        servicos.value = data.servicos;
        dias.value     = data.dias;
        state.value    = 'form';
        if (pets.value.length === 1) form.value.pet_id = pets.value[0].id;
    } catch { state.value = 'invalido'; }
});

// ── Ações ─────────────────────────────────────────────────────────────────────
function selecionarServico(s) {
    form.value.servico_id = s.id;
    form.value.data = '';
    form.value.horario_inicio = '';
    slots.value = [];
    erroConfirmar.value = '';
}

async function selecionarData(dateStr) {
    form.value.data = dateStr;
    form.value.horario_inicio = '';
    erroConfirmar.value = '';
    slots.value = [];
    loadingSlots.value = true;
    try {
        const { data } = await axios.get(`/publico/agendar/${token}/slots`, {
            params: { data: dateStr, servico_id: form.value.servico_id },
        });
        slots.value = data.slots;
    } catch { slots.value = []; } finally { loadingSlots.value = false; }
}

async function confirmar() {
    erroConfirmar.value = '';
    confirmando.value = true;
    try {
        const { data } = await axios.post(`/publico/agendar/${token}`, {
            servico_id: form.value.servico_id,
            data: form.value.data,
            horario_inicio: form.value.horario_inicio,
            pet_id: form.value.pet_id || null,
            observacao: form.value.observacao || null,
        });
        confirmacao.value = data.agendamento;
        state.value = 'sucesso';
    } catch (e) {
        erroConfirmar.value = e.response?.data?.message || 'Erro ao realizar agendamento. Tente novamente.';
    } finally { confirmando.value = false; }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtMoney(v) { return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); }
function porteLabel(v) { return { pequeno: 'Pequeno', medio: 'Médio', grande: 'Grande' }[v] || ''; }
function fmtDataCurta(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' });
}
function fmtDataCompleta(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
}
</script>

<style scoped>
.pg-wrapper {
    min-height: 100vh;
    background: #f0ecff;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem 1rem 4rem;
}
.pg-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 0.25rem; }
.brand-mark    { font-size: 1.8rem; line-height: 1; }
.brand-name    { font-weight: 800; font-size: 1.15rem; letter-spacing: .1em; color: #6246ea; }
.brand-tagline { font-size: .7rem; color: #999; }
.pg-loja-nome  { font-size: 1.05rem; font-weight: 700; color: #222; margin-bottom: 1.25rem; text-align: center; }
.pg-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 28px rgba(98,70,234,.12);
    padding: 1.5rem;
    width: 100%; max-width: 480px;
}
.pg-footer { margin-top: 1.5rem; font-size: .7rem; color: #bbb; }

.form-section  { margin-bottom: 1.5rem; }
.section-label {
    display: flex; align-items: center; gap: 7px;
    font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: #555; margin-bottom: .75rem;
}
.section-sub { font-size: .72rem; font-weight: 400; color: #999; text-transform: none; letter-spacing: 0; }
.step-dot {
    width: 20px; height: 20px; background: #6246ea; color: #fff;
    border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
    font-size: .68rem; font-weight: 700; flex-shrink: 0;
}

/* Pets */
.choice-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.choice-btn {
    display: flex; flex-direction: column; padding: 9px 14px;
    border: 2px solid #e8e4f8; border-radius: 10px; background: #fff;
    cursor: pointer; transition: border-color .14s, background .14s; min-width: 90px; text-align: left;
}
.choice-btn:hover  { border-color: #6246ea; background: #f5f3ff; }
.choice-btn--on    { border-color: #6246ea; background: #f0ecff; }
.choice-main { font-size: .88rem; font-weight: 600; color: #222; }
.choice-sub  { font-size: .72rem; color: #888; margin-top: 2px; }

/* Serviços */
.servicos-list { display: flex; flex-direction: column; gap: 7px; }
.servico-btn {
    display: flex; justify-content: space-between; align-items: center; padding: 10px 13px;
    border: 2px solid #e8e4f8; border-radius: 10px; background: #fff;
    cursor: pointer; transition: border-color .14s, background .14s; text-align: left; width: 100%;
}
.servico-btn:hover { border-color: #6246ea; background: #f5f3ff; }
.servico-btn--on   { border-color: #6246ea; background: #f0ecff; }
.servico-nome { font-size: .88rem; font-weight: 500; color: #222; }
.servico-meta { font-size: .75rem; color: #888; white-space: nowrap; margin-left: 8px; }

/* Calendário */
.cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.cal-titulo { font-size: .92rem; font-weight: 700; color: #333; text-transform: capitalize; }
.cal-arrow {
    width: 30px; height: 30px; border: 1px solid #ddd; border-radius: 8px;
    background: #fff; cursor: pointer; font-size: 1.2rem; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    color: #555; transition: background .12s, border-color .12s;
}
.cal-arrow:hover:not(:disabled) { background: #f0ecff; border-color: #6246ea; color: #6246ea; }
.cal-arrow:disabled { opacity: .35; cursor: default; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
.cal-head { text-align: center; font-size: .64rem; font-weight: 700; color: #aaa; text-transform: uppercase; padding: 4px 0 6px; }
.cal-cell {
    aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: .83rem; font-weight: 500;
    border: none; background: transparent; color: #333; cursor: default;
    transition: background .12s, color .12s; position: relative;
}
.cal-cell--vazio      { background: transparent; }
.cal-cell--off        { color: #d0d0d0; }
.cal-cell--disponivel { cursor: pointer; color: #6246ea; font-weight: 700; background: #f0ecff; }
.cal-cell--disponivel:hover { background: #e0d8ff; }
.cal-cell--selected   { background: #6246ea !important; color: #fff !important; font-weight: 700; cursor: pointer; }
.cal-cell--today::after {
    content: ''; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%);
    width: 4px; height: 4px; border-radius: 50%; background: #6246ea;
}

/* Slots */
.slots-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.slot-btn {
    padding: 7px 16px; border: 2px solid #e8e4f8; border-radius: 8px; background: #fff;
    font-size: .88rem; font-weight: 600; color: #444; cursor: pointer;
    transition: border-color .13s, background .13s, color .13s;
}
.slot-btn:hover { border-color: #6246ea; background: #f5f3ff; }
.slot-btn--on   { border-color: #6246ea; background: #6246ea; color: #fff; }
.aviso-vazio { font-size: .82rem; color: #999; padding: 10px 0; }
.aviso-erro  { font-size: .82rem; color: #c0392b; background: #fdf0ef; padding: 8px 12px; border-radius: 8px; }

/* Resumo */
.resumo-box { background: #f8f7ff; border: 1px solid #e8e4f8; border-radius: 10px; padding: 12px 14px; }
.resumo-row { display: flex; gap: 8px; font-size: .83rem; color: #444; padding: 3px 0; }
.resumo-row span:first-child { width: 20px; flex-shrink: 0; }
.btn-confirmar {
    width: 100%; padding: .65rem; background: #6246ea; border: none; border-radius: 10px;
    color: #fff; font-size: .95rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 6px; transition: background .14s;
}
.btn-confirmar:hover:not(:disabled) { background: #4f35d2; }
.btn-confirmar:disabled { opacity: .65; cursor: default; }

/* Sucesso */
.confirm-box { background: #f8f7ff; border: 1px solid #e8e4f8; border-radius: 10px; padding: 14px; text-align: left; }
.confirm-row { display: flex; justify-content: space-between; align-items: baseline; font-size: .83rem; color: #666; padding: 4px 0; border-bottom: 1px solid #f0ecff; }
.confirm-row:last-child { border-bottom: none; }
.confirm-row span   { font-size: .75rem; }
.confirm-row strong { color: #222; }
</style>
