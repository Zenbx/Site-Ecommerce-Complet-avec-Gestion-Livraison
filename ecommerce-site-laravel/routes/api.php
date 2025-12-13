<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DeliveryController;

// ============================================
// ROUTES PUBLIQUES
// ============================================

Route::post('/auth/register', [AuthController::class, 'registerClient']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Catalogue public (sans auth)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// ============================================
// ROUTES PROTÉGÉES (AUTH REQUIRED)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    
    // --------------------------------------------
    // AUTHENTIFICATION
    // --------------------------------------------
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // --------------------------------------------
    // ADMIN - GESTION PRODUITS
    // --------------------------------------------
    Route::prefix('admin')->middleware('admin')->group(function () {
        
        // Produits
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);
            Route::patch('/{id}/stock', [ProductController::class, 'updateStock']);
        });

        // Commandes (Admin)
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'adminIndex']);
            Route::get('/{id}', [OrderController::class, 'show']);
            Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
            Route::post('/{id}/assign-delivery', [DeliveryController::class, 'assignDelivery']);
        });

        // Livraisons (Admin)
        Route::prefix('deliveries')->group(function () {
            Route::get('/', [DeliveryController::class, 'adminIndex']);
            Route::get('/{id}', [DeliveryController::class, 'show']);
        });
    });

    // --------------------------------------------
    // CLIENT - PANIER
    // --------------------------------------------
    Route::prefix('client/cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'store']);
        Route::patch('/items/{id}', [CartController::class, 'update']);
        Route::delete('/items/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // --------------------------------------------
    // CLIENT - COMMANDES
    // --------------------------------------------
    Route::prefix('client/orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
    });

    // --------------------------------------------
    // LIVREUR - LIVRAISONS
    // --------------------------------------------
    Route::prefix('delivery-person')->group(function () {
        
        // Liste et détails
        Route::get('/deliveries', [DeliveryController::class, 'index']);
        Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
        
        // Actions de livraison
        Route::post('/deliveries/{id}/pickup', [DeliveryController::class, 'pickup']);
        Route::post('/deliveries/{id}/start', [DeliveryController::class, 'start']);
        Route::post('/deliveries/{id}/scan-qr', [DeliveryController::class, 'scanQr']);
        Route::post('/deliveries/{id}/proof', [DeliveryController::class, 'submitProof']);
        Route::post('/deliveries/{id}/complete', [DeliveryController::class, 'complete']);
        Route::post('/deliveries/{id}/report-issue', [DeliveryController::class, 'reportIssue']);
        
        // Disponibilité
        Route::patch('/availability', [DeliveryController::class, 'updateAvailability']);
    });
});