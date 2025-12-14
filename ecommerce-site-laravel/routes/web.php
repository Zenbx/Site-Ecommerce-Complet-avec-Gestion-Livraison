<?php

use Illuminate\Support\Facades\Route;

// Fichier requis par Laravel, même pour une API pure
Route::get('/ping', function () {
    return response()->json([
        'pong' => true
    ]);
});
