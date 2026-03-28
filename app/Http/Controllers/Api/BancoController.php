<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BancoRequest;
use App\Models\Banco;

class BancoController extends Controller
{
    public function index()
    {
        return response()->json(Banco::orderBy('nome')->get());
    }

    public function store(BancoRequest $request)
    {
        if (Banco::count() >= 5) {
            return response()->json(['message' => 'Limite maximo de 5 bancos atingido.'], 409);
        }

        $banco = Banco::create($request->validated());
        return response()->json($banco, 201);
    }

    public function show(Banco $banco)
    {
        return response()->json($banco);
    }

    public function update(BancoRequest $request, Banco $banco)
    {
        $banco->update($request->validated());
        return response()->json($banco);
    }

    public function destroy(Banco $banco)
    {
        $banco->delete();
        return response()->json(null, 204);
    }
}
