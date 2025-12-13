<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Admin;
use App\Models\Client;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\Product;
use App\Models\Delivery;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate pour vérifier si l'utilisateur est un admin
        Gate::define('admin-access', function ($user) {
            return $user instanceof Admin;
        });

        // Gate pour vérifier si l'utilisateur est un client
        Gate::define('client-access', function ($user) {
            return $user instanceof Client;
        });

        // Gate pour vérifier si l'utilisateur est un livreur
        Gate::define('delivery-access', function ($user) {
            return $user instanceof DeliveryPerson;
        });

        // Gate pour gérer les produits (Admin uniquement)
        Gate::define('manage-products', function ($user) {
            return $user instanceof Admin;
        });

        // Gate pour voir une commande
        Gate::define('view-order', function ($user, Order $order) {
            if ($user instanceof Admin) {
                return true;
            }
            if ($user instanceof Client) {
                return $order->client_id === $user->id;
            }
            return false;
        });

        // Gate pour gérer une livraison
        Gate::define('manage-delivery', function ($user, Delivery $delivery) {
            if ($user instanceof Admin) {
                return true;
            }
            if ($user instanceof DeliveryPerson) {
                return $delivery->delivery_person_id === $user->id;
            }
            return false;
        });

        // Gate pour assigner des livraisons (Admin uniquement)
        Gate::define('assign-delivery', function ($user) {
            return $user instanceof Admin;
        });

        // Gate pour vérifier les rôles admin
        Gate::define('admin-role', function (Admin $user, string $role) {
            return $user->role === $role;
        });

        Gate::define('is-gestionnaire', function ($user) {
            return $user instanceof Admin && $user->role === 'GESTIONNAIRE';
        });

        Gate::define('is-superviseur', function ($user) {
            return $user instanceof Admin && in_array($user->role, ['SUPERVISEUR', 'ADMIN']);
        });

        Gate::define('is-super-admin', function ($user) {
            return $user instanceof Admin && $user->role === 'ADMIN';
        });
    }
}