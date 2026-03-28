<?php

namespace App\Http\Controllers;

use App\Http\Requests\BancoRequest;
use App\Models\Banco;

class BancoController extends Controller
{
    public function index()
    {
        $bancos = Banco::orderBy('nome')->get();
        return view('bancos.index', compact('bancos'));
    }

    public function create()
    {
        if (Banco::count() >= 5) {
            return redirect()->route('bancos.index')
                ->with('error', 'Limite maximo de 5 bancos atingido.');
        }

        return view('bancos.form');
    }

    public function store(BancoRequest $request)
    {
        if (Banco::count() >= 5) {
            return back()->with('error', 'Limite maximo de 5 bancos atingido.');
        }

        Banco::create($request->validated());
        return redirect()->route('bancos.index')->with('success', 'Banco criado com sucesso.');
    }

    public function edit(Banco $banco)
    {
        return view('bancos.form', compact('banco'));
    }

    public function update(BancoRequest $request, Banco $banco)
    {
        $banco->update($request->validated());
        return redirect()->route('bancos.index')->with('success', 'Banco atualizado com sucesso.');
    }

    public function destroy(Banco $banco)
    {
        $banco->delete();
        return redirect()->route('bancos.index')->with('success', 'Banco removido com sucesso.');
    }
}
