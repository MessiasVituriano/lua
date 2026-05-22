# PRD - LUA: Sistema de Gestao de Fluxo de Caixa para PetShop

**Versao:** 1.0
**Data:** 2026-03-28
**Status:** Em definicao

---

## 1. Visao Geral

Sistema web para gerenciamento do fluxo de caixa de PetShops com suporte a multi-loja. Controla entradas diarias por forma de recebimento, agendamento de pagamentos com recorrencia, fornecedores, produtos com margem e estoque, e oferece dashboard com resumo financeiro.

Este documento tambem cobre a evolucao da operacao de Banho e Tosa, reaproveitando as bases ja existentes de clientes, pets, lojas, calendario de funcionamento, caixa diario e usuarios do projeto atual.

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

### 2.5 Movimentacoes Internas

**Descricao:** Registro de movimentacoes financeiras internas da loja, onde o dinheiro nao sai da empresa. Exemplos: transferencias entre contas/bancos, retiradas de caixa para cofre, aportes de capital, sangrias, transferencias entre lojas. Acessivel pelo menu "Movimentacoes Internas".

**Entidades - Movimentacao Interna:**

| Campo              | Tipo     | Obrigatorio | Observacao                                                        |
|-------------------|----------|-------------|-------------------------------------------------------------------|
| id                | integer  | auto        | PK                                                                |
| loja_id           | integer  | sim         | FK, loja de origem                                                |
| loja_destino_id   | integer  | nao         | FK, preenchido apenas em transferencias entre lojas               |
| tipo              | enum     | sim         | transferencia_banco, sangria, aporte, transferencia_loja          |
| banco_origem_id   | integer  | nao         | FK, banco de onde sai o valor (nao obrigatorio - dinheiro fisico) |
| banco_destino_id  | integer  | nao         | FK, banco para onde vai o valor                                   |
| valor             | decimal  | sim         |                                                                   |
| descricao         | string   | sim         | ex: "Transferencia Nubank para Itau", "Sangria caixa"            |
| data_movimentacao | date     | sim         |                                                                   |
| status            | enum     | sim         | solicitada, aprovada, rejeitada                                   |
| solicitado_por    | integer  | sim         | FK usuario, quem criou a solicitacao                              |
| aprovado_por      | integer  | nao         | FK usuario (admin), quem aprovou/rejeitou                         |
| aprovado_em       | datetime | nao         | data/hora da aprovacao/rejeicao                                   |
| motivo_rejeicao   | string   | nao         | preenchido pelo admin ao rejeitar                                 |
| observacao        | text     | nao         | campo livre                                                       |
| created_at        | datetime | auto        |                                                                   |
| updated_at        | datetime | auto        |                                                                   |

**Regras:**
- Movimentacoes internas **nao afetam** o saldo de entradas vs saidas do fluxo de caixa, pois o dinheiro permanece na empresa
- **Sangria:** subtrai o valor do saldo visivel do caixa diario (sem contar como despesa). O caixa **nao precisa** estar aberto para registrar
- **Aporte:** soma o valor ao saldo visivel do caixa diario
- **Transferencia entre bancos:** `banco_origem_id` pode ser nulo (dinheiro fisico para banco). `banco_destino_id` obrigatorio
- **Transferencia entre lojas:** exige `loja_destino_id`. Na aprovacao, gera automaticamente um registro espelho de entrada na loja destino. A loja destino deve **confirmar** o recebimento
- **Fluxo de aprovacao:** atendente pode **solicitar** qualquer movimentacao. Admin **aprova ou rejeita**. Somente movimentacoes aprovadas tem efeito financeiro
- **Admin pode criar e aprovar** movimentacoes diretamente (status ja nasce como "aprovada")
- Historico consultavel com filtros por tipo, status, periodo e loja

**Funcionalidades:**
- [ ] CRUD de movimentacoes internas (atendente cria com status "solicitada")
- [ ] Tela de aprovacao para admin (lista de movimentacoes pendentes)
- [ ] Acao de aprovar/rejeitar com motivo obrigatorio na rejeicao
- [ ] Filtros: por tipo, status, periodo, banco, loja destino
- [ ] Registro rapido de sangria e aporte a partir da tela do caixa diario
- [ ] Listagem de transferencias entre lojas com confirmacao de recebimento
- [ ] Historico completo de movimentacoes por loja
- [ ] Indicacao visual no caixa diario de sangrias e aportes aprovados no dia
- [ ] Badge no menu com contagem de movimentacoes pendentes de aprovacao (admin)
- [ ] Reflexo no saldo do caixa diario: sangrias subtraem, aportes somam (somente apos aprovacao)
- [ ] Card no dashboard com resumo de movimentacoes internas do mes

---

### 2.6 Fornecedores

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

### 2.7 Produtos e Estoque

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

### 2.8 Dashboard e Relatorios

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
| Meta de Venda             | Percentual atingido, realizado, faltante e media  |
| Meta por Saldo            | Percentual atingido, realizado, faltante e media  |

**Funcionalidades:**
- [ ] Dashboard principal com cards de resumo
- [ ] Filtro por mes/ano e por loja
- [ ] Graficos de entradas por forma de recebimento
- [ ] Graficos de saidas por categoria
- [ ] Lista de pagamentos proximos do vencimento (7 dias)
- [ ] Lista de produtos com estoque baixo
- [ ] Comparativo com mes anterior (percentual de variacao)
- [ ] Cards de meta de venda e meta por saldo
- [ ] Barra de progresso da meta com realizado e restante
- [ ] Grafico dia a dia com meta diaria vs realizado
- [ ] Grafico mensal agrupando dias, saldo e percentual atingido

---

### 2.9 Metas de Venda e Saldo

**Descricao:** Modulo para gerenciamento de metas mensais por loja, com distribuicao diaria, acompanhamento de realizado e comparativos por dia e por mes.

**Objetivo do Modulo:**
- Controlar metas de venda e metas por saldo em base mensal, sempre por loja
- Distribuir automaticamente a meta mensal entre os dias de funcionamento da loja
- Permitir edicao manual da meta mensal e dos valores diarios, com recalculo automatico dos dias futuros
- Permitir ajustar manualmente metas diarias especificas apos o cadastro da meta mensal
- Permitir informar o valor ja vendido no mes para iniciar o comparativo com contexto real
- Exibir o progresso da meta em cards, barras, graficos diarios e consolidados mensais

**Conceitos do Modulo:**

| Conceito             | Definicao |
|---------------------|-----------|
| Meta de Venda       | Meta financeira de receita da loja, usada para medir o faturamento realizado no periodo |
| Meta por Saldo      | Meta do saldo final do caixa, usada para medir o saldo acumulado do periodo |
| Meta Mensal         | Valor total definido para cada mes e para cada tipo de meta |
| Meta Diaria         | Parcela da meta mensal distribuida entre os dias de funcionamento |
| Realizado Diario    | Valor efetivo apurado no caixa diario da loja |
| Saldo Diario        | Saldo do caixa diario do dia, usado na meta por saldo |

**Entidades - Meta Mensal:**

| Campo               | Tipo     | Obrigatorio | Observacao |
|--------------------|----------|-------------|------------|
| id                 | integer  | auto        | PK |
| loja_id            | integer  | sim         | FK |
| tipo               | enum     | sim         | venda, saldo |
| competencia        | date     | sim         | referencia do mes, usando o primeiro dia do mes |
| valor_meta         | decimal  | sim         | valor total da meta no mes |
| valor_realizado_inicial | decimal | nao      | valor ja vendido no mes no momento do cadastro da meta |
| valor_realizado    | decimal  | auto        | soma dos valores realizados do mes |
| valor_restante     | decimal  | auto        | valor_meta - valor_realizado |
| percentual_atingido| decimal  | auto        | percentual de progresso da meta |
| status             | enum     | sim         | aberta, fechada, travada |
| observacao         | text     | nao         | observacoes livres |
| created_at         | datetime | auto        | |
| updated_at         | datetime | auto        | |

**Entidades - Meta Diaria:**

| Campo            | Tipo     | Obrigatorio | Observacao |
|-----------------|----------|-------------|------------|
| id              | integer  | auto        | PK |
| meta_mensal_id  | integer  | sim         | FK |
| data            | date     | sim         | dia de funcionamento |
| valor_meta      | decimal  | sim         | meta do dia |
| valor_realizado | decimal  | auto        | valor apurado no dia |
| saldo_diario    | decimal  | auto        | saldo do caixa diario do dia |
| diferenca       | decimal  | auto        | valor_realizado - valor_meta |
| status          | enum     | sim         | acima, dentro, abaixo |
| created_at      | datetime | auto        | |
| updated_at      | datetime | auto        | |

**Entidades - Calendario de Funcionamento:**

| Campo       | Tipo     | Obrigatorio | Observacao |
|------------|----------|-------------|------------|
| id         | integer  | auto        | PK |
| loja_id    | integer  | sim         | FK |
| dia_semana | enum     | sim         | segunda, terca, quarta, quinta, sexta, sabado, domingo |
| ativa      | boolean  | sim         | define se a loja funciona naquele dia da semana |
| created_at | datetime | auto        | |
| updated_at | datetime | auto        | |

**Entidades - Excecao de Funcionamento:**

| Campo      | Tipo     | Obrigatorio | Observacao |
|-----------|----------|-------------|------------|
| id        | integer  | auto        | PK |
| loja_id   | integer  | sim         | FK |
| data      | date     | sim         | data especifica |
| tipo      | enum     | sim         | aberto, fechado |
| motivo    | string   | nao         | feriado, evento, fechamento pontual |
| created_at| datetime | auto        | |
| updated_at| datetime | auto        | |

**Regras de Negocio:**
- A meta e sempre cadastrada por loja e por mes
- Cada mes possui duas metas independentes: meta de venda e meta por saldo
- A meta mensal pode ser editada, mas a partir do fechamento do mes fica travada
- A meta diaria e distribuida igualmente entre os dias de funcionamento da loja no periodo
- Depois da distribuicao automatica, a meta diaria pode ser editada manualmente por dia
- Ao editar uma meta diaria, o sistema recalcula apenas os dias futuros nao editados manualmente para manter o total da meta mensal
- Se o calendario da loja for alterado, o sistema recalcula automaticamente as metas dos dias futuros
- Dias sem funcionamento nao recebem meta diaria
- Dias com excecao de funcionamento podem ser abertos ou fechados manualmente e entram no calculo apenas quando estiverem ativos
- O realizado diario vem do caixa diario ja existente
- No cadastro da meta mensal, o usuario pode informar o valor ja vendido no mes para iniciar o comparativo
- O valor_realizado considerado no comparativo = valor_realizado_inicial + realizado apurado no caixa diario apos o cadastro
- Na meta de venda, o realizado considera o total de entradas do caixa diario
- Na meta por saldo, o realizado considera o saldo do caixa diario no fim do dia
- Dias sem movimento entram com valor zero no grafico e no acumulado
- Ao editar a meta mensal, os dias passados permanecem preservados e apenas os dias futuros sao recalculados
- O sistema deve mostrar saldo comparado com a meta diaria cadastrada em grafico dia a dia
- O sistema deve mostrar o acompanhamento mes a mes, agrupando os dias, o saldo e o percentual atingido

**Indicadores do Modulo:**

| Indicador                 | Calculo |
|--------------------------|---------|
| Realizado da Venda       | Soma das entradas do caixa diario no mes |
| Meta de Venda            | Meta mensal distribuida pelos dias de funcionamento |
| Realizado do Saldo       | Saldo acumulado do periodo com base nos saldos diarios apurados |
| Meta por Saldo           | Meta mensal distribuida pelos dias de funcionamento |
| Falta para Meta          | Meta mensal - realizado acumulado |
| Media Necessaria por Dia  | Falta para meta / dias uteis restantes |
| Percentual Atingido      | realizado acumulado / meta mensal |

**Funcionalidades:**
- [ ] Cadastro de meta mensal por loja para venda e saldo
- [ ] Edicao da meta mensal com recalculo dos dias futuros
- [ ] Edicao manual da meta diaria por dia apos a distribuicao automatica
- [ ] Campo no cadastro da meta mensal para informar valor ja vendido no mes
- [ ] Cadastro e manutencao do calendario de funcionamento por loja
- [ ] Cadastro de excecoes por data para abertura e fechamento pontual
- [ ] Distribuicao automatica da meta diaria conforme dias de funcionamento
- [ ] Grafico dia a dia comparando realizado, meta diaria e saldo acumulado
- [ ] Progress bar com meta atingida, restante e percentual
- [ ] Card com media de venda necessaria por dia para atingir a meta no mes
- [ ] Acompanhamento mensal com agrupamento dos dias e saldo por periodo
- [ ] Comparativo com meses anteriores
- [ ] Bloqueio de edicao do mes fechado

**Fluxo de Uso:**
1. O usuario seleciona a loja e o mes de referencia
2. O usuario cadastra a meta mensal e pode informar o valor ja vendido no mes
3. O sistema distribui a meta diaria entre os dias de funcionamento cadastrados
4. O usuario pode ajustar manualmente metas diarias especificas
5. O realizado diario e buscado automaticamente no caixa diario e somado ao valor realizado inicial
6. O dashboard mostra os indicadores, a progress bar e os graficos diarios e mensais
7. Ao editar o calendario, a meta mensal ou uma meta diaria, os dias futuros elegiveis sao recalculados automaticamente

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
  |-- 1:N --> movimentacoes_internas --> bancos (origem/destino)
  |                                 --> lojas (destino, em transferencias entre lojas)
  |-- 1:N --> produtos --> fornecedores
  |             |-- 1:N --> movimentacoes_estoque
  |-- 1:N --> metas_mensais --> metas_diarias
  |             |-- 1:N --> calendario_funcionamento
  |             |-- 1:N --> excecoes_funcionamento
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
| 6    | Movimentacoes Internas        | Lojas, Bancos         |
| 7    | Produtos e Estoque            | Lojas, Fornecedores   |
| 8    | Metas de Venda e Saldo        | Fluxo de Caixa, Lojas |
| 9    | Dashboard e Relatorios        | todos os anteriores   |

---

## 6. Banho e Tosa

### 6.1 Objetivo

Organizar a operacao de banho e tosa por loja, com foco em agenda, controle de horarios, catalogo de servicos, precificacao e visibilidade de custos. A funcionalidade deve usar as entidades ja presentes no sistema para evitar duplicidade de cadastro:

- Cliente e Pet ja existentes em `clientes-pets`
- Loja e usuario para definir responsabilidade e acesso
- Calendario de funcionamento e excecoes para limitar horarios disponiveis
- Caixa diario para refletir o faturamento dos atendimentos concluidos
- Pagamentos para custos fixos, custos recorrentes e despesas operacionais

### 6.2 Modulos da Funcionalidade

#### 6.2.1 Agenda de Horarios

**Descricao:** cadastro e gestao dos agendamentos de banho e tosa por loja, com horarios disponiveis por dia, duracao estimada e status operacional.

**Entidades - Agendamento de Banho e Tosa:**

| Campo               | Tipo     | Obrigatorio | Observacao |
|--------------------|----------|-------------|------------|
| id                 | integer  | auto        | PK |
| loja_id            | integer  | sim         | FK para loja |
| cliente_id         | integer  | sim         | FK para cliente |
| pet_id             | integer  | sim         | FK para pet |
| usuario_responsavel_id | integer | nao      | FK usuario que registrou ou atendeu |
| data               | date     | sim         | data do atendimento |
| horario_inicio     | time     | sim         | inicio do bloco |
| horario_fim        | time     | sim         | calculado pela duracao ou informado manualmente |
| duracao_minutos    | integer  | sim         | tempo estimado do atendimento |
| status             | enum     | sim         | solicitado, confirmado, em_andamento, concluido, cancelado, faltou |
| origem             | enum     | nao         | balcao, telefone, whatsapp, online |
| observacao         | text     | nao         | anotacoes gerais |
| valor_estimado     | decimal  | nao         | valor antes da finalizacao |
| valor_final        | decimal  | nao         | valor cobrado na conclusao |
| created_at         | datetime | auto        | |
| updated_at         | datetime | auto        | |

**Regras:**
- O agendamento deve respeitar o calendario de funcionamento da loja e as excecoes cadastradas
- Nao pode existir sobreposicao de horario para a mesma loja em um mesmo intervalo
- O agendamento deve estar vinculado a um pet ativo da mesma loja do usuario logado
- O horario final pode ser recalculado quando o servico ou a duracao forem alterados
- Atendimentos concluidos podem gerar movimentacao no caixa diario da loja ativa

**Funcionalidades:**
- [ ] CRUD de agendamentos
- [ ] Lista diaria com horario, cliente, pet, servico e status
- [ ] Visualizacao em agenda por dia e por semana
- [ ] Reagendamento rapido com verificacao de conflito
- [ ] Cancelamento com motivo
- [ ] Marcacao de no-show / faltou
- [ ] Indicacao visual de status por cor

#### 6.2.2 Catalogo de Servicos

**Descricao:** cadastro dos servicos oferecidos pela loja, com preco base, duracao padrao e custo estimado.

**Entidades - Servico de Banho e Tosa:**

| Campo            | Tipo     | Obrigatorio | Observacao |
|-----------------|----------|-------------|------------|
| id              | integer  | auto        | PK |
| loja_id         | integer  | sim         | FK para loja |
| nome            | string   | sim         | ex: banho simples, banho e tosa, higienizacao |
| categoria       | enum     | sim         | banho, tosa, pacote, extra |
| descricao       | string   | nao         | detalhamento comercial |
| preco_base      | decimal  | sim         | preco sugerido |
| custo_estimado  | decimal  | nao         | custo medio por execucao |
| duracao_minutos | integer  | sim         | tempo padrao para agenda |
| ativo           | boolean  | sim         | default true |
| created_at      | datetime | auto        | |
| updated_at      | datetime | auto        | |

**Regras:**
- O catalogo deve permitir servicos fixos e extras avulsos
- O preco base pode ser ajustado no agendamento final, com registro da alteracao
- A duracao padrao deve ser usada para sugerir o horario fim do atendimento

**Funcionalidades:**
- [ ] CRUD de servicos
- [ ] Listagem para uso no agendamento
- [ ] Ajuste de preco por loja
- [ ] Desativacao de servicos sem perda de historico

#### 6.2.3 Atendimento e Execucao

**Descricao:** acompanhamento do atendimento desde a confirmacao ate a finalizacao, com controle de status, consumo de servicos extras e cobranca final.

**Regras:**
- Somente agendamentos confirmados podem entrar em execucao
- Ao finalizar, o status vai para concluido e o valor final fica travado para auditoria
- O atendente pode adicionar observacoes sobre comportamento do pet, produtos usados e ocorrencias
- Atendimentos cancelados ou faltados nao entram no faturamento do caixa

**Funcionalidades:**
- [ ] Botao de iniciar atendimento
- [ ] Botao de concluir atendimento
- [ ] Registro de observacoes tecnicas e comerciais
- [ ] Inclusao de extras no fechamento do atendimento
- [ ] Historico do pet com ultimos servicos realizados

#### 6.2.4 Custos da Operacao

**Descricao:** controle dos custos vinculados ao banho e tosa, tanto por servico quanto por despesa recorrente da operacao.

**Como o projeto atual pode ser reaproveitado:**
- Custos fixos da operacao podem ser registrados em `Pagamentos` com categoria apropriada
- Custos variaveis por atendimento podem ser estimados no catalogo de servicos
- Custos recorrentes da area podem ser gerados automaticamente via fluxo de pagamentos recorrentes

**Entidades - Custo Operacional de Banho e Tosa:**

| Campo            | Tipo     | Obrigatorio | Observacao |
|-----------------|----------|-------------|------------|
| id              | integer  | auto        | PK |
| loja_id         | integer  | sim         | FK |
| servico_id      | integer  | nao         | FK para servico, quando custo estiver ligado a um servico |
| tipo            | enum     | sim         | fixo, variavel, recorrente, insumo, comissao |
| descricao       | string   | sim         | ex: shampoo, toalhas, comissao tosador |
| valor           | decimal  | sim         | valor do custo |
| data_custo      | date     | sim         | data de registro |
| origem          | enum     | nao         | pagamento, atendimento, manual |
| pagamento_id    | integer  | nao         | FK para pagamento, quando houver |
| observacao      | text     | nao         | campo livre |
| created_at      | datetime | auto        | |
| updated_at      | datetime | auto        | |

**Regras:**
- Custos recorrentes devem ser consolidados por loja e por mes
- Custos por servico devem permitir apurar margem bruta estimada
- Se o custo estiver vinculado a um pagamento ja existente, o sistema nao deve duplicar o lancamento financeiro
- Comissoes de profissionais podem ser calculadas por percentual ou valor fixo por servico

**Funcionalidades:**
- [ ] Cadastro de custos operacionais
- [ ] Vinculo entre custo e servico executado
- [ ] Vinculo entre custo e pagamento ja existente
- [ ] Resumo mensal de custo total, ticket medio e margem estimada
- [ ] Relatorio de custo por tipo de servico

### 6.3 Integracoes com o que ja existe

- `clientes-pets` ja resolve o cadastro de cliente e pet, entao o novo modulo deve reutilizar esses registros sem duplicar dados
- `calendario_funcionamento` e `excecoes_funcionamento` ja definem a disponibilidade da loja e podem ser usados para bloquear horarios indisponiveis
- `caixa/hoje` ja registra entradas, entao atendimentos concluidos podem ser contabilizados como receita operacional da loja
- `pagamentos` ja cobre despesas, fornecedores e recorrencia, entao a estrutura de custos pode reaproveitar essa base
- `usuarios` e `lojas` ja existem, entao o agendamento pode ser segmentado por loja e responsavel

### 6.4 Regras de Negocio do Banho e Tosa

- A agenda sempre pertence a uma loja especifica
- Um pet nao pode ter dois agendamentos concorrentes no mesmo intervalo
- A loja so pode abrir horarios dentro do calendario de funcionamento ativo
- Excecoes de funcionamento com tipo fechado bloqueiam a agenda naquele dia
- O status de concluido deve ser a unica condicao para faturamento no caixa
- O sistema deve manter historico de alteracao de valor final, reagendamento e cancelamento
- O usuario deve conseguir localizar rapidamente agenda por cliente, pet, data, status e servico
- O historico do pet deve mostrar frequencia de retorno, servicos mais usados e ultimo atendimento

### 6.5 Funcionalidades do MVP

- [ ] Cadastro de servicos de banho e tosa
- [ ] Agenda diaria com horarios disponiveis por loja
- [ ] Cadastro e edicao de agendamentos vinculados a cliente e pet
- [ ] Controle de status do atendimento
- [ ] Integracao com calendario de funcionamento
- [ ] Registro de valor estimado e valor final
- [ ] Relatorio simples de faturamento por periodo
- [ ] Relatorio de custos por servico e por mes
- [ ] Pesquisa por cliente, pet, servico e data

---

## 7. Regras Globais

- Todos os valores monetarios usam `decimal(10,2)`
- Datas no formato brasileiro (dd/mm/yyyy) no frontend, ISO no banco
- Soft delete em entidades principais (usuarios, fornecedores, produtos)
- Toda operacao financeira registra o usuario que executou
- Dados sempre filtrados pela loja ativa do usuario logado
- Meses fechados nao permitem alteracao de metas ou do calendario de funcionamento
- O dashboard deve refletir metas de venda e metas por saldo separadamente

---

## 8. Backlog Tecnico - Banho e Tosa

### 8.1 Objetivo do Backlog

Transformar a visao funcional de Banho e Tosa em entregas tecnicas implementaveis, com foco em reutilizar o que o projeto ja possui:

- Cadastro de cliente e pet via `clientes-pets`
- Loja ativa do usuario para isolamento por unidade
- Calendario de funcionamento e excecoes para bloqueio de horarios
- Caixa diario para refletir faturamento de atendimentos concluidos
- Pagamentos para custos, recorrencias e despesas operacionais

### 8.2 Epicos Tecnicos

| Epic | Entrega | Dependencias | Prioridade |
|------|---------|--------------|------------|
| BT-01 | Base de dados e modelos | Cliente, Pet, Loja, calendario_funcionamento | Alta |
| BT-02 | Servicos de banho e tosa | BT-01 | Alta |
| BT-03 | Agenda e conflitos de horario | BT-01, BT-02, calendario_funcionamento, excecoes_funcionamento | Alta |
| BT-04 | Fluxo de atendimento | BT-03 | Alta |
| BT-05 | Cobranca e reflexo financeiro | BT-04, CaixaDiario, EntradaCaixa | Alta |
| BT-06 | Custos operacionais e margem | BT-02, Pagamento | Media |
| BT-07 | Relatorios e dashboard | BT-04, BT-05, BT-06 | Media |
| BT-08 | Permissoes e UX operacional | Auth, usuarios, lojas | Media |

### 8.3 Telas

#### 8.3.1 Tela de Agenda

**Objetivo:** visualizar a operacao do dia por loja, com horarios livres, agendados e em atendimento.

**Componentes:**
- Seletor de data
- Filtro por loja, status, cliente, pet e servico
- Timeline ou grade de horarios
- Cards de agendamento com status e acoes rapidas
- Indicador de horarios bloqueados por funcionamento ou excecao

**Acoes:**
- Criar agendamento
- Reagendar
- Confirmar
- Iniciar atendimento
- Concluir atendimento
- Cancelar atendimento

#### 8.3.2 Tela de Cadastro de Servicos

**Objetivo:** gerenciar catalogo de servicos com preco, duracao e custo estimado.

**Componentes:**
- Lista de servicos
- Formulario de criacao e edicao
- Filtro por ativo/inativo
- Indicador de margem estimada por servico

**Acoes:**
- Criar servico
- Editar servico
- Desativar servico
- Consultar servicos usados com mais frequencia

#### 8.3.3 Tela de Cadastro de Agendamento

**Objetivo:** registrar o atendimento de um cliente e seu pet em um horario valido.

**Componentes:**
- Busca de cliente existente
- Busca de pet do cliente
- Selecao de servicos
- Horario inicio e duracao
- Valor estimado
- Observacoes

**Acoes:**
- Salvar agendamento
- Salvar e confirmar
- Verificar conflito de horario

#### 8.3.4 Tela de Atendimento

**Objetivo:** controlar a execucao do atendimento ate a conclusao.

**Componentes:**
- Dados do cliente e pet
- Servicos selecionados
- Observacoes tecnicas
- Extras adicionados no atendimento
- Valor final
- Status atual

**Acoes:**
- Iniciar
- Pausar, se necessario
- Concluir
- Cancelar
- Marcar como faltou

#### 8.3.5 Tela de Custos e Rentabilidade

**Objetivo:** consolidar custos operacionais e estimar margem por servico e por periodo.

**Componentes:**
- Lista de custos
- Filtros por periodo, tipo e servico
- Total de custos do mes
- Ticket medio
- Margem estimada
- Custos por atendimento e custos recorrentes

**Acoes:**
- Registrar custo manual
- Vincular custo a pagamento existente
- Consultar fechamento mensal

#### 8.3.6 Tela de Historico do Pet

**Objetivo:** exibir o historico de atendimentos de cada pet.

**Componentes:**
- Linha do tempo de atendimentos
- Ultimos servicos realizados
- Frequencia de visitas
- Observacoes relevantes

**Acoes:**
- Abrir agendamento anterior
- Repetir servico anterior
- Criar novo agendamento a partir do historico

### 8.4 Endpoints

#### 8.4.1 Reaproveitados do projeto atual

- `GET /clientes-pets` - lista pets do usuario logado
- `POST /clientes-pets` - cria cliente e pet juntos quando necessario
- `GET /clientes-pets/clientes-list` - lista clientes ativos da loja
- `GET /lojas/{loja}/calendario` - consulta dias ativos da loja
- `POST /lojas/{loja}/calendario` - salva calendario de funcionamento
- `GET /metas` - consulta configuracoes de calendario e excecoes para referencia operacional
- `GET /caixa/hoje` - contexto do caixa diario para lancamento de receita
- `POST /caixa/{caixa}/entrada` - registra entrada quando o atendimento for pago
- `GET /pagamentos` - consulta custos e despesas ja cadastrados
- `POST /pagamentos` - cria custo recorrente ou despesa operacional

#### 8.4.2 Novos endpoints sugeridos

**Servicos**
- `GET /banho-tosa/servicos`
- `GET /banho-tosa/servicos/{servico}`
- `POST /banho-tosa/servicos`
- `PUT /banho-tosa/servicos/{servico}`
- `DELETE /banho-tosa/servicos/{servico}`

**Agendamentos**
- `GET /banho-tosa/agendamentos`
- `GET /banho-tosa/agendamentos/{agendamento}`
- `POST /banho-tosa/agendamentos`
- `PUT /banho-tosa/agendamentos/{agendamento}`
- `DELETE /banho-tosa/agendamentos/{agendamento}`
- `POST /banho-tosa/agendamentos/{agendamento}/confirmar`
- `POST /banho-tosa/agendamentos/{agendamento}/iniciar`
- `POST /banho-tosa/agendamentos/{agendamento}/concluir`
- `POST /banho-tosa/agendamentos/{agendamento}/cancelar`
- `POST /banho-tosa/agendamentos/{agendamento}/faltou`
- `GET /banho-tosa/agenda/diaria`
- `GET /banho-tosa/agenda/semanal`

**Atendimento e historico**
- `GET /banho-tosa/pets/{pet}/historico`
- `GET /banho-tosa/clientes/{cliente}/agendamentos`
- `GET /banho-tosa/agendamentos/{agendamento}/resumo`

**Custos e rentabilidade**
- `GET /banho-tosa/custos`
- `POST /banho-tosa/custos`
- `PUT /banho-tosa/custos/{custo}`
- `DELETE /banho-tosa/custos/{custo}`
- `GET /banho-tosa/relatorios/rentabilidade`
- `GET /banho-tosa/relatorios/agenda`
- `GET /banho-tosa/relatorios/historico-pet`

### 8.5 Ordem Sugerida de Implementacao

1. Modelos e migrations de servicos, agendamentos e custos
2. Regras de conflito de horario e disponibilidade por calendario
3. CRUD de servicos
4. CRUD de agendamentos
5. Fluxo de atendimento com confirmacao, inicio e conclusao
6. Integracao com caixa diario e pagamentos
7. Telas de agenda e atendimento
8. Historico do pet e relatorios de rentabilidade
