export const CATEGORIAS_PRODUTO = {
    racao: 'Ração',
    racao_umida: 'Ração Úmida',
    medicamento: 'Medicamento',
    acessorio: 'Acessório',
    higiene: 'Higiene',
    petisco: 'Petisco',
};

export const CATEGORIAS_FORNECEDOR = {
    racao: 'Ração',
    medicamento: 'Medicamento',
    acessorio: 'Acessório',
    higiene: 'Higiene',
    petisco: 'Petisco',
    outros: 'Outros',
};

export function isRacao(produto) {
    return produto?.categoria === 'racao';
}

export function fmtMoeda(v) {
    return Number(v || 0).toFixed(2).replace('.', ',');
}

export function fmtGramas(g) {
    const n = Number(g || 0);
    if (Math.abs(n) >= 1000) return (n / 1000).toFixed(3).replace('.', ',').replace(/,?0+$/, '') + ' kg';
    return n + ' g';
}

/** Quantidade formatada respeitando a unidade do produto (gramas para ração). */
export function fmtQtd(produto, valor) {
    return isRacao(produto) ? fmtGramas(valor) : valor;
}

export function estoqueBaixo(produto) {
    return produto && produto.estoque_min !== null && produto.estoque_atual <= produto.estoque_min;
}

export function fmtDataHora(d) {
    return new Date(d).toLocaleString('pt-BR');
}

/** Agenda fn para rodar apos `wait` ms, cancelando a chamada anterior. */
export function debounce(fn, wait = 350) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };
}
