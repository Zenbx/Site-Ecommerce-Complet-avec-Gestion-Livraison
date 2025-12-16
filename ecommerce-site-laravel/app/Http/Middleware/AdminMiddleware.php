<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware pour vérifier qu'un utilisateur authentifié est bien un Admin
 * 
 * IMPORTANT : Ce middleware doit être utilisé EN PLUS de auth:admin-api,
 * pas à la place. Il sert à vérifier que l'utilisateur authentifié a bien
 * les permissions d'un admin.
 */
class AdminMiddleware
{
    /**
     * Gère une requête entrante
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // CORRECTION : Spécifier explicitement le guard 'admin-api'
        // au lieu d'utiliser auth() qui utilise le guard par défaut
        $admin = $request->user('admin-api');
        
        // Vérifier que l'utilisateur est authentifié ET est une instance d'Admin
        if (!$admin || !($admin instanceof Admin)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être administrateur.'
            ], 403);
        }

        return $next($request);
    }
}
