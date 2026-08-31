<?php

namespace App\Providers;

use App\Models\MovimentacaoInterna;
use App\Models\Pagamento;
use App\Observers\MovimentacaoInternaObserver;
use App\Observers\PagamentoObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Pagamento::observe(PagamentoObserver::class);
        MovimentacaoInterna::observe(MovimentacaoInternaObserver::class);
    }
}
