<?php

namespace App\Http\Controllers;

use App\Http\Requests\LojaRequest;
use App\Models\Loja;
use App\Models\User;
use Illuminate\Http\Request;

class LojaController extends Controller
{
    public function index()
    {
        $lojas = Loja::withCount('usuarios')->orderBy('nome')->paginate(15);
        return view('lojas.index', compact('lojas'));
    }

    public function create()
    {
        return view('lojas.form');
    }

    public function store(LojaRequest $request)
    {
        $loja = Loja::create($request->validated());

        $user = auth()->user();
        $loja->usuarios()->attach($user->id);

        if (!$user->loja_id) {
            $user->update(['loja_id' => $loja->id]);
        }

        return redirect()->route('lojas.index')->with('success', 'Loja criada com sucesso.');
    }

    public function edit(Loja $loja)
    {
        return view('lojas.form', compact('loja'));
    }

    public function update(LojaRequest $request, Loja $loja)
    {
        $loja->update($request->validated());
        return redirect()->route('lojas.index')->with('success', 'Loja atualizada com sucesso.');
    }

    public function destroy(Loja $loja)
    {
        $loja->delete();
        return redirect()->route('lojas.index')->with('success', 'Loja removida com sucesso.');
    }

    public function usuarios(Loja $loja)
    {
        $usuarios = $loja->usuarios()->paginate(15);
        $disponiveis = User::whereDoesntHave('lojas', function ($q) use ($loja) {
            $q->where('lojas.id', $loja->id);
        })->where('ativo', true)->get();

        return view('lojas.usuarios', compact('loja', 'usuarios', 'disponiveis'));
    }

    public function vincularUsuario(Request $request, Loja $loja)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $loja->usuarios()->syncWithoutDetaching([$request->user_id]);
        return back()->with('success', 'Usuario vinculado com sucesso.');
    }

    public function desvincularUsuario(Loja $loja, User $user)
    {
        $loja->usuarios()->detach($user->id);

        if ($user->loja_id === $loja->id) {
            $primeiraLoja = $user->lojas()->first();
            $user->update(['loja_id' => $primeiraLoja?->id]);
        }

        return back()->with('success', 'Usuario desvinculado com sucesso.');
    }
}
