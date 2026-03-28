<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLojaSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->loja_id) {
            if (!$request->routeIs('lojas.*') && !$request->routeIs('logout')) {
                return redirect()->route('lojas.index')
                    ->with('warning', 'Selecione ou cadastre uma loja para continuar.');
            }
        }

        return $next($request);
    }
}
