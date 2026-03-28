<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Loja;
use App\Models\User;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with('lojaAtiva')->orderBy('name')->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $lojas = Loja::where('ativa', true)->orderBy('nome')->get();
        return view('usuarios.form', compact('lojas'));
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

        return redirect()->route('usuarios.index')->with('success', 'Usuario criado com sucesso.');
    }

    public function edit(User $usuario)
    {
        $lojas = Loja::where('ativa', true)->orderBy('nome')->get();
        $usuarioLojas = $usuario->lojas()->pluck('lojas.id')->toArray();
        return view('usuarios.form', compact('usuario', 'lojas', 'usuarioLojas'));
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

        return redirect()->route('usuarios.index')->with('success', 'Usuario atualizado com sucesso.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'Voce nao pode remover seu proprio usuario.');
        }

        $usuario->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario removido com sucesso.');
    }
}
