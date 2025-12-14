<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Controller d'authentification pour les clients
 */
class AuthController extends Controller
{
    /**
     * Inscrire un nouveau client
     * 
     * Contrairement aux admins qui sont créés manuellement par un super-admin,
     * les clients peuvent s'inscrire eux-mêmes via cette méthode.
     */
    public function register(RegisterRequest $request)
    {
        // Validation des données d'inscription
       

        // Créer le nouveau client
       // Créer le nouveau client avec les données validées
        // validated() retourne seulement les champs qui ont passé la validation
        // C'est plus sûr que d'utiliser all() qui retournerait tous les champs
        $client = Client::create($request->validated());


        // Générer un token pour le nouveau client
        // Cela permet une connexion automatique après l'inscription
        $token = $client->createToken('client-token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Connecter un client
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        $token = $client->createToken('client-token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Déconnecter le client
     */
    public function logout(Request $request)
    {
        $request->user('client-api')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ], 200);
    }

    /**
     * Récupérer le profil du client connecté
     */
    public function me(Request $request)
    {
        $client = $request->user('client-api');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'address' => $client->address,
                'created_at' => $client->created_at,
            ],
        ], 200);
    }
}