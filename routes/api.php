<?php

use App\Http\Controllers\Api\BancoController;
use App\Http\Controllers\Api\AlertasMetricasController;
use App\Http\Controllers\Api\BanhoTosaAgendamentoController;
use App\Http\Controllers\Api\BanhoTosaServicoController;
use App\Http\Controllers\Api\BanhoTosaCustoController;
use App\Http\Controllers\Api\BandeiraController;
use App\Http\Controllers\Api\CaixaController;
use App\Http\Controllers\Api\ClientePetController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FornecedorController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\LojaController;
use App\Http\Controllers\Api\PagamentoController;
use App\Http\Controllers\Api\PedidoCompraController;
use App\Http\Controllers\Api\PlanoMaquininhaController;
use App\Http\Controllers\Api\MovimentacaoInternaController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['lojaAtiva', 'lojas']);
    });

    // === Acesso: admin + atendente ===

    // Caixa (operacional)
    Route::get('caixa/hoje', [CaixaController::class, 'hoje']);
    Route::post('caixa/abrir', [CaixaController::class, 'abrir']);
    Route::get('caixa/historico', [CaixaController::class, 'historico']);
    Route::get('caixa/historico/pdf', [CaixaController::class, 'historicoPdf']);
    Route::get('caixa/clientes-com-pets', [CaixaController::class, 'clientesComPets']);
    Route::post('caixa/clientes-com-pets', [CaixaController::class, 'criarClienteComPet']);
    Route::get('caixa/{caixa}', [CaixaController::class, 'show'])->where('caixa', '[0-9]+');
    Route::post('caixa/{caixa}/entrada', [CaixaController::class, 'adicionarEntrada']);
    Route::put('caixa/{caixa}/entrada/{entrada}', [CaixaController::class, 'atualizarEntrada']);
    Route::delete('caixa/{caixa}/entrada/{entrada}', [CaixaController::class, 'removerEntrada']);
    Route::get('caixa/produtos-racao-favoritos', [CaixaController::class, 'produtosRacaoFavoritos']);
    Route::post('caixa/{caixa}/fechar', [CaixaController::class, 'fechar']);
    Route::post('caixa/{caixa}/autorizar', [CaixaController::class, 'autorizar']);
    Route::post('caixa/{caixa}/reabrir', [CaixaController::class, 'reabrir']);
    Route::get('caixa-pendentes', [CaixaController::class, 'pendentes']);
    Route::get('alertas-metricas', [AlertasMetricasController::class, 'index']);
    Route::get('clientes-pets/clientes-list', [ClientePetController::class, 'clientesList']);
    Route::apiResource('clientes-pets', ClientePetController::class)->parameters([
        'clientes-pets' => 'pet',
    ]);

    // Produtos (operacional: CRUD + estoque)
    Route::apiResource('produtos', ProdutoController::class);
    Route::get('produtos/{produto}/movimentacoes', [ProdutoController::class, 'movimentacoes']);
    Route::post('produtos/{produto}/movimentacao', [ProdutoController::class, 'registrarMovimentacao']);

    // Fornecedores (leitura para todos)
    Route::get('fornecedores', [FornecedorController::class, 'index']);
    Route::get('fornecedores/{fornecedor}', [FornecedorController::class, 'show']);

    // Bancos (leitura para todos)
    Route::get('bancos', [BancoController::class, 'index']);
    Route::get('bancos/{banco}', [BancoController::class, 'show']);

    // Bandeiras e Plano ativo (leitura para todos — usado no form de entrada por cartao)
    Route::get('bandeiras', [BandeiraController::class, 'index']);
    Route::get('planos-maquininha/ativo', [PlanoMaquininhaController::class, 'ativo']);

    // Movimentacoes Internas (CRUD para todos, aprovacao admin via middleware no controller)
    Route::get('movimentacoes-internas-saldos', [MovimentacaoInternaController::class, 'saldos']);
    Route::get('movimentacoes-internas/pdf', [MovimentacaoInternaController::class, 'pdf']);
    Route::apiResource('movimentacoes-internas', MovimentacaoInternaController::class);
    Route::post('movimentacoes-internas/{movimentacoes_interna}/aprovar', [MovimentacaoInternaController::class, 'aprovar']);
    Route::post('movimentacoes-internas/{movimentacoes_interna}/rejeitar', [MovimentacaoInternaController::class, 'rejeitar']);
    Route::get('movimentacoes-internas-pendentes', [MovimentacaoInternaController::class, 'pendentes']);

    // Alertas pagamentos (badge no sidebar)
    Route::get('pagamentos-alertas', [PagamentoController::class, 'alertas']);

    // === Banho e Tosa (admin + atendente) ===
    Route::get('banho-tosa/agendamentos', [BanhoTosaAgendamentoController::class, 'index']);
    Route::post('banho-tosa/agendamentos', [BanhoTosaAgendamentoController::class, 'store']);
    Route::put('banho-tosa/agendamentos/{agendamento}', [BanhoTosaAgendamentoController::class, 'update']);
    Route::post('banho-tosa/agendamentos/{agendamento}/confirmar', [BanhoTosaAgendamentoController::class, 'confirmar']);
    Route::post('banho-tosa/agendamentos/{agendamento}/iniciar', [BanhoTosaAgendamentoController::class, 'iniciar']);
    Route::post('banho-tosa/agendamentos/{agendamento}/concluir', [BanhoTosaAgendamentoController::class, 'concluir']);
    Route::post('banho-tosa/agendamentos/{agendamento}/cancelar', [BanhoTosaAgendamentoController::class, 'cancelar']);
    Route::get('banho-tosa/servicos', [BanhoTosaServicoController::class, 'index']);
    Route::get('banho-tosa/servicos/{servico}', [BanhoTosaServicoController::class, 'show']);

    // === Acesso: somente admin ===
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('metas/anual', [MetaController::class, 'anual']);
        Route::get('metas', [MetaController::class, 'index']);
        Route::post('metas', [MetaController::class, 'store']);
        Route::put('metas/{meta}', [MetaController::class, 'update']);
        Route::post('metas/{meta}/fechar', [MetaController::class, 'fechar']);
        Route::post('metas/dias/{metaDiaria}', [MetaController::class, 'updateDia']);
        Route::post('metas/excecao', [MetaController::class, 'excecao']);

        // Lojas
        Route::apiResource('lojas', LojaController::class);
        Route::get('lojas/{loja}/calendario', [LojaController::class, 'calendario']);
        Route::post('lojas/{loja}/calendario', [LojaController::class, 'salvarCalendario']);
        Route::get('lojas/{loja}/usuarios', [LojaController::class, 'usuarios']);
        Route::post('lojas/{loja}/vincular-usuario', [LojaController::class, 'vincularUsuario']);
        Route::delete('lojas/{loja}/desvincular-usuario/{user}', [LojaController::class, 'desvincularUsuario']);

        // Bancos (escrita)
        Route::post('bancos', [BancoController::class, 'store']);
        Route::put('bancos/{banco}', [BancoController::class, 'update']);
        Route::delete('bancos/{banco}', [BancoController::class, 'destroy']);

        // Bandeiras (escrita)
        Route::post('bandeiras', [BandeiraController::class, 'store']);
        Route::get('bandeiras/{bandeira}', [BandeiraController::class, 'show']);
        Route::put('bandeiras/{bandeira}', [BandeiraController::class, 'update']);
        Route::delete('bandeiras/{bandeira}', [BandeiraController::class, 'destroy']);

        // Planos de Maquininha
        Route::get('planos-maquininha', [PlanoMaquininhaController::class, 'index']);
        Route::post('planos-maquininha', [PlanoMaquininhaController::class, 'store']);
        Route::get('planos-maquininha/{plano}', [PlanoMaquininhaController::class, 'show']);
        Route::put('planos-maquininha/{plano}', [PlanoMaquininhaController::class, 'update']);
        Route::delete('planos-maquininha/{plano}', [PlanoMaquininhaController::class, 'destroy']);

        // Fornecedores (escrita)
        Route::post('fornecedores', [FornecedorController::class, 'store']);
        Route::put('fornecedores/{fornecedor}', [FornecedorController::class, 'update']);
        Route::delete('fornecedores/{fornecedor}', [FornecedorController::class, 'destroy']);

        // Usuarios
        Route::apiResource('usuarios', UsuarioController::class);
        Route::get('lojas-list', [UsuarioController::class, 'lojasList']);

        // Pagamentos (completo)
        Route::get('pagamentos/pdf', [PagamentoController::class, 'pdf']);
        Route::apiResource('pagamentos', PagamentoController::class);
        Route::post('pagamentos/{pagamento}/pagar', [PagamentoController::class, 'registrarPagamento']);

        // Banho e Tosa escrita (somente admin)
        Route::post('banho-tosa/servicos', [BanhoTosaServicoController::class, 'store']);
        Route::put('banho-tosa/servicos/{servico}', [BanhoTosaServicoController::class, 'update']);
        Route::delete('banho-tosa/servicos/{servico}', [BanhoTosaServicoController::class, 'destroy']);
        Route::get('banho-tosa/custos', [BanhoTosaCustoController::class, 'index']);
        Route::post('banho-tosa/custos', [BanhoTosaCustoController::class, 'store']);
        Route::delete('banho-tosa/custos/{custo}', [BanhoTosaCustoController::class, 'destroy']);

        // Pedidos de Compra
        Route::get('pedidos-compra', [PedidoCompraController::class, 'index']);
        Route::post('pedidos-compra', [PedidoCompraController::class, 'store']);
        Route::get('pedidos-compra/{pedidoCompra}', [PedidoCompraController::class, 'show']);
        Route::put('pedidos-compra/{pedidoCompra}', [PedidoCompraController::class, 'update']);
        Route::patch('pedidos-compra/{pedidoCompra}/pagamento', [PedidoCompraController::class, 'atualizarDadosPagamento']);
        Route::post('pedidos-compra/{pedidoCompra}/confirmar', [PedidoCompraController::class, 'confirmar']);
        Route::post('pedidos-compra/{pedidoCompra}/confirmar-entrega', [PedidoCompraController::class, 'confirmarEntrega']);
        Route::post('pedidos-compra/{pedidoCompra}/cancelar', [PedidoCompraController::class, 'cancelar']);
        Route::get('pedidos-compra/{pedidoCompra}/pdf', [PedidoCompraController::class, 'pdf']);
    });
});
