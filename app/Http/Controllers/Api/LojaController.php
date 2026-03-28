<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LojaRequest;
use App\Models\Loja;
use App\Models\User;
use Illuminate\Http\Request;

class LojaController extends Controller
{
    public function index()
    {
        $lojas = Loja::withCount('usuarios')->orderBy('nome')->paginate(15);
        return response()->json($lojas);
    }

    public function store(LojaRequest $request)
    {
        $loja = Loja::create($request->validated());

        $user = auth()->user();
        $loja->usuarios()->attach($user->id);

        if (!$user->loja_id) {
            $user->update(['loja_id' => $loja->id]);
        }

        return response()->json($loja, 201);
    }

    public function show(Loja $loja)
    {
        return response()->json($loja);
    }

    public function update(LojaRequest $request, Loja $loja)
    {
        $loja->update($request->validated());
        return response()->json($loja);
    }

    public function destroy(Loja $loja)
    {
        $loja->delete();
        return response()->json(null, 204);
    }

    public function usuarios(Loja $loja)
    {
        $usuarios = $loja->usuarios()->get();
        $disponiveis = User::whereDoesntHave('lojas', function ($q) use ($loja) {
            $q->where('lojas.id', $loja->id);
        })->where('ativo', true)->get();

        return response()->json([
            'loja' => $loja,
            'usuarios' => $usuarios,
            'disponiveis' => $disponiveis,
        ]);
    }

    public function vincularUsuario(Request $request, Loja $loja)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $loja->usuarios()->syncWithoutDetaching([$request->user_id]);
        return response()->json(['message' => 'Usuario vinculado com sucesso.']);
    }

    public function desvincularUsuario(Loja $loja, User $user)
    {
        $loja->usuarios()->detach($user->id);

        if ($user->loja_id === $loja->id) {
            $primeiraLoja = $user->lojas()->first();
            $user->update(['loja_id' => $primeiraLoja?->id]);
        }

        return response()->json(null, 204);
    }
}
