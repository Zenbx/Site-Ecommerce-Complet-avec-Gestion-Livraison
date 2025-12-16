<?php

use Illuminate\Support\Facades\Route;

// ============================================================================
// IMPORTS DES CONTROLLERS
// ============================================================================

// Controllers d'authentification
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Api\DeliveryPerson\AuthController as DeliveryPersonAuthController;

// Controllers Admin
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
//use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\DeliveryPersonController as AdminDeliveryPersonController;
use App\Http\Controllers\Api\Admin\DeliveryController as AdminDeliveryController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;

// Controllers Client
use App\Http\Controllers\Api\Client\ProductController as ClientProductController;
use App\Http\Controllers\Api\Client\CartController;
use App\Http\Controllers\Api\Client\OrderController as ClientOrderController;
use App\Http\Controllers\Api\Client\ProfileController as ClientProfileController;

// Controllers Delivery Person
use App\Http\Controllers\Api\DeliveryPerson\DeliveryController as DeliveryPersonDeliveryController;
use App\Http\Controllers\Api\DeliveryPerson\ProfileController as DeliveryPersonProfileController;
use App\Http\Controllers\Api\DeliveryPerson\DashboardController as DeliveryPersonDashboardController;

// Controllers utilitaires
use App\Http\Controllers\Api\MapController;

/*
|--------------------------------------------------------------------------
| ROUTES API
|--------------------------------------------------------------------------
|
| Organisation des routes par section fonctionnelle :
| 1. Authentification (publique)
| 2. Routes publiques (catalogue produits)
| 3. Routes Admin (protégées par auth:admin-api)
| 4. Routes Client (protégées par auth:client-api)
| 5. Routes Delivery Person (protégées par auth:delivery-api)
| 6. Routes utilitaires (cartographie, etc.)
|
| Chaque section utilise les middlewares appropriés pour la sécurité.
*/

// ============================================================================
// SECTION 1 : AUTHENTIFICATION (Routes publiques)
// ============================================================================

Route::prefix('auth')->name('auth.')->group(function () {
    
    // Authentification Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login');
        
        // Routes protégées admin auth
        Route::middleware('auth:admin-api')->group(function () {
            Route::post('/register', [AdminAuthController::class, 'register'])->name('register');
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
            Route::post('/refresh', [AdminAuthController::class, 'refresh'])->name('refresh');
            Route::get('/me', [AdminAuthController::class, 'me'])->name('me');
        });
    });
    
    // Authentification Client
    Route::prefix('client')->name('client.')->group(function () {
        Route::post('/register', [ClientAuthController::class, 'register'])->name('register');
        Route::post('/login', [ClientAuthController::class, 'login'])->name('login');
        
        Route::middleware('auth:client-api')->group(function () {
            Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');
            Route::post('/refresh', [ClientAuthController::class, 'refresh'])->name('refresh');
            Route::get('/me', [ClientAuthController::class, 'me'])->name('me');
        });
    });
    
    // Authentification Delivery Person
    Route::prefix('delivery-person')->name('delivery-person.')->group(function () {
        Route::post('/register', [DeliveryPersonAuthController::class, 'register'])->name('register');
        Route::post('/login', [DeliveryPersonAuthController::class, 'login'])->name('login');
        
        Route::middleware('auth:delivery-api')->group(function () {
            Route::post('/logout', [DeliveryPersonAuthController::class, 'logout'])->name('logout');
            Route::post('/refresh', [DeliveryPersonAuthController::class, 'refresh'])->name('refresh');
            Route::get('/me', [DeliveryPersonAuthController::class, 'me'])->name('me');
        });
    });
});

// ============================================================================
// SECTION 2 : ROUTES PUBLIQUES (Catalogue produits)
// ============================================================================

Route::prefix('public')->name('public.')->group(function () {
    // Catalogue produits accessible sans authentification
    Route::get('/products', [ClientProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ClientProductController::class, 'show'])->name('products.show');
    Route::get('/products/search', [ClientProductController::class, 'search'])->name('products.search');
    Route::get('/products/popular', [ClientProductController::class, 'popular'])->name('products.popular');
    Route::get('/categories', [ClientProductController::class, 'categories'])->name('categories');
});

// ============================================================================
// SECTION 3 : ROUTES ADMIN (Protégées)
// ============================================================================

Route::prefix('admin')->name('admin.')->middleware('auth:admin-api')->group(function () {
    
    // Dashboard Admin
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/overview', [AdminDashboardController::class, 'overview'])->name('overview');
        Route::get('/sales-chart', [AdminDashboardController::class, 'salesChart'])->name('sales-chart');
        Route::get('/top-products', [AdminDashboardController::class, 'topProducts'])->name('top-products');
        Route::get('/delivery-performance', [AdminDashboardController::class, 'deliveryPerformance'])->name('delivery-performance');
        Route::get('/recent-activity', [AdminDashboardController::class, 'recentActivity'])->name('recent-activity');
    });
    
    // Gestion des catégories
    Route::apiResource('categories', AdminCategoryController::class);
    
    // Gestion des produits
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [AdminProductController::class, 'index'])->name('index');
        Route::post('/', [AdminProductController::class, 'store'])->name('store');
        Route::get('/{product}', [AdminProductController::class, 'show'])->name('show');
        Route::put('/{product}', [AdminProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [AdminProductController::class, 'destroy'])->name('destroy');
        Route::patch('/{product}/stock', [AdminProductController::class, 'updateStock'])->name('update-stock');
    });
    
    // Gestion des commandes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->name('index');
        Route::get('/{order}', [AdminOrderController::class, 'show'])->name('show');
        Route::patch('/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{order}/assign-delivery', [AdminOrderController::class, 'assignDelivery'])->name('assign-delivery');
        Route::post('/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('cancel');
    });
    
    // Gestion des utilisateurs
    //Route::apiResource('users', AdminUserController::class);
    
    // Gestion des livreurs
    Route::prefix('delivery-persons')->name('delivery-persons.')->group(function () {
        Route::get('/', [AdminDeliveryPersonController::class, 'index'])->name('index');
        Route::post('/', [AdminDeliveryPersonController::class, 'store'])->name('store');
        Route::get('/{deliveryPerson}', [AdminDeliveryPersonController::class, 'show'])->name('show');
        Route::put('/{deliveryPerson}', [AdminDeliveryPersonController::class, 'update'])->name('update');
        Route::delete('/{deliveryPerson}', [AdminDeliveryPersonController::class, 'destroy'])->name('destroy');
        Route::patch('/{deliveryPerson}/availability', [AdminDeliveryPersonController::class, 'updateAvailability'])->name('update-availability');
        Route::get('/{deliveryPerson}/deliveries', [AdminDeliveryPersonController::class, 'deliveries'])->name('deliveries');
    });
    
    // Gestion des livraisons
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [AdminDeliveryController::class, 'index'])->name('index');
        Route::get('/{delivery}', [AdminDeliveryController::class, 'show'])->name('show');
        Route::post('/{delivery}/autoassign', [AdminDeliveryController::class, 'autoAssign'])->name('auto-assign');
        Route::post('/{delivery}/manualassign', [AdminDeliveryController::class, 'manualAssign'])->name('manual-assign');
        Route::patch('/{delivery}/reassign', [AdminDeliveryController::class, 'reassign'])->name('reassign');
    });
});

// ============================================================================
// SECTION 4 : ROUTES CLIENT (Protégées)
// ============================================================================

Route::prefix('client')->name('client.')->middleware('auth:client-api')->group(function () {
    
    // Profil client
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ClientProfileController::class, 'show'])->name('show');
        Route::put('/', [ClientProfileController::class, 'update'])->name('update');
        Route::post('/photo', [ClientProfileController::class, 'updatePhoto'])->name('update-photo');
        Route::put('/password', [ClientProfileController::class, 'changePassword'])->name('change-password');
        Route::get('/orders', [ClientProfileController::class, 'orderHistory'])->name('order-history');
    });
    
    // Catalogue produits (authentifié pour personnalisation)
    Route::get('/products', [ClientProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ClientProductController::class, 'show'])->name('products.show');
    Route::get('/products/search', [ClientProductController::class, 'search'])->name('products.search');
    Route::get('/categories', [ClientProductController::class, 'categories'])->name('categories');
    
    // Gestion du panier
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::post('/items', [CartController::class, 'addItem'])->name('add-item');
        Route::patch('/items/{cartLine}', [CartController::class, 'updateItem'])->name('update-item');
        Route::delete('/items/{cartLine}', [CartController::class, 'removeItem'])->name('remove-item');
        Route::delete('/', [CartController::class, 'clear'])->name('clear');
    });
    
    // Gestion des commandes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [ClientOrderController::class, 'index'])->name('index');
        Route::post('/', [ClientOrderController::class, 'store'])->name('store');
        Route::get('/{order}', [ClientOrderController::class, 'show'])->name('show');
        Route::get('/{order}/track', [ClientOrderController::class, 'track'])->name('track');
        Route::post('/{order}/cancel', [ClientOrderController::class, 'cancel'])->name('cancel');
    });
});

// ============================================================================
// SECTION 5 : ROUTES DELIVERY PERSON (Protégées)
// ============================================================================

Route::prefix('delivery-person')->name('delivery-person.')->middleware('auth:delivery-api')->group(function () {
    
    // Dashboard livreur
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/overview', [DeliveryPersonDashboardController::class, 'overview'])->name('overview');
        Route::get('/daily-summary', [DeliveryPersonDashboardController::class, 'dailySummary'])->name('daily-summary');
    });
    
    // Profil livreur
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [DeliveryPersonProfileController::class, 'show'])->name('show');
        Route::put('/', [DeliveryPersonProfileController::class, 'update'])->name('update');
        Route::post('/photo', [DeliveryPersonProfileController::class, 'updatePhoto'])->name('update-photo');
        Route::put('/password', [DeliveryPersonProfileController::class, 'changePassword'])->name('change-password');
        Route::patch('/availability', [DeliveryPersonProfileController::class, 'updateAvailability'])->name('update-availability');
        Route::get('/deliveries', [DeliveryPersonProfileController::class, 'deliveryHistory'])->name('delivery-history');
        Route::get('/statistics', [DeliveryPersonProfileController::class, 'statistics'])->name('statistics');
    });
    
    // Gestion des livraisons
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [DeliveryPersonDeliveryController::class, 'index'])->name('index');
        Route::get('/{delivery}', [DeliveryPersonDeliveryController::class, 'show'])->name('show');
        Route::post('/{delivery}/accept', [DeliveryPersonDeliveryController::class, 'accept'])->name('accept');
        Route::post('/{delivery}/decline', [DeliveryPersonDeliveryController::class, 'decline'])->name('decline');
        Route::post('/{delivery}/pickup', [DeliveryPersonDeliveryController::class, 'markAsPickedUp'])->name('pickup');
        Route::post('/{delivery}/start', [DeliveryPersonDeliveryController::class, 'start'])->name('start');
        Route::post('/{delivery}/location', [DeliveryPersonDeliveryController::class, 'updateLocation'])->name('update-location');
        Route::post('/{delivery}/scan-qr', [DeliveryPersonDeliveryController::class, 'scanQR'])->name('scan-qr');
        Route::post('/{delivery}/complete', [DeliveryPersonDeliveryController::class, 'complete'])->name('complete');
        Route::post('/{delivery}/report-issue', [DeliveryPersonDeliveryController::class, 'reportIssue'])->name('report-issue');
        Route::get('/{delivery}/route', [DeliveryPersonDeliveryController::class, 'getRoute'])->name('get-route');
    });
});

// ============================================================================
// SECTION 6 : ROUTES UTILITAIRES (Cartographie)
// ============================================================================

Route::prefix('map')->name('map.')->group(function () {
    // Ces endpoints peuvent être publics ou protégés selon vos besoins
    Route::post('/geocode', [MapController::class, 'geocode'])->name('geocode');
    Route::post('/route', [MapController::class, 'calculateRoute'])->name('route');
    Route::post('/distance', [MapController::class, 'calculateDistance'])->name('distance');
});

// ============================================================================
// ROUTE DE TEST (À supprimer en production)
// ============================================================================

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API E-Commerce Laravel fonctionne correctement',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});