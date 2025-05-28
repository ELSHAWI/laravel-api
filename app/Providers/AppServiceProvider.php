<?php

namespace App\Providers;

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
    public function boot()
    {
        if (env('APP_ENV') == 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }
          config([
        'database.default' => 'pgsql',
        'database.connections.mysql' => null // Disable MySQL completely
    ]);
    
    // Block any MySQL connection attempts
    app('db')->extend('mysql', function() {
        throw new \Exception("MySQL is disabled. Use PostgreSQL.");
    });
    }
}
