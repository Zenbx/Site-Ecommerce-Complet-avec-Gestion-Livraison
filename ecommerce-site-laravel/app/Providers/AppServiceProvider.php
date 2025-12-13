<?php

// ============================================
// 1. APP SERVICE PROVIDER
// app/Providers/AppServiceProvider.php
// ============================================

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        // Pour PostgreSQL et les anciennes versions de MySQL
        Schema::defaultStringLength(191);
        
        // Force HTTPS en production
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}