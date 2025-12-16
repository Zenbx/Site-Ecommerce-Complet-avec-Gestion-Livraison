<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * Le Kernel HTTP de l'application
 * 
 * Dans Laravel 12, ce fichier est beaucoup plus simple qu'avant.
 * La plupart de la configuration des middlewares se fait maintenant
 * dans bootstrap/app.php avec une syntaxe plus moderne et lisible.
 * 
 * Ce fichier ne contient plus que les middlewares globaux et quelques
 * alias pour compatibilité avec du code ancien.
 */
class Kernel extends HttpKernel
{
    /**
     * Les middlewares HTTP globaux de l'application
     * 
     * Ces middlewares s'exécutent pour CHAQUE requête.
     * Laravel 12 gère maintenant beaucoup de choses automatiquement,
     * donc cette liste peut être assez courte.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * Alias de middlewares pour compatibilité
     * 
     * Laravel 12 gère maintenant les alias dans bootstrap/app.php,
     * mais on peut en garder quelques-uns ici pour compatibilité.
     */
    protected $middlewareAliases = [
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'must.change.password' => \App\Http\Middleware\MustChangePassword::class,
    ];
}