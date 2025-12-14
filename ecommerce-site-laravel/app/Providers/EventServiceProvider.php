<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Événements personnalisés pour votre application
        
        // Quand une commande est créée
        'App\Events\OrderCreated' => [
            'App\Listeners\SendOrderConfirmationEmail',
            'App\Listeners\UpdateProductStock',
            'App\Listeners\CreateDeliveryRecord',
        ],
        
        // Quand une commande est annulée
        'App\Events\OrderCancelled' => [
            'App\Listeners\RestoreProductStock',
            'App\Listeners\SendOrderCancellationEmail',
        ],
        
        // Quand une livraison est assignée
        'App\Events\DeliveryAssigned' => [
            'App\Listeners\NotifyDeliveryPerson',
            'App\Listeners\NotifyCustomer',
        ],
        
        // Quand une livraison est complétée
        'App\Events\DeliveryCompleted' => [
            'App\Listeners\SendDeliveryConfirmationEmail',
            'App\Listeners\UpdateDeliveryPersonStats',
        ],
        
        // Quand un produit est en rupture de stock
        'App\Events\ProductOutOfStock' => [
            'App\Listeners\NotifyAdminsLowStock',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
