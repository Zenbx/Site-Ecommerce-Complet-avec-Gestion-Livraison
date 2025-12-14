<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Api\DeliveryPerson\AuthController as DeliveryAuthController;

/*
|--------------------------------------------------------------------------
| Routes API
|--------------------------------------------------------------------------
|
| Toutes les routes définies dans ce fichier sont automatiquement préfixées
| par /api grâce à la configuration dans RouteServiceProvider.
| Elles retournent toutes du JSON et sont destinées à être consommées par
| nos applications frontend Angular et React Native.
|
*/

/*
|--------------------------------------------------------------------------
| ROUTES PUBLIQUES D'AUTHENTIFICATION
|--------------------------------------------------------------------------
|
| Ces routes ne nécessitent aucune authentification. Elles sont accessibles
| à tous et permettent aux utilisateurs de s'inscrire et de se connecter.
|
*/

// ============================================
// ROUTES ADMIN
// ============================================
// Toutes les routes admin sont préfixées par /api/admin
Route::prefix('admin')->group(function () {
    
    // Routes publiques (non authentifiées)
    // POST /api/admin/login - Connexion d'un administrateur
    Route::post('/login', [AdminAuthController::class, 'login']);
    
    // Routes protégées (nécessitent un token valide)
    // Ces routes sont protégées par le middleware auth:admin-api
    // qui vérifie que la requête contient un token valide émis pour un admin
    Route::middleware('auth:admin-api')->group(function () {
        // POST /api/admin/logout - Déconnexion (supprime le token)
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        
        // GET /api/admin/me - Récupérer le profil de l'admin connecté
        Route::get('/me', [AdminAuthController::class, 'me']);
        
        // Ici viendront toutes les autres routes protégées pour les admins :
        // - Gestion des produits (CRUD)
        // - Gestion des catégories
        // - Gestion des commandes
        // - Gestion des utilisateurs
        // - Gestion des livreurs
        // - Dashboard et statistiques
        // etc.
    });
});

// ============================================
// ROUTES CLIENT
// ============================================
// Toutes les routes client sont préfixées par /api/client
Route::prefix('client')->group(function () {
    
    // Routes publiques (non authentifiées)
    // POST /api/client/register - Inscription d'un nouveau client
    Route::post('/register', [ClientAuthController::class, 'register']);
    
    // POST /api/client/login - Connexion d'un client
    Route::post('/login', [ClientAuthController::class, 'login']);
    
    // Routes protégées (nécessitent un token valide de client)
    Route::middleware('auth:client-api')->group(function () {
        // POST /api/client/logout - Déconnexion
        Route::post('/logout', [ClientAuthController::class, 'logout']);
        
        // GET /api/client/me - Profil du client connecté
        Route::get('/me', [ClientAuthController::class, 'me']);
        
        // Ici viendront toutes les autres routes protégées pour les clients :
        // - Gestion du panier
        // - Passage de commandes
        // - Historique des commandes
        // - Suivi des livraisons
        // - Gestion du profil
        // - Liste de souhaits
        // etc.
    });
});

// ============================================
// ROUTES DELIVERY PERSON
// ============================================
// Toutes les routes livreur sont préfixées par /api/delivery-person
Route::prefix('delivery-person')->group(function () {
    
    // Routes publiques (non authentifiées)
    // POST /api/delivery-person/login - Connexion d'un livreur
    Route::post('/login', [DeliveryAuthController::class, 'login']);
    
    // Routes protégées (nécessitent un token valide de livreur)
    Route::middleware('auth:delivery-api')->group(function () {
        // POST /api/delivery-person/logout - Déconnexion
        Route::post('/logout', [DeliveryAuthController::class, 'logout']);
        
        // GET /api/delivery-person/me - Profil du livreur connecté
        Route::get('/me', [DeliveryAuthController::class, 'me']);
        
        // PATCH /api/delivery-person/availability - Mettre à jour la disponibilité
        Route::patch('/availability', [DeliveryAuthController::class, 'updateAvailability']);
        
        // Ici viendront toutes les autres routes protégées pour les livreurs :
        // - Liste des livraisons assignées
        // - Mise à jour du statut des livraisons
        // - Scan des QR codes
        // - Upload des preuves de livraison
        // - Mise à jour de la géolocalisation
        // - Historique des livraisons
        // etc.
    });
});

/*
|--------------------------------------------------------------------------
| ROUTES PUBLIQUES (CATALOGUE)
|--------------------------------------------------------------------------
|
| Ces routes sont accessibles sans authentification et permettent
| de consulter le catalogue de produits, les catégories, etc.
| Ces endpoints seront utilisés par les visiteurs non connectés du site.
|
*/

// Route::get('/products', [ProductController::class, 'index']);
// Route::get('/products/{id}', [ProductController::class, 'show']);
// Route::get('/categories', [CategoryController::class, 'index']);
// etc.