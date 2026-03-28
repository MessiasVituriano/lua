<?php

namespace App\Http\Controllers;

use App\Http\Requests\FornecedorRequest;
use App\Models\Fornecedor;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Fornecedor::query();

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%' . $request->busca . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        $fornecedores = $query->orderBy('nome')->paginate(15)->withQueryString();

        return view('fornecedores.index', compact('fornecedores'));
    }

    public function create()
    {
        return view('fornecedores.form');
    }

    public function store(FornecedorRequest $request)
    {
        Fornecedor::create($request->validated());
        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor criado com sucesso.');
    }

    public function show(Fornecedor $fornecedor)
    {
        return view('fornecedores.show', compact('fornecedor'));
    }

    public function edit(Fornecedor $fornecedore)
    {
        return view('fornecedores.form', ['fornecedor' => $fornecedore]);
    }

    public function update(FornecedorRequest $request, Fornecedor $fornecedore)
    {
        $fornecedore->update($request->validated());
        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Fornecedor $fornecedore)
    {
        $fornecedore->delete();
        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor removido com sucesso.');
    }
}
