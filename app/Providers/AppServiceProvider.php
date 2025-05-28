<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Exception;

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
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            $this->app['request']->server->set('HTTPS', true);
        }

        // Set PostgreSQL as default and disable MySQL
        config([
            'database.default' => 'pgsql',
            'database.connections.mysql' => null
        ]);
    
        // Throw error if anything tries to use MySQL
        $this->app['db']->extend('mysql', function () {
            throw new Exception("MySQL is disabled in this application. Please use PostgreSQL instead.");
        });
    }
}