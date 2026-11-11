<?php

namespace App\Providers;

use App\Domains\Fiscal\Contracts\SunatGateway;
use App\Domains\Fiscal\Services\NullSunatGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SunatGateway::class, NullSunatGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
