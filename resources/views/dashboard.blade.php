@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-shop text-primary fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Lojas</div>
                        <div class="fs-4 fw-bold">{{ \App\Models\Loja::where('ativa', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-bank2 text-success fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Bancos</div>
                        <div class="fs-4 fw-bold">{{ \App\Models\Banco::where('ativo', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-truck text-warning fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Fornecedores</div>
                        <div class="fs-4 fw-bold">{{ \App\Models\Fornecedor::where('ativo', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-people-fill text-info fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Usuarios</div>
                        <div class="fs-4 fw-bold">{{ \App\Models\User::where('ativo', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4 p-4">
        <h5>Bem-vindo ao LUA!</h5>
        <p class="text-muted mb-0">
            Sistema de gestao de fluxo de caixa do seu PetShop.
            Use o menu lateral para navegar entre os modulos.
        </p>
    </div>
@endsection
