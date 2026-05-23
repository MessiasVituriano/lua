<?php

namespace Tests\Feature;

use App\Models\Banco;
use App\Models\Fornecedor;
use App\Models\Loja;
use App\Models\MovimentacaoEstoque;
use App\Models\Pagamento;
use App\Models\PedidoCompra;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PedidoCompraTest extends TestCase
{
    use RefreshDatabase;

    private Loja $loja;
    private User $admin;
    private Fornecedor $fornecedor;
    private Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loja = Loja::create(['nome' => 'Loja Teste', 'ativa' => true]);

        $this->admin = User::factory()->create([
            'loja_id' => $this->loja->id,
            'role' => 'admin',
            'ativo' => true,
        ]);

        $this->fornecedor = Fornecedor::create([
            'nome' => 'Fornecedor Teste',
            'categoria' => 'racao',
            'ativo' => true,
        ]);

        $this->produto = Produto::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'nome' => 'Ração Premium 15kg',
            'categoria' => 'racao',
            'valor_custo' => 80.00,
            'margem' => 25,
            'valor_venda' => 100.00,
            'estoque_atual' => 10,
            'ativo' => true,
        ]);

        Sanctum::actingAs($this->admin);
    }

    private function payloadValido(array $overrides = []): array
    {
        return array_merge([
            'fornecedor_id' => $this->fornecedor->id,
            'data_estimativa_entrega' => now()->addDays(5)->toDateString(),
            'itens' => [
                [
                    'produto_id' => $this->produto->id,
                    'quantidade' => 3,
                    'valor_unitario' => 80.00,
                ],
            ],
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // CRIAÇÃO
    // -----------------------------------------------------------------------

    public function test_cria_pedido_com_status_pendente(): void
    {
        $response = $this->postJson('/api/pedidos-compra', $this->payloadValido());

        $response->assertCreated()
            ->assertJsonPath('status', 'pendente')
            ->assertJsonPath('valor_total', '240.00');

        $this->assertDatabaseHas('pedidos_compra', [
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'status' => 'pendente',
            'valor_total' => 240.00,
        ]);

        $this->assertDatabaseHas('pedido_compra_itens', [
            'produto_id' => $this->produto->id,
            'quantidade' => 3,
            'valor_unitario' => 80.00,
            'valor_total' => 240.00,
        ]);
    }

    public function test_cria_pedido_usa_valor_custo_do_produto_quando_valor_unitario_omitido(): void
    {
        $payload = $this->payloadValido();
        unset($payload['itens'][0]['valor_unitario']);

        $response = $this->postJson('/api/pedidos-compra', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('pedido_compra_itens', [
            'produto_id' => $this->produto->id,
            'valor_unitario' => 80.00,
        ]);
    }

    public function test_cria_pedido_com_multiplos_itens_calcula_valor_total_correto(): void
    {
        $produto2 = Produto::create([
            'loja_id' => $this->loja->id,
            'nome' => 'Shampoo Pet',
            'categoria' => 'higiene',
            'valor_custo' => 20.00,
            'margem' => 30,
            'valor_venda' => 26.00,
            'estoque_atual' => 50,
            'ativo' => true,
        ]);

        $payload = $this->payloadValido([
            'itens' => [
                ['produto_id' => $this->produto->id, 'quantidade' => 2, 'valor_unitario' => 80.00],
                ['produto_id' => $produto2->id, 'quantidade' => 5, 'valor_unitario' => 20.00],
            ],
        ]);

        $response = $this->postJson('/api/pedidos-compra', $payload);

        // 2*80 + 5*20 = 160 + 100 = 260
        $response->assertCreated()
            ->assertJsonPath('valor_total', '260.00');
    }

    // -----------------------------------------------------------------------
    // VALIDAÇÃO (campos obrigatórios)
    // -----------------------------------------------------------------------

    public function test_rejeita_criacao_sem_fornecedor_id(): void
    {
        $payload = $this->payloadValido();
        unset($payload['fornecedor_id']);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fornecedor_id']);
    }

    public function test_rejeita_criacao_sem_data_estimativa_entrega(): void
    {
        $payload = $this->payloadValido();
        unset($payload['data_estimativa_entrega']);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data_estimativa_entrega']);
    }

    public function test_rejeita_criacao_sem_itens(): void
    {
        $payload = $this->payloadValido(['itens' => []]);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['itens']);
    }

    public function test_rejeita_item_sem_produto_id(): void
    {
        $payload = $this->payloadValido([
            'itens' => [['quantidade' => 2, 'valor_unitario' => 80]],
        ]);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.produto_id']);
    }

    public function test_rejeita_item_com_quantidade_zero(): void
    {
        $payload = $this->payloadValido([
            'itens' => [['produto_id' => $this->produto->id, 'quantidade' => 0]],
        ]);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.quantidade']);
    }

    public function test_rejeita_fornecedor_inexistente(): void
    {
        $payload = $this->payloadValido(['fornecedor_id' => 99999]);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fornecedor_id']);
    }

    public function test_rejeita_produto_inexistente_nos_itens(): void
    {
        $payload = $this->payloadValido([
            'itens' => [['produto_id' => 99999, 'quantidade' => 1]],
        ]);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.produto_id']);
    }

    public function test_rejeita_forma_pagamento_invalida(): void
    {
        $payload = $this->payloadValido(['forma_pagamento' => 'cartao_credito']);

        $this->postJson('/api/pedidos-compra', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['forma_pagamento']);
    }

    // -----------------------------------------------------------------------
    // ACESSO (autenticação / autorização)
    // -----------------------------------------------------------------------

    public function test_nao_autenticado_nao_acessa_pedidos(): void
    {
        // Descarta o guard configurado no setUp
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/pedidos-compra');

        $response->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // LISTAGEM
    // -----------------------------------------------------------------------

    public function test_lista_apenas_pedidos_da_loja_do_usuario(): void
    {
        $outraLoja = Loja::create(['nome' => 'Outra Loja', 'ativa' => true]);
        PedidoCompra::create([
            'loja_id' => $outraLoja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'pendente',
            'data_estimativa_entrega' => now()->addDays(3)->toDateString(),
            'valor_total' => 100,
        ]);

        PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'pendente',
            'data_estimativa_entrega' => now()->addDays(3)->toDateString(),
            'valor_total' => 200,
        ]);

        $response = $this->getJson('/api/pedidos-compra');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filtra_pedidos_por_status(): void
    {
        PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'pendente',
            'data_estimativa_entrega' => now()->addDays(2)->toDateString(),
            'valor_total' => 100,
        ]);

        PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'confirmado',
            'data_estimativa_entrega' => now()->addDays(2)->toDateString(),
            'valor_total' => 200,
        ]);

        $response = $this->getJson('/api/pedidos-compra?status=pendente');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('pendente', $response->json('data.0.status'));
    }

    public function test_indica_pedido_atrasado_na_listagem(): void
    {
        PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'confirmado',
            'data_estimativa_entrega' => now()->subDays(2)->toDateString(),
            'valor_total' => 300,
        ]);

        $response = $this->getJson('/api/pedidos-compra');

        $response->assertOk();
        $this->assertTrue($response->json('data.0.atrasado'));
        $this->assertEquals(1, $response->json('total_atrasados'));
    }

    // -----------------------------------------------------------------------
    // EDIÇÃO
    // -----------------------------------------------------------------------

    public function test_edita_pedido_pendente(): void
    {
        $pedido = PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'pendente',
            'data_estimativa_entrega' => now()->addDays(5)->toDateString(),
            'valor_total' => 240,
        ]);

        $novaData = now()->addDays(10)->toDateString();

        $this->putJson("/api/pedidos-compra/{$pedido->id}", $this->payloadValido([
            'data_estimativa_entrega' => $novaData,
            'itens' => [['produto_id' => $this->produto->id, 'quantidade' => 1, 'valor_unitario' => 80]],
        ]))->assertOk()->assertJsonPath('valor_total', '80.00');

        $this->assertDatabaseHas('pedidos_compra', [
            'id' => $pedido->id,
            'data_estimativa_entrega' => $novaData,
            'valor_total' => 80.00,
        ]);
    }

    public function test_nao_permite_editar_pedido_confirmado(): void
    {
        $pedido = PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'confirmado',
            'data_estimativa_entrega' => now()->addDays(3)->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'valor_total' => 80,
        ]);

        $this->putJson("/api/pedidos-compra/{$pedido->id}", $this->payloadValido())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Somente pedidos pendentes podem ser editados.');
    }

    // -----------------------------------------------------------------------
    // CONFIRMAÇÃO
    // -----------------------------------------------------------------------

    public function test_confirma_pedido_e_gera_pagamento_unico(): void
    {
        $pedido = $this->postJson('/api/pedidos-compra', $this->payloadValido([
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'forma_pagamento' => 'pix',
        ]))->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar")
            ->assertOk()
            ->assertJsonPath('status', 'confirmado');

        $this->assertDatabaseHas('pagamentos', [
            'pedido_compra_id' => $pedido['id'],
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'categoria' => 'fornecedor',
            'valor_total' => 240.00,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseCount('pagamentos', 1);
    }

    public function test_confirma_pedido_e_gera_parcelas_corretamente(): void
    {
        $pedido = $this->postJson('/api/pedidos-compra', $this->payloadValido([
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'quantidade_parcelas' => 3,
            'recorrencia_dias' => 30,
        ]))->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar")
            ->assertOk();

        // 240 / 3 = 80 por parcela
        $this->assertDatabaseCount('pagamentos', 3);
        $this->assertEquals(
            240.00,
            Pagamento::where('pedido_compra_id', $pedido['id'])->sum('valor_total')
        );
    }

    public function test_nao_permite_confirmar_sem_data_vencimento(): void
    {
        $pedido = $this->postJson('/api/pedidos-compra', $this->payloadValido())
            ->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Informe a data de vencimento do pagamento antes de confirmar.');
    }

    public function test_nao_permite_confirmar_pedido_ja_confirmado(): void
    {
        $pedido = PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'confirmado',
            'data_estimativa_entrega' => now()->addDays(3)->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'valor_total' => 80,
        ]);

        $this->postJson("/api/pedidos-compra/{$pedido->id}/confirmar")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Somente pedidos pendentes podem ser confirmados.');
    }

    // -----------------------------------------------------------------------
    // CONFIRMAÇÃO DE ENTREGA
    // -----------------------------------------------------------------------

    public function test_confirmar_entrega_atualiza_status_e_incrementa_estoque(): void
    {
        $estoqueInicial = $this->produto->estoque_atual;

        $pedido = $this->postJson('/api/pedidos-compra', $this->payloadValido([
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'itens' => [['produto_id' => $this->produto->id, 'quantidade' => 5, 'valor_unitario' => 80]],
        ]))->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar")->assertOk();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar-entrega")
            ->assertOk()
            ->assertJsonPath('status', 'entregue');

        $this->assertDatabaseHas('pedidos_compra', [
            'id' => $pedido['id'],
            'status' => 'entregue',
        ]);

        $this->assertDatabaseHas('movimentacoes_estoque', [
            'produto_id' => $this->produto->id,
            'tipo' => 'entrada',
            'quantidade' => 5,
            'usuario_id' => $this->admin->id,
        ]);

        $this->assertEquals(
            $estoqueInicial + 5,
            $this->produto->fresh()->estoque_atual
        );
    }

    public function test_confirmar_entrega_cria_movimentacao_para_cada_item(): void
    {
        $produto2 = Produto::create([
            'loja_id' => $this->loja->id,
            'nome' => 'Coleira',
            'categoria' => 'acessorio',
            'valor_custo' => 15, 'margem' => 30, 'valor_venda' => 19.5,
            'estoque_atual' => 0, 'ativo' => true,
        ]);

        $pedido = $this->postJson('/api/pedidos-compra', $this->payloadValido([
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'itens' => [
                ['produto_id' => $this->produto->id, 'quantidade' => 2, 'valor_unitario' => 80],
                ['produto_id' => $produto2->id, 'quantidade' => 4, 'valor_unitario' => 15],
            ],
        ]))->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar")->assertOk();
        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar-entrega")->assertOk();

        $this->assertDatabaseCount('movimentacoes_estoque', 2);
    }

    public function test_nao_permite_confirmar_entrega_de_pedido_pendente(): void
    {
        $pedido = $this->postJson('/api/pedidos-compra', $this->payloadValido())
            ->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar-entrega")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Somente pedidos confirmados podem ter entrega registrada.');
    }

    // -----------------------------------------------------------------------
    // CANCELAMENTO
    // -----------------------------------------------------------------------

    public function test_cancela_pedido_pendente(): void
    {
        $pedido = $this->postJson('/api/pedidos-compra', $this->payloadValido())
            ->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/cancelar")
            ->assertOk()
            ->assertJsonPath('status', 'cancelado');

        $this->assertDatabaseHas('pedidos_compra', [
            'id' => $pedido['id'],
            'status' => 'cancelado',
            'cancelado_por' => $this->admin->id,
        ]);
    }

    public function test_cancela_pedido_confirmado(): void
    {
        $pedido = PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'confirmado',
            'data_estimativa_entrega' => now()->addDays(3)->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'valor_total' => 80,
        ]);

        $this->postJson("/api/pedidos-compra/{$pedido->id}/cancelar")
            ->assertOk()
            ->assertJsonPath('status', 'cancelado');
    }

    public function test_nao_permite_cancelar_pedido_entregue(): void
    {
        $pedido = PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'entregue',
            'data_estimativa_entrega' => now()->subDays(1)->toDateString(),
            'valor_total' => 80,
        ]);

        $this->postJson("/api/pedidos-compra/{$pedido->id}/cancelar")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Pedido não pode ser cancelado neste status.');
    }

    public function test_nao_permite_cancelar_pedido_ja_cancelado(): void
    {
        $pedido = PedidoCompra::create([
            'loja_id' => $this->loja->id,
            'fornecedor_id' => $this->fornecedor->id,
            'usuario_id' => $this->admin->id,
            'status' => 'cancelado',
            'data_estimativa_entrega' => now()->subDays(1)->toDateString(),
            'valor_total' => 80,
        ]);

        $this->postJson("/api/pedidos-compra/{$pedido->id}/cancelar")
            ->assertUnprocessable();
    }

    // -----------------------------------------------------------------------
    // GERAÇÃO DE PAGAMENTOS — distribuição de valor
    // -----------------------------------------------------------------------

    public function test_distribui_valor_com_resto_centavo_entre_parcelas(): void
    {
        // 100 / 3 = 33.33 + 33.33 + 33.34
        $produto = Produto::create([
            'loja_id' => $this->loja->id,
            'nome' => 'Produto Centavo',
            'categoria' => 'higiene',
            'valor_custo' => 100, 'margem' => 0, 'valor_venda' => 100,
            'estoque_atual' => 50, 'ativo' => true,
        ]);

        $pedido = $this->postJson('/api/pedidos-compra', [
            'fornecedor_id' => $this->fornecedor->id,
            'data_estimativa_entrega' => now()->addDays(5)->toDateString(),
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'quantidade_parcelas' => 3,
            'recorrencia_dias' => 30,
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 1, 'valor_unitario' => 100]],
        ])->assertCreated()->json();

        $this->postJson("/api/pedidos-compra/{$pedido['id']}/confirmar")->assertOk();

        $parcelas = Pagamento::where('pedido_compra_id', $pedido['id'])
            ->orderBy('data_vencimento')
            ->pluck('valor_total')
            ->map(fn($v) => (float) $v)
            ->toArray();

        $this->assertCount(3, $parcelas);
        $this->assertEquals(100.00, array_sum($parcelas));
    }
}
