<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Auth routes (session-based for Sanctum SPA)
Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/switch-loja', [AuthController::class, 'switchLoja']);
});

// SPA catch-all — serve o Vue app para qualquer rota nao-API
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
