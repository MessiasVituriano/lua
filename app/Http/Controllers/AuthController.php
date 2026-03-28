<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user->loja_id && $user->lojas()->count() > 0) {
                $user->update(['loja_id' => $user->lojas()->first()->id]);
            }

            return response()->json($user->load(['lojaAtiva', 'lojas']));
        }

        return response()->json([
            'errors' => ['email' => ['Credenciais invalidas.']]
        ], 422);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($validated);

        Auth::login($user);

        return response()->json($user, 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function switchLoja(Request $request)
    {
        $request->validate(['loja_id' => ['required', 'exists:lojas,id']]);

        $user = Auth::user();

        if ($user->lojas()->where('lojas.id', $request->loja_id)->exists()) {
            $user->update(['loja_id' => $request->loja_id]);
        }

        return response()->json($user->fresh()->load(['lojaAtiva', 'lojas']));
    }
}
