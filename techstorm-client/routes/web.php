<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home'); // Charge home.blade.php qui utilise layouts/app.blade.php
});

Route::get('/connexion', function () {
    return view('connexion'); // Charge connexion.blade.php qui utilise layouts/app.blade.php
});

Route::get('/inscription', function () {
    return view('inscription'); // Charge inscription.blade.php qui utilise layouts/app.blade.php
});

Route::get('/catalogue', function () {
    return view('catalogue'); // Charge catalogue.blade.php qui utilise layouts/app.blade.php
});

Route::get('/panier', function () {
    return view('panier'); // Charge panier.blade.php qui utilise layouts/app.blade.php
});

Route::get('/about', function () {
    return view('about'); // Charge about.blade.php qui utilise layouts/app.blade.php
});

Route::get('/contact', function () {
    return view('contact'); // Charge contact.blade.php qui utilise layouts/app.blade.php
});

Route::get('/account', function () {
    return view('account'); // Charge account.blade.php qui utilise layouts/app.blade.php
});

Route::get('/commandes', function () {
    return view('commandes'); // Charge commandes.blade.php qui utilise layouts/app.blade.php
});

Route::get('/favoris', function () {
    return view('favoris'); // Charge favoris.blade.php qui utilise layouts/app.blade.php
});

Route::get('/settings', function () {
    return view('settings'); // Charge settings.blade.php qui utilise layouts/app.blade.php
});


