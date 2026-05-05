<?php

namespace Database\Seeders;

use App\Models\CaixaDiario;
use App\Models\Cliente;
use App\Models\EntradaCaixa;
use App\Models\EntradaCaixaItem;
use App\Models\Fornecedor;
use App\Models\Loja;
use App\Models\MovimentacaoEstoque;
use App\Models\Pet;
use App\Models\Produto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoVendasPorAnimalSeeder extends Seeder
{
    public function run(): void
    {
        $loja = Loja::first();
        $admin = User::where('role', 'admin')->first();

        $fornecedorRacao = Fornecedor::where('categoria', 'racao')->first()
            ?? Fornecedor::first();

        // ── Produtos de ração com estoque em gramas ──────────────────────────
        // Estoque representa gramas disponíveis (ex: 10 sacos de 15kg = 150.000g)
        $racoes = [
            [
                'nome'          => 'Ração Premier Adulto 15kg',
                'valor_custo'   => 120.00,
                'margem'        => 35,
                'estoque_atual' => 150000,  // 10 sacos de 15kg
                'estoque_min'   => 15000,   // mínimo 1 saco
            ],
            [
                'nome'          => 'Ração Royal Canin Mini Adulto 7,5kg',
                'valor_custo'   => 180.00,
                'margem'        => 40,
                'estoque_atual' => 112500,  // 15 sacos de 7,5kg
                'estoque_min'   => 7500,
            ],
            [
                'nome'          => 'Ração Golden Cão Grande 15kg',
                'valor_custo'   => 95.00,
                'margem'        => 38,
                'estoque_atual' => 225000,  // 15 sacos de 15kg
                'estoque_min'   => 15000,
            ],
            [
                'nome'          => 'Ração Golden Gatos 10kg',
                'valor_custo'   => 85.00,
                'margem'        => 38,
                'estoque_atual' => 100000,  // 10 sacos de 10kg
                'estoque_min'   => 10000,
            ],
            [
                'nome'          => 'Ração Whiskas Adulto 10kg',
                'valor_custo'   => 75.00,
                'margem'        => 42,
                'estoque_atual' => 80000,   // 8 sacos de 10kg
                'estoque_min'   => 10000,
            ],
        ];

        $produtosRacao = [];
        foreach ($racoes as $dados) {
            $produto = Produto::create([
                'loja_id'       => $loja->id,
                'fornecedor_id' => $fornecedorRacao?->id,
                'nome'          => $dados['nome'],
                'categoria'     => 'racao',
                'valor_custo'   => $dados['valor_custo'],
                'margem'        => $dados['margem'],
                'valor_venda'   => Produto::calcularValorVenda($dados['valor_custo'], $dados['margem']),
                'estoque_atual' => $dados['estoque_atual'],
                'estoque_min'   => $dados['estoque_min'],
            ]);

            MovimentacaoEstoque::create([
                'produto_id' => $produto->id,
                'tipo'       => 'entrada',
                'quantidade' => $dados['estoque_atual'],
                'motivo'     => 'Estoque inicial (demo)',
                'usuario_id' => $admin->id,
            ]);

            $produtosRacao[] = $produto;
        }

        // ── Outros produtos ─────────────────────────────────────────────────
        $outros = [
            ['nome' => 'Antipulgas Frontline Plus',    'categoria' => 'medicamento', 'valor_custo' => 45.00, 'margem' => 50, 'estoque_atual' => 30],
            ['nome' => 'Shampoo Sanol Dog 500ml',       'categoria' => 'higiene',     'valor_custo' => 12.00, 'margem' => 60, 'estoque_atual' => 20],
            ['nome' => 'Coleira Antipulgas Grande',     'categoria' => 'acessorio',   'valor_custo' => 18.00, 'margem' => 65, 'estoque_atual' => 15],
            ['nome' => 'Petisco Natural Frango 200g',   'categoria' => 'petisco',     'valor_custo' =>  8.00, 'margem' => 70, 'estoque_atual' => 40],
        ];

        $produtosOutros = [];
        foreach ($outros as $dados) {
            $produto = Produto::create([
                'loja_id'       => $loja->id,
                'fornecedor_id' => $fornecedorRacao?->id,
                'nome'          => $dados['nome'],
                'categoria'     => $dados['categoria'],
                'valor_custo'   => $dados['valor_custo'],
                'margem'        => $dados['margem'],
                'valor_venda'   => Produto::calcularValorVenda($dados['valor_custo'], $dados['margem']),
                'estoque_atual' => $dados['estoque_atual'],
                'estoque_min'   => 5,
            ]);

            MovimentacaoEstoque::create([
                'produto_id' => $produto->id,
                'tipo'       => 'entrada',
                'quantidade' => $dados['estoque_atual'],
                'motivo'     => 'Estoque inicial (demo)',
                'usuario_id' => $admin->id,
            ]);

            $produtosOutros[] = $produto;
        }

        // ── Caixa do dia de hoje ─────────────────────────────────────────────
        $hoje = Carbon::today();

        $caixa = CaixaDiario::firstOrCreate(
            ['loja_id' => $loja->id, 'data' => $hoje->toDateString()],
            ['status' => 'aberto']
        );

        // ── Clientes e pets demo ───────────────────────────────────────────
        $clienteAna = Cliente::create([
            'loja_id' => $loja->id,
            'nome' => 'Ana Souza',
            'telefone' => '(11) 98888-1111',
            'ativo' => true,
        ]);

        $clienteBruno = Cliente::create([
            'loja_id' => $loja->id,
            'nome' => 'Bruno Lima',
            'telefone' => '(11) 97777-2222',
            'ativo' => true,
        ]);

        $clienteCarla = Cliente::create([
            'loja_id' => $loja->id,
            'nome' => 'Carla Martins',
            'telefone' => '(11) 96666-3333',
            'ativo' => true,
        ]);

        $petThor = Pet::create([
            'cliente_id' => $clienteAna->id,
            'nome' => 'Thor',
            'tipo' => 'cao',
            'porte' => 'grande',
            'raca' => 'Labrador',
            'idade_meses' => 48,
            'ativo' => true,
        ]);

        $petLuna = Pet::create([
            'cliente_id' => $clienteAna->id,
            'nome' => 'Luna',
            'tipo' => 'cao',
            'porte' => 'pequeno',
            'raca' => 'Shih Tzu',
            'idade_meses' => 36,
            'ativo' => true,
        ]);

        $petMia = Pet::create([
            'cliente_id' => $clienteBruno->id,
            'nome' => 'Mia',
            'tipo' => 'gato',
            'porte' => null,
            'raca' => 'Persa',
            'idade_meses' => 30,
            'ativo' => true,
        ]);

        $petToto = Pet::create([
            'cliente_id' => $clienteCarla->id,
            'nome' => 'Toto',
            'tipo' => 'cao',
            'porte' => 'medio',
            'raca' => 'SRD',
            'idade_meses' => 18,
            'ativo' => true,
        ]);

        // ── Vendas demo com itens por perfil de pet ──────────────────────────
        $vendas = [
            // 1. Cão pequeno comprando ração Premier
            [
                'forma'     => 'pix',
                'descricao' => 'Venda ração - cão pequeno',
                'itens'     => [
                    [
                        'produto'       => $produtosRacao[0], // Premier 15kg
                        'quantidade'    => 1,
                        'peso_gramas'   => 15000,
                        'perfil'        => 'cao_pequeno',
                        'pet_id'        => $petLuna->id,
                    ],
                ],
            ],

            // 2. Cão médio comprando Royal Canin + petisco
            [
                'forma'     => 'cartao_debito',
                'descricao' => 'Venda ração + petisco - cão médio',
                'itens'     => [
                    [
                        'produto'       => $produtosRacao[1], // Royal Canin 7,5kg
                        'quantidade'    => 1,
                        'peso_gramas'   => 7500,
                        'perfil'        => 'cao_medio',
                        'pet_id'        => $petToto->id,
                    ],
                    [
                        'produto'       => $produtosOutros[3], // Petisco
                        'quantidade'    => 2,
                        'peso_gramas'   => null,
                        'perfil'        => 'cao_medio',
                        'pet_id'        => $petToto->id,
                    ],
                ],
            ],

            // 3. Cão grande comprando Golden
            [
                'forma'     => 'dinheiro',
                'descricao' => 'Venda ração - cão grande',
                'itens'     => [
                    [
                        'produto'       => $produtosRacao[2], // Golden Grande 15kg
                        'quantidade'    => 1,
                        'peso_gramas'   => 15000,
                        'perfil'        => 'cao_grande',
                        'pet_id'        => $petThor->id,
                    ],
                ],
            ],

            // 4. Gato comprando ração Golden Gatos
            [
                'forma'     => 'pix',
                'descricao' => 'Venda ração - gato',
                'itens'     => [
                    [
                        'produto'       => $produtosRacao[3], // Golden Gatos 10kg
                        'quantidade'    => 1,
                        'peso_gramas'   => 10000,
                        'perfil'        => 'gato',
                        'pet_id'        => $petMia->id,
                    ],
                ],
            ],

            // 5. Gato comprando Whiskas
            [
                'forma'     => 'cartao_credito',
                'descricao' => 'Venda ração - gato',
                'itens'     => [
                    [
                        'produto'       => $produtosRacao[4], // Whiskas 10kg
                        'quantidade'    => 1,
                        'peso_gramas'   => 10000,
                        'perfil'        => 'gato',
                        'pet_id'        => $petMia->id,
                    ],
                ],
            ],

            // 6. Cão médio comprando ração + medicamento + higiene
            [
                'forma'     => 'pix',
                'descricao' => 'Venda múltiplos itens - cão médio',
                'itens'     => [
                    [
                        'produto'       => $produtosRacao[0], // Premier 15kg
                        'quantidade'    => 1,
                        'peso_gramas'   => 15000,
                        'perfil'        => 'cao_medio',
                        'pet_id'        => $petToto->id,
                    ],
                    [
                        'produto'       => $produtosOutros[0], // Antipulgas
                        'quantidade'    => 1,
                        'peso_gramas'   => null,
                        'perfil'        => 'cao_medio',
                        'pet_id'        => $petToto->id,
                    ],
                    [
                        'produto'       => $produtosOutros[1], // Shampoo
                        'quantidade'    => 1,
                        'peso_gramas'   => null,
                        'perfil'        => 'cao_medio',
                        'pet_id'        => $petToto->id,
                    ],
                ],
            ],

            // 7. Cão pequeno comprando Royal Canin Mini
            [
                'forma'     => 'dinheiro',
                'descricao' => 'Venda ração - cão pequeno',
                'itens'     => [
                    [
                        'produto'       => $produtosRacao[1], // Royal Canin Mini 7,5kg
                        'quantidade'    => 2,
                        'peso_gramas'   => 7500,
                        'perfil'        => 'cao_pequeno',
                        'pet_id'        => $petLuna->id,
                    ],
                ],
            ],

            // 8. Venda legado sem itens (compatibilidade)
            [
                'forma'     => 'dinheiro',
                'descricao' => 'Banho e tosa',
                'valor'     => 80.00,
                'itens'     => [],
            ],
        ];

        $totalCaixa = 0;

        foreach ($vendas as $idx => $venda) {
            $valorTotal = $venda['valor'] ?? 0;

            // Calcular subtotais dos itens
            $itensProcessados = [];
            foreach ($venda['itens'] as $itemDados) {
                $produto   = $itemDados['produto'];
                $qtd       = $itemDados['quantidade'];
                $subtotal  = round($produto->valor_venda * $qtd, 2);
                $valorTotal += $subtotal;

                $itensProcessados[] = [
                    'produto'   => $produto,
                    'quantidade' => $qtd,
                    'subtotal'  => $subtotal,
                    'peso_gramas' => $itemDados['peso_gramas'],
                    'perfil'    => $itemDados['perfil'] ?? null,
                    'pet_id'    => $itemDados['pet_id'] ?? null,
                ];
            }

            // Criar entrada de caixa
            $entrada = EntradaCaixa::create([
                'caixa_diario_id'  => $caixa->id,
                'forma_recebimento' => $venda['forma'],
                'valor'            => $valorTotal,
                'descricao'        => $venda['descricao'],
                'created_at'       => Carbon::today()->setHour(8 + $idx)->setMinute(rand(0, 59)),
                'updated_at'       => Carbon::today()->setHour(8 + $idx)->setMinute(rand(0, 59)),
            ]);

            $totalCaixa += $valorTotal;

            // Criar itens e baixar estoque
            foreach ($itensProcessados as $item) {
                $produto     = $item['produto'];
                $perfil      = $item['perfil'] ?? 'outros';
                $consumoDia  = EntradaCaixaItem::CONSUMO_PADRAO_GRAMAS_DIA[$perfil] ?? 150;

                // Calcular data próxima compra para ração
                $proximaCompra = null;
                if ($produto->categoria === 'racao' && $item['peso_gramas']) {
                    $diasEstimados = (int) floor($item['peso_gramas'] / $consumoDia);
                    $proximaCompra = Carbon::today()->addDays($diasEstimados)->toDateString();
                }

                EntradaCaixaItem::create([
                    'entrada_caixa_id'            => $entrada->id,
                    'produto_id'                  => $produto->id,
                    'quantidade'                  => $item['quantidade'],
                    'preco_unitario'              => $produto->valor_venda,
                    'subtotal'                    => $item['subtotal'],
                    'peso_gramas'                 => $item['peso_gramas'],
                    'perfil_pet_tipo'             => $perfil,
                    'pet_id'                      => $item['pet_id'],
                    'data_proxima_compra_estimada' => $proximaCompra,
                ]);

                // Baixar estoque
                $qtdSaida = $item['peso_gramas'] ?? (int) round($item['quantidade']);
                $produto->decrement('estoque_atual', $qtdSaida);

                MovimentacaoEstoque::create([
                    'produto_id' => $produto->id,
                    'tipo'       => 'saida',
                    'quantidade' => $qtdSaida,
                    'motivo'     => 'Venda - ' . $venda['descricao'],
                    'usuario_id' => $admin->id,
                ]);
            }
        }

        // Atualizar totais do caixa
        $caixa->update([
            'total_entradas' => $totalCaixa,
            'total_saidas'   => 0,
            'saldo'          => $totalCaixa,
        ]);

        $this->command->info('Demo vendas por animal criado com sucesso!');
        $this->command->info('  Produtos de ração: ' . count($produtosRacao));
        $this->command->info('  Vendas criadas: ' . count($vendas));
        $this->command->info('  Total do caixa: R$ ' . number_format($totalCaixa, 2, ',', '.'));
    }
}
