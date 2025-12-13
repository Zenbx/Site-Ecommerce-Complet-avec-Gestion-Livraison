<?php

// ============================================
// 6. CUSTOM SERVICE PROVIDER POUR LES SERVICES
// app/Providers/RepositoryServiceProvider.php
// ============================================

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Enregistrement des repositories si vous utilisez le pattern Repository
        
        // $this->app->bind(
        //     'App\Interfaces\ProductRepositoryInterface',
        //     'App\Repositories\ProductRepository'
        // );
        
        // $this->app->bind(
        //     'App\Interfaces\OrderRepositoryInterface',
        //     'App\Repositories\OrderRepository'
        // );
        
        // Services personnalisés
        $this->app->singleton('App\Services\QRCodeService', function ($app) {
            return new \App\Services\QRCodeService();
        });
        
        $this->app->singleton('App\Services\PaymentService', function ($app) {
            return new \App\Services\PaymentService();
        });
        
        $this->app->singleton('App\Services\NotificationService', function ($app) {
            return new \App\Services\NotificationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
