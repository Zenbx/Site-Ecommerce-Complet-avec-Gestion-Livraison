<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Gestionnaire global d'exceptions pour l'API
 * 
 * Cette classe centralise la gestion de toutes les exceptions qui peuvent
 * survenir dans votre application. Elle transforme les exceptions PHP
 * en réponses JSON cohérentes et appropriées pour une API REST.
 */
class Handler extends ExceptionHandler
{
    /**
     * Liste des exceptions qui ne doivent pas être loggées
     * 
     * Certaines exceptions sont normales et attendues (comme ValidationException
     * quand un utilisateur envoie des données invalides). Nous ne voulons pas
     * polluer nos logs avec ces exceptions courantes.
     */
    protected $dontReport = [
        //
    ];

    /**
     * Liste des inputs qui ne doivent jamais apparaître dans les logs
     * 
     * Pour des raisons de sécurité, nous ne voulons jamais logger les mots de passe,
     * les tokens, ou autres données sensibles, même en cas d'erreur.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Enregistre les callbacks de gestion d'exceptions
     * 
     * Cette méthode est appelée au démarrage de l'application et nous permet
     * de définir comment différents types d'exceptions doivent être traités.
     */
    public function register(): void
    {
        // Gérer les exceptions de façon personnalisée pour les requêtes API
        $this->renderable(function (Throwable $e, $request) {
            // Vérifier si la requête attend une réponse JSON
            // Toutes nos routes API commencent par /api, donc nous vérifions cela
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->handleApiException($e, $request);
            }
            
            // Pour les autres requêtes (non-API), laisser Laravel gérer normalement
            return null;
        });
    }
    
    /**
     * Gère les exceptions pour les requêtes API
     * 
     * Cette méthode transforme toutes les exceptions en réponses JSON cohérentes
     * avec un format standardisé : { success, message, error, code }
     * 
     * @param Throwable $exception L'exception qui a été levée
     * @param \Illuminate\Http\Request $request La requête HTTP qui a causé l'exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException(Throwable $exception, $request)
    {
        // Initialiser les valeurs par défaut de la réponse
        $response = [
            'success' => false,
            'message' => 'Une erreur est survenue',
        ];
        
        $statusCode = 500; // Code HTTP par défaut pour erreur serveur
        
        // Traiter différents types d'exceptions de manière appropriée
        
        // 1. Erreurs de validation (données invalides envoyées par le client)
        if ($exception instanceof ValidationException) {
            $statusCode = 422; // Unprocessable Entity
            $response['message'] = 'Les données fournies sont invalides';
            $response['errors'] = $exception->errors();
            
        // 2. Erreurs d'authentication (token manquant ou invalide)
        } elseif ($exception instanceof AuthenticationException) {
            $statusCode = 401; // Unauthorized
            $response['message'] = 'Non authentifié. Veuillez vous connecter.';
            
        // 3. Modèle non trouvé (par exemple, Product::findOrFail(999) quand l'ID 999 n'existe pas)
        } elseif ($exception instanceof ModelNotFoundException) {
            $statusCode = 404; // Not Found
            $modelName = class_basename($exception->getModel());
            $response['message'] = "{$modelName} non trouvé(e)";
            
        // 4. Route non trouvée (endpoint inexistant)
        } elseif ($exception instanceof NotFoundHttpException) {
            $statusCode = 404;
            $response['message'] = 'Endpoint non trouvé';
            
        // 5. Méthode HTTP non autorisée (par exemple, GET au lieu de POST)
        } elseif ($exception instanceof MethodNotAllowedHttpException) {
            $statusCode = 405; // Method Not Allowed
            $response['message'] = 'Méthode HTTP non autorisée pour cet endpoint';
            
        // 6. Exceptions HTTP générales (peuvent être levées avec abort(403, 'message'))
        } elseif ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $response['message'] = $exception->getMessage() ?: 'Erreur HTTP';
            
        // 7. Toutes les autres exceptions (erreurs de code, erreurs de base de données, etc.)
        } else {
            $statusCode = 500;
            $response['message'] = 'Une erreur interne du serveur est survenue';
            
            // En mode debug (développement), inclure les détails de l'erreur
            // En production, ne jamais exposer les détails techniques des erreurs
            if (config('app.debug')) {
                $response['error'] = $exception->getMessage();
                $response['trace'] = $exception->getTraceAsString();
                $response['file'] = $exception->getFile();
                $response['line'] = $exception->getLine();
            }
        }
        
        // Ajouter le code HTTP à la réponse pour cohérence
        $response['code'] = $statusCode;
        
        // Logger l'exception si c'est une vraie erreur serveur
        // Les erreurs 4xx sont des erreurs client (mauvaises données, mauvaise requête)
        // et ne nécessitent généralement pas de logging détaillé
        if ($statusCode >= 500) {
            $this->report($exception);
        }
        
        // Retourner la réponse JSON formatée avec le code de statut approprié
        return response()->json($response, $statusCode);
    }
}