<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LinkAgendamento;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LinkAgendamentoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);

        $lojaId = auth()->user()->loja_id;

        $link = LinkAgendamento::create([
            'loja_id'    => $lojaId,
            'cliente_id' => $request->cliente_id,
            'criado_por' => auth()->id(),
            'token'      => Str::random(48),
            'expires_at' => now()->addDay(),
        ]);

        $url = config('app.url') . '/agendar/' . $link->token;

        return response()->json([
            'token'      => $link->token,
            'url'        => $url,
            'expires_at' => $link->expires_at->toIso8601String(),
        ], 201);
    }
}
