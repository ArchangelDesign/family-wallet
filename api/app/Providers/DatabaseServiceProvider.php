<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('App\Services\DatabaseService', function ($app) {
            return new DatabaseService(
                env('DB_HOST'),
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                env('DB_PORT'),
                env('DB_DATABASE'),
                env('DB_CONNECTION')
            );
        });
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
