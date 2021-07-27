<?php

namespace App\Providers;

use App\Services\DatabaseService;
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
        if (empty(env('DB_CONNECTION'))) {
            throw new \RuntimeException('Environment is not configured.');
        }
        $this->app->singleton(
            'App\Services\DatabaseService', function ($app) {
                return new DatabaseService(
                    env('DB_HOST'),
                    env('DB_USERNAME'),
                    env('DB_PASSWORD'),
                    env('DB_PORT'),
                    env('DB_DATABASE'),
                    env('DB_CONNECTION')
                );
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
