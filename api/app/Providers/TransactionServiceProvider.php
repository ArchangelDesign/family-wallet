<?php

namespace App\Providers;

use App\Services\DatabaseService;
use App\Services\TransactionService;
use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            'App\Services\TransactionService', function ($app) {
                return new TransactionService($this->app->make(DatabaseService::class));
            }
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
