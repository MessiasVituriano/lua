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
- Se o calendario da loja for alterado, o sistema recalcula automaticamente as metas dos dias futuros
- Dias sem funcionamento nao recebem meta diaria
- Dias com excecao de funcionamento podem ser abertos ou fechados manualmente e entram no calculo apenas quando estiverem ativos
- O realizado diario vem do caixa diario ja existente
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
2. O sistema exibe a meta mensal de venda e a meta por saldo
3. O sistema distribui a meta diaria entre os dias de funcionamento cadastrados
4. O realizado diario e buscado automaticamente no caixa diario
5. O dashboard mostra os indicadores, a progress bar e os graficos diarios e mensais
6. Ao editar o calendario ou a meta mensal, os dias futuros sao recalculados automaticamente

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

## 6. Regras Globais

- Todos os valores monetarios usam `decimal(10,2)`
- Datas no formato brasileiro (dd/mm/yyyy) no frontend, ISO no banco
- Soft delete em entidades principais (usuarios, fornecedores, produtos)
- Toda operacao financeira registra o usuario que executou
- Dados sempre filtrados pela loja ativa do usuario logado
- Meses fechados nao permitem alteracao de metas ou do calendario de funcionamento
- O dashboard deve refletir metas de venda e metas por saldo separadamente
