<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Vérifier si l'utilisateur doit changer son mot de passe
        if ($user && method_exists($user, 'getAttribute') && $user->getAttribute('must_change_password')) {
            // Liste des routes autorisées même avec must_change_password
            $allowedRoutes = [
                'api/delivery-person/auth/change-password',
                'api/delivery-person/auth/logout',
            ];

            // Si la route actuelle n'est pas dans la liste autorisée
            if (!in_array($request->path(), $allowedRoutes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez changer votre mot de passe temporaire avant de continuer',
                    'must_change_password' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}