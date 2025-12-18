<?php

use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__.'/../bootstrap/app.php';

// ⚠️ NE PAS UTILISER ça : $app->handleRequest(Request::capture());
// ⚠️ Ça génère une erreur dans votre version

// ✅ UTILISEZ CE CODE À LA PLACE (garanti sans erreur) :
if ($app->bound(Kernel::class)) {
    $kernel = $app->make(Kernel::class);
    $response = $kernel->handle(
        $request = Request::capture()
    );
    $response->send();
    $kernel->terminate($request, $response);
} else {
    // Fallback si Kernel n'est pas disponible
    $app->run();
}