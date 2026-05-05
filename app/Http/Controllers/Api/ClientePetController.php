<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pet;
use Illuminate\Http\Request;

class ClientePetController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $query = Pet::query()
            ->with('cliente:id,loja_id,nome,telefone,ativo')
            ->whereHas('cliente', function ($q) use ($lojaId) {
                $q->where('loja_id', $lojaId);
            });

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'ilike', '%' . $busca . '%')
                    ->orWhere('raca', 'ilike', '%' . $busca . '%')
                    ->orWhereHas('cliente', function ($cq) use ($busca) {
                        $cq->where('nome', 'ilike', '%' . $busca . '%')
                            ->orWhere('telefone', 'ilike', '%' . $busca . '%');
                    });
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('porte')) {
            $query->where('porte', $request->porte);
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        return response()->json($query->orderBy('nome')->paginate(15));
    }

    public function store(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $dados = $request->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'cliente_nome' => ['required_without:cliente_id', 'string', 'max:255'],
            'cliente_telefone' => ['nullable', 'string', 'max:30'],
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['nullable', 'in:cao,gato,outros'],
            'porte' => ['nullable', 'in:pequeno,medio,grande'],
            'raca' => ['nullable', 'string', 'max:80'],
            'idade_meses' => ['nullable', 'integer', 'min:0', 'max:400'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $cliente = null;
        if (!empty($dados['cliente_id'])) {
            $cliente = Cliente::query()
                ->where('id', (int) $dados['cliente_id'])
                ->where('loja_id', $lojaId)
                ->first();

            if (!$cliente) {
                return response()->json(['message' => 'Cliente inválido para esta loja.'], 422);
            }
        } else {
            $cliente = Cliente::create([
                'loja_id' => $lojaId,
                'nome' => $dados['cliente_nome'],
                'telefone' => $dados['cliente_telefone'] ?? null,
                'ativo' => true,
            ]);
        }

        $pet = Pet::create([
            'cliente_id' => $cliente->id,
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'] ?? null,
            'porte' => $dados['porte'] ?? null,
            'raca' => $dados['raca'] ?? null,
            'idade_meses' => $dados['idade_meses'] ?? null,
            'ativo' => array_key_exists('ativo', $dados) ? (bool) $dados['ativo'] : true,
        ]);

        return response()->json($pet->load('cliente'), 201);
    }

    public function show(Pet $pet)
    {
        $this->garantirMesmaLoja($pet);
        return response()->json($pet->load('cliente'));
    }

    public function update(Request $request, Pet $pet)
    {
        $this->garantirMesmaLoja($pet);

        $lojaId = auth()->user()->loja_id;

        $dados = $request->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'cliente_nome' => ['nullable', 'string', 'max:255'],
            'cliente_telefone' => ['nullable', 'string', 'max:30'],
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['nullable', 'in:cao,gato,outros'],
            'porte' => ['nullable', 'in:pequeno,medio,grande'],
            'raca' => ['nullable', 'string', 'max:80'],
            'idade_meses' => ['nullable', 'integer', 'min:0', 'max:400'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $clienteAtual = $pet->cliente;

        if (!empty($dados['cliente_id'])) {
            $novoCliente = Cliente::query()
                ->where('id', (int) $dados['cliente_id'])
                ->where('loja_id', $lojaId)
                ->first();

            if (!$novoCliente) {
                return response()->json(['message' => 'Cliente inválido para esta loja.'], 422);
            }

            $pet->cliente_id = $novoCliente->id;
            $clienteAtual = $novoCliente;
        }

        if (array_key_exists('cliente_nome', $dados) || array_key_exists('cliente_telefone', $dados)) {
            $clienteAtual->update([
                'nome' => $dados['cliente_nome'] ?? $clienteAtual->nome,
                'telefone' => array_key_exists('cliente_telefone', $dados)
                    ? ($dados['cliente_telefone'] ?: null)
                    : $clienteAtual->telefone,
            ]);
        }

        $pet->update([
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'] ?? null,
            'porte' => $dados['porte'] ?? null,
            'raca' => $dados['raca'] ?? null,
            'idade_meses' => $dados['idade_meses'] ?? null,
            'ativo' => array_key_exists('ativo', $dados) ? (bool) $dados['ativo'] : $pet->ativo,
        ]);

        return response()->json($pet->fresh()->load('cliente'));
    }

    public function destroy(Pet $pet)
    {
        $this->garantirMesmaLoja($pet);
        $pet->delete();

        return response()->json(null, 204);
    }

    public function clientesList()
    {
        $lojaId = auth()->user()->loja_id;

        $clientes = Cliente::query()
            ->where('loja_id', $lojaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'telefone']);

        return response()->json($clientes);
    }

    private function garantirMesmaLoja(Pet $pet): void
    {
        $lojaId = auth()->user()->loja_id;

        if ((int) $pet->cliente?->loja_id !== (int) $lojaId) {
            abort(404);
        }
    }
}
