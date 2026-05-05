<?php

namespace Tests\Feature;

use App\Models\CaixaDiario;
use App\Models\Loja;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CaixaEntradaItensTest extends TestCase
{
    use RefreshDatabase;

    private function autenticarUsuarioDaLoja(): array
    {
        $loja = Loja::create([
            'nome' => 'Loja Teste',
            'ativa' => true,
        ]);

        $user = User::factory()->create([
            'loja_id' => $loja->id,
            'role' => 'admin',
            'ativo' => true,
        ]);

        Sanctum::actingAs($user);

        $caixa = CaixaDiario::create([
            'loja_id' => $loja->id,
            'data' => now()->toDateString(),
            'status' => 'aberto',
        ]);

        return [$loja, $user, $caixa];
    }

    public function test_mantem_fluxo_legado_de_entrada_por_valor_total(): void
    {
        [, , $caixa] = $this->autenticarUsuarioDaLoja();

        $response = $this->postJson("/api/caixa/{$caixa->id}/entrada", [
            'forma_recebimento' => 'pix',
            'valor' => 100.50,
            'descricao' => 'Venda avulsa',
        ]);

        $response->assertCreated()
            ->assertJsonPath('valor', '100.50');

        $this->assertDatabaseHas('entradas_caixa', [
            'caixa_diario_id' => $caixa->id,
            'descricao' => 'Venda avulsa',
        ]);
    }

    public function test_cria_entrada_com_item_de_racao_e_perfil_generico_quando_pet_nao_informado(): void
    {
        [$loja, , $caixa] = $this->autenticarUsuarioDaLoja();

        $produto = Produto::create([
            'loja_id' => $loja->id,
            'nome' => 'Racao Premium 1kg',
            'categoria' => 'racao',
            'valor_custo' => 20,
            'margem' => 50,
            'valor_venda' => 30,
            'estoque_atual' => 10000,
            'ativo' => true,
        ]);

        $response = $this->postJson("/api/caixa/{$caixa->id}/entrada", [
            'forma_recebimento' => 'pix',
            'descricao' => 'Racao fracionada',
            'itens' => [
                [
                    'produto_id' => $produto->id,
                    'quantidade' => 1,
                    'preco_unitario' => 30,
                    'peso_gramas' => 1000,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('valor', '30.00')
            ->assertJsonPath('itens.0.perfil_pet_tipo', 'outros');

        $this->assertDatabaseHas('entrada_caixa_itens', [
            'produto_id' => $produto->id,
            'perfil_pet_tipo' => 'outros',
            'subtotal' => 30.00,
        ]);
    }

    public function test_calcula_valor_total_da_entrada_pela_soma_dos_itens(): void
    {
        [$loja, , $caixa] = $this->autenticarUsuarioDaLoja();

        $produto = Produto::create([
            'loja_id' => $loja->id,
            'nome' => 'Racao Super',
            'categoria' => 'racao',
            'valor_custo' => 15,
            'margem' => 40,
            'valor_venda' => 21,
            'estoque_atual' => 10000,
            'ativo' => true,
        ]);

        $response = $this->postJson("/api/caixa/{$caixa->id}/entrada", [
            'forma_recebimento' => 'dinheiro',
            'itens' => [
                [
                    'produto_id' => $produto->id,
                    'quantidade' => 2,
                    'preco_unitario' => 21,
                    'perfil_pet_tipo' => 'cao_medio',
                    'peso_gramas' => 2000,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('valor', '42.00');
    }

    public function test_preenche_data_proxima_compra_estimada_com_base_no_perfil(): void
    {
        [$loja, , $caixa] = $this->autenticarUsuarioDaLoja();

        $produto = Produto::create([
            'loja_id' => $loja->id,
            'nome' => 'Racao Controle',
            'categoria' => 'racao',
            'valor_custo' => 18,
            'margem' => 30,
            'valor_venda' => 23.4,
            'estoque_atual' => 10000,
            'ativo' => true,
        ]);

        $this->postJson("/api/caixa/{$caixa->id}/entrada", [
            'forma_recebimento' => 'pix',
            'itens' => [
                [
                    'produto_id' => $produto->id,
                    'quantidade' => 1,
                    'preco_unitario' => 23.4,
                    'peso_gramas' => 1000,
                    'perfil_pet_tipo' => 'cao_pequeno',
                ],
            ],
        ])->assertCreated();

        $esperada = now()->startOfDay()->addDays((int) floor(1000 / 120))->toDateString();

        $this->assertDatabaseHas('entrada_caixa_itens', [
            'produto_id' => $produto->id,
            'perfil_pet_tipo' => 'cao_pequeno',
            'data_proxima_compra_estimada' => $esperada,
        ]);
    }

    public function test_baixa_estoque_e_registra_movimentacao_quando_lanca_item(): void
    {
        [$loja, $user, $caixa] = $this->autenticarUsuarioDaLoja();

        $produto = Produto::create([
            'loja_id' => $loja->id,
            'nome' => 'Racao Baixa Estoque',
            'categoria' => 'racao',
            'valor_custo' => 19,
            'margem' => 20,
            'valor_venda' => 22.8,
            'estoque_atual' => 5000,
            'ativo' => true,
        ]);

        $this->postJson("/api/caixa/{$caixa->id}/entrada", [
            'forma_recebimento' => 'pix',
            'itens' => [
                [
                    'produto_id' => $produto->id,
                    'quantidade' => 1,
                    'preco_unitario' => 22.8,
                    'peso_gramas' => 1000,
                    'perfil_pet_tipo' => 'outros',
                ],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('movimentacoes_estoque', [
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 1000,
            'usuario_id' => $user->id,
        ]);

        $this->assertSame(4000, (int) $produto->fresh()->estoque_atual);
    }

    public function test_retorna_erro_quando_estoque_for_insuficiente_para_item(): void
    {
        [$loja, , $caixa] = $this->autenticarUsuarioDaLoja();

        $produto = Produto::create([
            'loja_id' => $loja->id,
            'nome' => 'Racao Estoque Insuficiente',
            'categoria' => 'racao',
            'valor_custo' => 10,
            'margem' => 30,
            'valor_venda' => 13,
            'estoque_atual' => 500,
            'ativo' => true,
        ]);

        $this->postJson("/api/caixa/{$caixa->id}/entrada", [
            'forma_recebimento' => 'dinheiro',
            'itens' => [
                [
                    'produto_id' => $produto->id,
                    'quantidade' => 1,
                    'preco_unitario' => 13,
                    'peso_gramas' => 1000,
                    'perfil_pet_tipo' => 'outros',
                ],
            ],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('entradas_caixa', [
            'caixa_diario_id' => $caixa->id,
        ]);
    }
}
