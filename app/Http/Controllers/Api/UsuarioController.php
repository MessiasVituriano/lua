<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\Loja;
use App\Models\User;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with('lojaAtiva')->orderBy('name')->paginate(15);
        return response()->json($usuarios);
    }

    public function store(UsuarioRequest $request)
    {
        $data = $request->validated();
        $lojaIds = $data['lojas'] ?? [];
        unset($data['lojas'], $data['password_confirmation']);

        $user = User::create($data);

        if (!empty($lojaIds)) {
            $user->lojas()->attach($lojaIds);
            $user->update(['loja_id' => $lojaIds[0]]);
        }

        return response()->json($user->load('lojas'), 201);
    }

    public function show(User $usuario)
    {
        return response()->json($usuario->load('lojas'));
    }

    public function update(UsuarioRequest $request, User $usuario)
    {
        $data = $request->validated();
        $lojaIds = $data['lojas'] ?? [];
        unset($data['lojas'], $data['password_confirmation']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $usuario->update($data);
        $usuario->lojas()->sync($lojaIds);

        if (!in_array($usuario->loja_id, $lojaIds) && !empty($lojaIds)) {
            $usuario->update(['loja_id' => $lojaIds[0]]);
        }

        return response()->json($usuario->load('lojas'));
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return response()->json(['message' => 'Voce nao pode remover seu proprio usuario.'], 403);
        }

        $usuario->delete();
        return response()->json(null, 204);
    }

    public function lojasList()
    {
        return response()->json(Loja::where('ativa', true)->orderBy('nome')->get());
    }
}
