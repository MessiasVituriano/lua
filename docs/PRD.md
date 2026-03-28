# PRD - LUA: Sistema de Gestao de Fluxo de Caixa para PetShop

**Versao:** 1.0
**Data:** 2026-03-28
**Status:** Em definicao

---

## 1. Visao Geral

Sistema web para gerenciamento do fluxo de caixa de PetShops com suporte a multi-loja. Controla entradas diarias por forma de recebimento, agendamento de pagamentos com recorrencia, fornecedores, produtos com margem e estoque, e oferece dashboard com resumo financeiro.

---

## 2. Modulos do Sistema

### 2.1 Autenticacao e Usuarios

**Descricao:** Cadastro e login de usuarios vinculados a uma ou mais lojas.

**Entidades:**

| Campo        | Tipo     | Obrigatorio | Observacao                  |
|-------------|----------|-------------|-----------------------------|
| id          | integer  | auto        | PK                          |
| nome        | string   | sim         |                             |
| email       | string   | sim         | unico, usado no login       |
| senha       | string   | sim         | hash bcrypt                 |
| loja_id     | integer  | sim         | FK para loja ativa          |
| ativo       | boolean  | sim         | default true                |
| created_at  | datetime | auto        |                             |
| updated_at  | datetime | auto        |                             |

**Regras:**
- Todo usuario tem acesso total ao sistema (sem niveis de permissao nesta versao)
- Usuario pode alternar entre lojas que tem acesso
- Relacao N:N entre usuarios e lojas (tabela pivot `usuario_loja`)

**Funcionalidades:**
- [ ] Tela de login (email + senha)
- [ ] CRUD de usuarios
- [ ] Seletor de loja ativa no header do sistema

---

### 2.2 Lojas

**Descricao:** Cadastro de unidades do PetShop.

**Entidades:**

| Campo       | Tipo     | Obrigatorio | Observacao |
|------------|----------|-------------|------------|
| id         | integer  | auto        | PK         |
| nome       | string   | sim         |            |
| endereco   | string   | nao         |            |
| telefone   | string   | nao         |            |
| ativa      | boolean  | sim         | default true |
| created_at | datetime | auto        |            |
| updated_at | datetime | auto        |            |

**Regras:**
- Todos os dados financeiros (caixa, pagamentos, estoque) sao separados por loja
- Uma loja pode ter multiplos usuarios

**Funcionalidades:**
- [ ] CRUD de lojas
- [ ] Vincular/desvincular usuarios a lojas

---

### 2.3 Fluxo de Caixa (Entrada)

**Descricao:** Registro diario das entradas de dinheiro por forma de recebimento. Preenchimento manual ao longo do dia, com fechamento ao final.

**Entidades - Caixa Diario:**

| Campo            | Tipo     | Obrigatorio | Observacao                    |
|-----------------|----------|-------------|-------------------------------|
| id              | integer  | auto        | PK                            |
| loja_id         | integer  | sim         | FK                            |
| data            | date     | sim         | unica por loja                |
| status          | enum     | sim         | aberto, fechado               |
| total_entradas  | decimal  | auto        | soma das entradas             |
| total_saidas    | decimal  | auto        | soma dos pagamentos do dia    |
| saldo           | decimal  | auto        | entradas - saidas             |
| fechado_por     | integer  | nao         | FK usuario, preenchido ao fechar |
| fechado_em      | datetime | nao         |                               |
| created_at      | datetime | auto        |                               |
| updated_at      | datetime | auto        |                               |

**Entidades - Entradas do Caixa:**

| Campo              | Tipo     | Obrigatorio | Observacao                                        |
|-------------------|----------|-------------|---------------------------------------------------|
| id                | integer  | auto        | PK                                                |
| caixa_diario_id   | integer  | sim         | FK                                                |
| forma_recebimento | enum     | sim         | dinheiro, pix, cartao_debito, cartao_credito      |
| banco_id          | integer  | nao         | FK, obrigatorio se forma != dinheiro              |
| valor             | decimal  | sim         |                                                   |
| descricao         | string   | nao         | observacao livre                                  |
| created_at        | datetime | auto        |                                                   |
| updated_at        | datetime | auto        |                                                   |

**Entidades - Bancos:**

| Campo      | Tipo     | Obrigatorio | Observacao        |
|-----------|----------|-------------|-------------------|
| id        | integer  | auto        | PK                |
| nome      | string   | sim         | ex: Nubank, Itau  |
| ativo     | boolean  | sim         | default true      |

**Regras:**
- Maximo de 5 bancos cadastrados no sistema
- Ao abrir o caixa de um dia, o status inicia como "aberto"
- Ao fechar, registra quem fechou e o horario. Nao gera documento
- Caixa fechado nao pode ser editado (somente admin poderia reabrir em versao futura)
- O total do dia e calculado automaticamente somando todas as entradas por forma de recebimento
- Historico de fechamentos consultavel com filtros por periodo (dia, semana, mes, intervalo customizado)

**Funcionalidades:**
- [ ] Tela de abertura de caixa do dia
- [ ] Formulario para adicionar entradas (forma de recebimento + banco + valor + descricao)
- [ ] Listagem das entradas do dia com totais por forma de recebimento
- [ ] Botao de fechamento do caixa (confirma com modal)
- [ ] Tela de historico de caixas com filtros por periodo
- [ ] Visualizacao detalhada de um caixa fechado (somente leitura)

---

### 2.4 Agendamento de Pagamentos (Saida)

**Descricao:** Cadastro e controle de todos os pagamentos da loja, com suporte a recorrencia mensal para custos fixos.

**Entidades - Pagamento:**

| Campo            | Tipo     | Obrigatorio | Observacao                                                         |
|-----------------|----------|-------------|--------------------------------------------------------------------|
| id              | integer  | auto        | PK                                                                 |
| loja_id         | integer  | sim         | FK                                                                 |
| fornecedor_id   | integer  | nao         | FK, nullable (nem todo pagamento tem fornecedor)                   |
| categoria       | enum     | sim         | boleto, imposto, custo_fixo, funcionario, fornecedor, outros       |
| descricao       | string   | sim         | ex: "Aluguel março", "FGTS", "Ração Premier 15kg"                 |
| valor_total     | decimal  | sim         |                                                                    |
| valor_pago      | decimal  | sim         | default 0, atualizado em pagamentos parciais                      |
| data_vencimento | date     | sim         |                                                                    |
| data_pagamento  | date     | nao         | preenchido ao marcar como pago                                     |
| forma_pagamento | enum     | nao         | dinheiro, pix, boleto, transferencia                               |
| banco_id        | integer  | nao         | FK, banco utilizado no pagamento                                   |
| status          | enum     | sim         | pendente, pago, atrasado, parcial                                  |
| observacao      | text     | nao         | campo livre                                                        |
| recorrente      | boolean  | sim         | default false                                                      |
| dia_recorrencia | integer  | nao         | 1-31, dia do mes para gerar automaticamente                       |
| created_at      | datetime | auto        |                                                                    |
| updated_at      | datetime | auto        |                                                                    |

**Regras:**
- **Status automatico:** se `data_vencimento < hoje` e status = "pendente", sistema marca como "atrasado" automaticamente
- **Pagamento parcial:** ao registrar um valor menor que `valor_total`, status vai para "parcial" e `valor_pago` e atualizado
- **Recorrencia:** pagamentos com `recorrente = true` geram automaticamente um novo registro no inicio de cada mes, no `dia_recorrencia` definido. O pagamento gerado herda categoria, descricao, valor e fornecedor
- **Vinculo com caixa:** pagamentos marcados como "pago" no dia do caixa aberto sao contabilizados como saida no fechamento
- **Alerta visual:** pagamentos com vencimento nos proximos 7 dias aparecem destacados (amarelo para proximos, vermelho para atrasados)

**Funcionalidades:**
- [ ] CRUD de pagamentos
- [ ] Filtros: por status, categoria, periodo, fornecedor
- [ ] Tela de calendario/lista de pagamentos do mes
- [ ] Acao de "registrar pagamento" (define forma, banco, valor pago, data)
- [ ] Badge/alerta no menu com contagem de pagamentos vencendo em 7 dias
- [ ] Indicadores visuais: amarelo (vence em 7 dias), vermelho (atrasado)
- [ ] Job automatico para gerar pagamentos recorrentes no inicio do mes

---

### 2.5 Fornecedores

**Descricao:** Cadastro de fornecedores vinculados aos pagamentos e produtos.

**Entidades:**

| Campo      | Tipo     | Obrigatorio | Observacao                                        |
|-----------|----------|-------------|---------------------------------------------------|
| id        | integer  | auto        | PK                                                |
| nome      | string   | sim         |                                                   |
| categoria | enum     | sim         | racao, medicamento, acessorio, higiene, outros     |
| telefone  | string   | nao         |                                                   |
| ativo     | boolean  | sim         | default true                                      |
| created_at| datetime | auto        |                                                   |
| updated_at| datetime | auto        |                                                   |

**Regras:**
- Fornecedor pode ser vinculado a pagamentos e a produtos
- Ao consultar fornecedor, exibir historico de pagamentos feitos a ele com totais por periodo

**Funcionalidades:**
- [ ] CRUD de fornecedores
- [ ] Tela de detalhe do fornecedor com:
  - Dados cadastrais
  - Lista de pagamentos vinculados (com filtro por periodo)
  - Total pago no periodo selecionado
  - Produtos fornecidos

---

### 2.6 Produtos e Estoque

**Descricao:** Cadastro de produtos com controle de custo, venda, margem por produto e estoque com entrada/saida.

**Entidades - Produto:**

| Campo          | Tipo     | Obrigatorio | Observacao                                    |
|---------------|----------|-------------|-----------------------------------------------|
| id            | integer  | auto        | PK                                            |
| loja_id       | integer  | sim         | FK                                            |
| fornecedor_id | integer  | nao         | FK                                            |
| nome          | string   | sim         |                                               |
| categoria     | enum     | sim         | racao, medicamento, acessorio, higiene        |
| valor_custo   | decimal  | sim         |                                               |
| margem        | decimal  | sim         | percentual, ex: 30.00                         |
| valor_venda   | decimal  | auto        | calculado: valor_custo * (1 + margem/100)     |
| estoque_atual | integer  | sim         | default 0                                     |
| estoque_min   | integer  | nao         | alerta quando estoque_atual <= estoque_min    |
| ativo         | boolean  | sim         | default true                                  |
| created_at    | datetime | auto        |                                               |
| updated_at    | datetime | auto        |                                               |

**Entidades - Movimentacao de Estoque:**

| Campo       | Tipo     | Obrigatorio | Observacao                     |
|------------|----------|-------------|--------------------------------|
| id         | integer  | auto        | PK                             |
| produto_id | integer  | sim         | FK                             |
| tipo       | enum     | sim         | entrada, saida                 |
| quantidade | integer  | sim         |                                |
| motivo     | string   | nao         | ex: "compra fornecedor", "venda", "perda" |
| usuario_id | integer  | sim         | FK, quem registrou             |
| created_at | datetime | auto        |                                |

**Regras:**
- `valor_venda` e sempre calculado: `valor_custo * (1 + margem / 100)`. Ao alterar custo ou margem, o valor de venda atualiza automaticamente
- A margem e definida individualmente por produto
- Cada movimentacao de estoque (entrada ou saida) atualiza o `estoque_atual` do produto
- Categorias fixas: racao, medicamento, acessorio, higiene
- Estoque e separado por loja

**Funcionalidades:**
- [ ] CRUD de produtos
- [ ] Calculo automatico de valor de venda ao preencher custo e margem
- [ ] Filtros: por categoria, fornecedor, faixa de preco
- [ ] Registro de entrada de estoque (quantidade + motivo)
- [ ] Registro de saida de estoque (quantidade + motivo)
- [ ] Historico de movimentacoes por produto
- [ ] Alerta visual para produtos com estoque abaixo do minimo

---

### 2.7 Dashboard e Relatorios

**Descricao:** Painel com resumo financeiro mensal e indicadores-chave.

**Indicadores do Dashboard:**

| Indicador                  | Calculo                                          |
|---------------------------|--------------------------------------------------|
| Total de Entradas (mes)   | Soma de todas as entradas dos caixas do mes       |
| Total de Saidas (mes)     | Soma de todos os pagamentos pagos no mes          |
| Saldo do Mes              | Entradas - Saidas                                 |
| Maiores Despesas          | Top 5 pagamentos por valor no mes                 |
| Entradas por Forma        | Grafico pizza: dinheiro, pix, debito, credito     |
| Saidas por Categoria      | Grafico pizza: boleto, imposto, fixo, funcionario |
| Pagamentos Pendentes      | Contagem e valor total de pendentes + atrasados   |
| Produtos com Estoque Baixo| Lista de produtos com estoque <= estoque_min      |

**Funcionalidades:**
- [ ] Dashboard principal com cards de resumo
- [ ] Filtro por mes/ano e por loja
- [ ] Graficos de entradas por forma de recebimento
- [ ] Graficos de saidas por categoria
- [ ] Lista de pagamentos proximos do vencimento (7 dias)
- [ ] Lista de produtos com estoque baixo
- [ ] Comparativo com mes anterior (percentual de variacao)

---

## 3. Stack Tecnica

| Camada     | Tecnologia              |
|-----------|-------------------------|
| Backend   | Laravel 10 (PHP 8.2)    |
| Frontend  | Blade + Livewire ou Vue |
| Banco     | PostgreSQL 16           |
| Infra     | Docker (ja configurado) |
| Auth      | Laravel Sanctum         |

---

## 4. Diagrama de Relacionamentos (Resumo)

```
lojas
  |-- 1:N --> caixas_diarios
  |             |-- 1:N --> entradas_caixa --> bancos
  |-- 1:N --> pagamentos --> fornecedores
  |                      --> bancos
  |-- 1:N --> produtos --> fornecedores
  |             |-- 1:N --> movimentacoes_estoque
  |-- N:N --> usuarios (pivot: usuario_loja)

bancos (tabela global, max 5 registros)
```

---

## 5. Ordem de Implementacao Sugerida

| Fase | Modulo                        | Dependencias          |
|------|-------------------------------|-----------------------|
| 1    | Lojas + Usuarios + Auth       | nenhuma               |
| 2    | Bancos                        | nenhuma               |
| 3    | Fornecedores                  | nenhuma               |
| 4    | Fluxo de Caixa (Entrada)      | Lojas, Bancos         |
| 5    | Agendamento de Pagamentos     | Lojas, Bancos, Fornecedores |
| 6    | Produtos e Estoque            | Lojas, Fornecedores   |
| 7    | Dashboard e Relatorios        | todos os anteriores   |

---

## 6. Regras Globais

- Todos os valores monetarios usam `decimal(10,2)`
- Datas no formato brasileiro (dd/mm/yyyy) no frontend, ISO no banco
- Soft delete em entidades principais (usuarios, fornecedores, produtos)
- Toda operacao financeira registra o usuario que executou
- Dados sempre filtrados pela loja ativa do usuario logado
