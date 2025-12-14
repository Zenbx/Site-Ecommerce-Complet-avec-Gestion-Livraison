<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Cette option définit le guard d'authentification par défaut et le 
    | password broker par défaut pour votre application. Vous pouvez
    | changer ces valeurs selon vos besoins, mais ce sont de bons points
    | de départ pour la plupart des applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez définir chaque guard d'authentification pour votre
    | application. Une configuration par défaut a été définie pour vous
    | qui utilise le stockage de session et le provider d'utilisateurs Eloquent.
    |
    | Tous les guards ont un provider d'utilisateurs. Cela définit comment
    | les utilisateurs sont réellement récupérés de votre base de données
    | ou d'autres mécanismes de stockage utilisés par cette application
    | pour persister les données de vos utilisateurs.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        // Guard par défaut pour les applications web traditionnelles
        // Nous ne l'utilisons pas dans notre API, mais Laravel en a besoin
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        /*
         * GUARD ADMIN-API
         * 
         * Ce guard protège toutes les routes de l'interface d'administration.
         * Il utilise Laravel Sanctum pour générer des tokens d'authentification
         * et le provider 'admins' pour récupérer les utilisateurs administrateurs.
         * 
         * Quand un admin se connecte via POST /api/admin/login, il reçoit un
         * token Sanctum qui doit être inclus dans toutes les requêtes suivantes
         * vers les routes protégées par ce guard.
         */
        'admin-api' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
            'hash' => false,
        ],

        /*
         * GUARD CLIENT-API
         * 
         * Ce guard protège toutes les routes accessibles aux clients.
         * Les clients s'authentifient pour gérer leur panier, passer des
         * commandes, et suivre leurs livraisons.
         */
        'client-api' => [
            'driver' => 'sanctum',
            'provider' => 'clients',
            'hash' => false,
        ],

        /*
         * GUARD DELIVERY-API
         * 
         * Ce guard protège toutes les routes de l'application mobile des livreurs.
         * Les livreurs s'authentifient pour voir leurs livraisons assignées,
         * mettre à jour les statuts, et soumettre des preuves de livraison.
         */
        'delivery-api' => [
            'driver' => 'sanctum',
            'provider' => 'delivery-persons',
            'hash' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Tous les guards d'authentification ont un provider d'utilisateurs.
    | Cela définit comment les utilisateurs sont réellement récupérés de
    | votre base de données ou d'autres mécanismes de stockage utilisés
    | par cette application pour persister les données de vos utilisateurs.
    |
    | Si vous avez plusieurs tables ou modèles d'utilisateurs, vous pouvez
    | configurer plusieurs sources qui représentent chaque modèle/table.
    | Ces sources peuvent ensuite être assignées à n'importe quel guard
    | d'authentification supplémentaire que vous avez défini.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        // Provider par défaut (non utilisé dans notre API)
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        /*
         * PROVIDER ADMINS
         * 
         * Ce provider indique au guard admin-api où trouver les utilisateurs
         * administrateurs. Il utilise le modèle Eloquent App\Models\Admin
         * pour interroger la table 'admins' dans PostgreSQL.
         */
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        /*
         * PROVIDER CLIENTS
         * 
         * Ce provider indique au guard client-api où trouver les clients.
         * Il utilise le modèle Eloquent App\Models\Client.
         */
        'clients' => [
            'driver' => 'eloquent',
            'model' => App\Models\Client::class,
        ],

        /*
         * PROVIDER DELIVERY-PERSONS
         * 
         * Ce provider indique au guard delivery-api où trouver les livreurs.
         * Il utilise le modèle Eloquent App\Models\DeliveryPerson.
         */
        'delivery-persons' => [
            'driver' => 'eloquent',
            'model' => App\Models\DeliveryPerson::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Vous pouvez spécifier plusieurs configurations de réinitialisation
    | de mot de passe si vous avez plus d'une table ou modèle d'utilisateurs
    | dans l'application et que vous souhaitez avoir des paramètres de
    | réinitialisation de mot de passe séparés basés sur les types
    | d'utilisateurs spécifiques.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'clients' => [
            'provider' => 'clients',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'delivery-persons' => [
            'provider' => 'delivery-persons',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Ici vous pouvez définir le délai (en secondes) avant qu'une fenêtre
    | de confirmation de mot de passe expire et que les utilisateurs soient
    | invités à entrer à nouveau leur mot de passe via l'écran de confirmation.
    | Par défaut, le délai d'expiration dure trois heures.
    |
    */

    'password_timeout' => 10800,

];