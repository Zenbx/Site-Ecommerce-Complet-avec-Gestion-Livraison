<?php

use Illuminate\Support\Facades\Route;

// Controllers Admin
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\DeliveryPersonController as AdminDeliveryPersonController;

// Controllers Client
use App\Http\Controllers\Api\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Api\Client\CartController;
use App\Http\Controllers\Api\Client\OrderController as ClientOrderController;

// Controllers Delivery Person
use App\Http\Controllers\Api\DeliveryPerson\AuthController as DeliveryAuthController;
use App\Http\Controllers\Api\DeliveryPerson\DeliveryController as DeliveryPersonDeliveryController;

/*
|--------------------------------------------------------------------------
| API E-COMMERCE COMPLÈTE - ROUTES
|--------------------------------------------------------------------------
|
| Ce fichier définit l'intégralité des endpoints de votre système e-commerce
| avec livraison intégrée et suivi en temps réel.
|
| ARCHITECTURE :
| - Admin API (Angular) : Gestion complète du système
| - Client API (Web/Mobile) : Catalogue, panier, commandes
| - Delivery Person API (React Native Mobile) : Livraisons terrain
|
| AUTHENTICATION :
| Trois guards Sanctum indépendants : admin-api, client-api, delivery-api
| Chaque section a ses routes publiques (auth) et protégées (business)
|
*/

// ============================================================================
// SECTION 1 : ADMINISTRATION
// Prefix: /api/admin
// Guard: admin-api
// Application: Angular Dashboard
// ============================================================================

Route::prefix('admin')->name('admin.')->group(function () {
    
    // ========== AUTHENTICATION ==========
    // Routes publiques - génération de tokens
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->name('login');
    
    // Routes protégées - nécessitent un token admin valide
    Route::middleware('auth:admin-api')->group(function () {
        
        // Profil et déconnexion
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AdminAuthController::class, 'me'])->name('me');
        
        // ========== GESTION DES PRODUITS ==========
        // CRUD complet + gestion du stock
        Route::apiResource('products', AdminProductController::class);
        Route::patch('products/{product}/stock', [AdminProductController::class, 'updateStock'])
            ->name('products.update-stock');
        
        // ========== GESTION DES COMMANDES ==========
        // Consultation, changement de statut, assignation de livraisons
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index'])->name('index');
            Route::get('/{order}', [AdminOrderController::class, 'show'])->name('show');
            Route::patch('/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('update-status');
            Route::post('/{order}/assign-delivery', [AdminOrderController::class, 'assignDelivery'])->name('assign-delivery');
        });
        
        // ========== GESTION DES LIVREURS ==========
        // CRUD complet + disponibilité + statistiques
        Route::apiResource('delivery-persons', AdminDeliveryPersonController::class);
        Route::patch('delivery-persons/{deliveryPerson}/availability', [AdminDeliveryPersonController::class, 'updateAvailability'])
            ->name('delivery-persons.update-availability');
        Route::get('delivery-persons/{deliveryPerson}/statistics', [AdminDeliveryPersonController::class, 'statistics'])
            ->name('delivery-persons.statistics');
        
        // ========== DASHBOARD & STATISTIQUES ==========
        // Ces routes seront utilisées pour afficher le tableau de bord Angular
        // avec graphiques, KPIs, et métriques en temps réel
        // Vous les implémenterez plus tard selon vos besoins spécifiques
        
        // Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        // Route::get('/dashboard/revenue', [DashboardController::class, 'revenue'])->name('dashboard.revenue');
        // Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts'])->name('dashboard.top-products');
        
    });
});

// ============================================================================
// SECTION 2 : CLIENT
// Prefix: /api/client
// Guard: client-api
// Application: Web Angular + Mobile (optionnel)
// ============================================================================

Route::prefix('client')->name('client.')->group(function () {
    
    // ========== AUTHENTICATION ==========
    // Routes publiques
    Route::post('/register', [ClientAuthController::class, 'register'])->name('register');
    Route::post('/login', [ClientAuthController::class, 'login'])->name('login');
    
    // Routes protégées - nécessitent un token client valide
    Route::middleware('auth:client-api')->group(function () {
        
        // Profil et déconnexion
        Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');
        Route::get('/me', [ClientAuthController::class, 'me'])->name('me');
        
        // ========== GESTION DU PANIER ==========
        // Le panier est une structure temporaire avant la commande
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'show'])->name('show');
            Route::post('/items', [CartController::class, 'addItem'])->name('add-item');
            Route::patch('/items/{cartLine}', [CartController::class, 'updateItem'])->name('update-item');
            Route::delete('/items/{cartLine}', [CartController::class, 'removeItem'])->name('remove-item');
            Route::delete('/', [CartController::class, 'clear'])->name('clear');
        });
        
        // ========== GESTION DES COMMANDES ==========
        // Création, consultation, annulation, suivi
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [ClientOrderController::class, 'index'])->name('index');
            Route::post('/', [ClientOrderController::class, 'store'])->name('store');
            Route::get('/{order}', [ClientOrderController::class, 'show'])->name('show');
            Route::post('/{order}/cancel', [ClientOrderController::class, 'cancel'])->name('cancel');
            Route::get('/{order}/tracking', [ClientOrderController::class, 'tracking'])->name('tracking');
        });
        
        // ========== CATALOGUE PRODUITS ==========
        // Ces routes permettent au client de parcourir le catalogue
        // Vous les implémenterez selon vos besoins
        
        // Route::get('/products', [ClientProductController::class, 'index'])->name('products.index');
        // Route::get('/products/{product}', [ClientProductController::class, 'show'])->name('products.show');
        // Route::get('/categories', [ClientCategoryController::class, 'index'])->name('categories.index');
        
    });
});

// ============================================================================
// SECTION 3 : LIVREUR (DELIVERY PERSON)
// Prefix: /api/delivery-person
// Guard: delivery-api
// Application: React Native Mobile
// ============================================================================

Route::prefix('delivery-person')->name('delivery.')->group(function () {
    
    // ========== AUTHENTICATION ==========
    // Routes publiques
    Route::post('/login', [DeliveryAuthController::class, 'login'])->name('login');
    
    // Routes protégées - nécessitent un token livreur valide
    Route::middleware('auth:delivery-api')->group(function () {
        
        // Profil et déconnexion
        Route::post('/logout', [DeliveryAuthController::class, 'logout'])->name('logout');
        Route::get('/me', [DeliveryAuthController::class, 'me'])->name('me');
        
        // Gestion de la disponibilité
        Route::patch('/availability', [DeliveryAuthController::class, 'updateAvailability'])
            ->name('update-availability');
        
        // ========== GESTION DES LIVRAISONS ==========
        // Interface complète pour gérer les livraisons depuis l'app mobile
        Route::prefix('deliveries')->name('deliveries.')->group(function () {
            
            // Liste et détails
            Route::get('/', [DeliveryPersonDeliveryController::class, 'index'])->name('index');
            Route::get('/{delivery}', [DeliveryPersonDeliveryController::class, 'show'])->name('show');
            
            // Acceptation/Refus de livraisons
            Route::post('/{delivery}/accept', [DeliveryPersonDeliveryController::class, 'accept'])->name('accept');
            Route::post('/{delivery}/decline', [DeliveryPersonDeliveryController::class, 'decline'])->name('decline');
            
            // Progression de la livraison
            Route::post('/{delivery}/pickup', [DeliveryPersonDeliveryController::class, 'markAsPickedUp'])->name('pickup');
            Route::post('/{delivery}/start', [DeliveryPersonDeliveryController::class, 'start'])->name('start');
            
            // Tracking GPS en temps réel
            Route::post('/{delivery}/location', [DeliveryPersonDeliveryController::class, 'updateLocation'])->name('update-location');
            
            // Confirmation de livraison
            Route::post('/{delivery}/scan-qr', [DeliveryPersonDeliveryController::class, 'scanQRCode'])->name('scan-qr');
            Route::post('/{delivery}/proof', [DeliveryPersonDeliveryController::class, 'submitProof'])->name('submit-proof');
            Route::post('/{delivery}/complete', [DeliveryPersonDeliveryController::class, 'complete'])->name('complete');
            
            // Gestion des problèmes
            Route::post('/{delivery}/report-issue', [DeliveryPersonDeliveryController::class, 'reportIssue'])->name('report-issue');
            
            // Historique
            Route::get('/history/list', [DeliveryPersonDeliveryController::class, 'history'])->name('history');
        });
        
        // ========== STATISTIQUES LIVREUR ==========
        // Ces routes fournissent les métriques de performance au livreur
        // Vous les implémenterez selon vos besoins
        
        // Route::get('/statistics/today', [DeliveryPersonStatsController::class, 'today'])->name('stats.today');
        // Route::get('/statistics/week', [DeliveryPersonStatsController::class, 'week'])->name('stats.week');
        // Route::get('/statistics/month', [DeliveryPersonStatsController::class, 'month'])->name('stats.month');
        
    });
});

// ============================================================================
// ROUTES UTILITAIRES
// ============================================================================

// Route de fallback pour les endpoints inexistants
// Retourne une erreur 404 formatée en JSON au lieu de HTML
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint API non trouvé',
        'error' => 'La route demandée n\'existe pas. Vérifiez l\'URL et la méthode HTTP.',
    ], 404);
});