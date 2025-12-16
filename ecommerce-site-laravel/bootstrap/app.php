<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Configuration de l'application Laravel 12
 * 
 * Dans Laravel 12 (comme dans Laravel 11), toute la configuration de l'application
 * se fait ici avec une syntaxe fluide et moderne. C'est beaucoup plus lisible et
 * plus intuitif que l'ancienne approche avec des tableaux dans Kernel.php.
 * 
 * Cette nouvelle architecture a été introduite pour simplifier la configuration
 * et la rendre plus accessible aux débutants.
 */
return Application::configure(basePath: dirname(__DIR__))
    /**
     * Configuration du routage
     * 
     * Ici on définit où se trouvent nos fichiers de routes.
     * Laravel chargera automatiquement ces fichiers et appliquera
     * les middlewares appropriés à chacun.
     */
    ->withRouting(
        // Routes web traditionnelles (vous n'en avez probablement pas besoin)
        web: __DIR__.'/../routes/web.php',
        
        // Routes API - C'EST VOTRE FICHIER PRINCIPAL
        // Ces routes seront automatiquement préfixées par /api
        // et auront le groupe de middleware 'api' appliqué
        api: __DIR__.'/../routes/api.php',
        
        // Routes de commandes Artisan
        commands: __DIR__.'/../routes/console.php',
        
        // Routes de broadcasting (WebSocket avec Reverb)
        channels: __DIR__.'/../routes/channels.php',
        
        // Endpoint de santé pour vérifier que l'application fonctionne
        health: '/up',
    )
    
    /**
     * Configuration des middlewares
     * 
     * C'EST ICI QUE NOUS RÉSOLVONS VOTRE PROBLÈME !
     * 
     * Dans Laravel 12, toute la configuration des middlewares se fait ici,
     * pas dans Kernel.php. La fonction reçoit un objet $middleware qui
     * fournit des méthodes pour configurer les groupes, les alias, etc.
     */
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * Configuration du groupe de middleware 'api'
         * 
         * CECI EST CRITIQUE POUR RÉSOUDRE VOS ERREURS 302/404 !
         * 
         * Par défaut, Laravel pourrait appliquer des middlewares inappropriés
         * pour une API. Nous redéfinissons explicitement le groupe 'api'
         * pour inclure SEULEMENT les middlewares appropriés pour une API :
         * - Pas de session (les APIs sont stateless)
         * - Pas de CSRF (les APIs utilisent des tokens Bearer)
         * - Pas de cookies (sauf pour CORS si nécessaire)
         * 
         * Note : La méthode exacte pour configurer les groupes de middlewares
         * dans Laravel 12 peut avoir légèrement évolué depuis Laravel 11.
         * Si cette syntaxe ne fonctionne pas, essayez les alternatives que
         * je vous donne plus bas.
         */
        
        // Approche 1 : Remplacer complètement le groupe 'api'
        // Cette approche supprime tous les middlewares par défaut et
        // les remplace par exactement ce dont nous avons besoin
        $middleware->group('api', [
            // Throttle limite le nombre de requêtes pour éviter les abus
            // 'api' fait référence au rate limiter défini ailleurs
            'throttle:api',
            
            // SubstituteBindings permet le route model binding
            // (transformer {product} en instance de Product automatiquement)
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        /**
         * Configuration des alias de middlewares
         * 
         * Ici on définit des noms courts pour nos middlewares personnalisés.
         * Ces alias peuvent ensuite être utilisés dans les routes avec
         * ->middleware('nom')
         */
        $middleware->alias([
            // Votre middleware admin personnalisé
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            
            // Votre middleware de changement de mot de passe
            'must.change.password' => \App\Http\Middleware\MustChangePassword::class,
        ]);
        
        /**
         * Exclusion de middlewares du groupe 'api'
         * 
         * IMPORTANT : Nous nous assurons explicitement que certains
         * middlewares web ne s'appliquent JAMAIS aux routes API.
         * 
         * Ces middlewares sont conçus pour les applications web traditionnelles
         * qui génèrent des pages HTML. Si ils s'appliquent à une API, ils
         * causeront exactement les problèmes que vous rencontrez (302, 404).
         */
        
        // Exclure la vérification CSRF pour les routes API
        // Les APIs utilisent des tokens Bearer, pas des tokens CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',  // Toutes les routes commençant par /api
        ]);
        
        // Si Laravel 12 a une méthode pour exclure explicitement StartSession
        // du groupe api, utilisez-la aussi. Dans Laravel 11, on ferait :
        // $middleware->removeFromGroup('api', \Illuminate\Session\Middleware\StartSession::class);
        
        /**
         * Configuration de la gestion des requêtes stateful (optionnel)
         * 
         * Si vous voulez que votre API supporte à la fois les tokens Bearer
         * ET l'authentification par cookies pour des SPAs (applications
         * Angular/React hébergées sur le même domaine), décommentez ceci :
         */
        // $middleware->statefulApi();
        
        /**
         * Middlewares globaux additionnels (si nécessaire)
         * 
         * Vous pouvez ajouter des middlewares qui s'appliquent à TOUTES
         * les requêtes, qu'elles soient web ou API :
         */
        // $middleware->append(\App\Http\Middleware\VotreMiddlewareGlobal::class);
        
        /**
         * Configuration CORS (optionnel mais recommandé)
         * 
         * Si vos applications Angular et React Native tournent sur des
         * domaines/ports différents de votre API, vous devez configurer CORS.
         * Laravel 12 devrait gérer cela automatiquement avec HandleCors qui
         * est dans les middlewares globaux, mais vous pouvez personnaliser :
         */
        // $middleware->cors(using: function () {
        //     return [
        //         'paths' => ['api/*'],
        //         'allowed_origins' => ['http://localhost:4200', 'http://localhost:19006'],
        //         'allowed_methods' => ['*'],
        //         'allowed_headers' => ['*'],
        //         'exposed_headers' => [],
        //         'max_age' => 0,
        //         'supports_credentials' => false,
        //     ];
        // });
    })
    
    /**
     * Configuration de la gestion des exceptions
     * 
     * Ici vous pouvez personnaliser comment Laravel gère les erreurs
     * et les exceptions. Pour une API, vous voudrez probablement
     * vous assurer que toutes les erreurs sont retournées en JSON.
     */
    ->withExceptions(function (Exceptions $exceptions): void {
        /**
         * Assurer que les erreurs d'authentification retournent du JSON
         * 
         * CECI EST IMPORTANT pour votre problème !
         * 
         * Par défaut, Laravel pourrait essayer de rediriger vers une page
         * de login HTML quand une requête non authentifiée arrive sur un
         * endpoint protégé. Nous forçons Laravel à toujours retourner du
         * JSON pour les routes API.
         */
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            // Retourner JSON si la requête attend du JSON OU si c'est une route API
            return $request->expectsJson() || $request->is('api/*');
        });
        
        /**
         * Personnalisation du rendu des exceptions d'authentification
         * 
         * Quand une requête non authentifiée arrive sur un endpoint protégé,
         * Laravel lance une AuthenticationException. Au lieu de rediriger
         * (comportement web), nous voulons retourner une réponse JSON 401.
         */
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            // Si c'est une requête API, retourner du JSON
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                ], 401);
            }
            
            // Pour les requêtes web (si vous en avez), comportement par défaut
            return null;
        });
    })
    ->create();