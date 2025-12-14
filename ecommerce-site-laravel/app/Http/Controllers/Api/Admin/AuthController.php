<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Resources\AdminResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Controller d'authentification pour les administrateurs
 * 
 * Ce controller gère toutes les opérations d'authentification spécifiques
 * aux administrateurs du système : connexion, déconnexion, et récupération
 * du profil. Il utilise le guard 'admin-api' pour toutes les opérations.
 */
class AuthController extends Controller
{
    /**
     * Connecter un administrateur
     * 
     * Cette méthode reçoit un email et un mot de passe, vérifie les credentials,
     * et si tout est valide, génère un token Sanctum qui permet à l'admin
     * d'accéder aux routes protégées.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        // Validation des données d'entrée
        // L'email doit être une adresse email valide et est obligatoire
        // Le mot de passe est obligatoire et doit être une chaîne de caractères
       
        // Chercher l'administrateur par son email dans la base de données
        // Si aucun admin n'existe avec cet email, $admin sera null
        $admin = Admin::where('email', $request->email)->first();

        // Vérification en deux étapes pour la sécurité :
        // 1. Est-ce que l'admin existe ?
        // 2. Si oui, est-ce que le mot de passe fourni correspond au hash stocké ?
        // 
        // Nous utilisons Hash::check() plutôt qu'une simple comparaison car
        // le mot de passe en base de données est hashé avec bcrypt pour la sécurité
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            // Si l'email n'existe pas OU si le mot de passe est incorrect,
            // nous lançons une exception de validation
            // 
            // Note importante de sécurité : nous ne disons jamais à l'utilisateur
            // si c'est l'email ou le mot de passe qui est incorrect. Nous donnons
            // toujours le même message générique. Cela empêche un attaquant de
            // deviner quels emails existent dans notre système.
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        // À ce stade, nous savons que l'admin existe et que le mot de passe est correct
        // Nous pouvons donc générer un token d'authentification
        // 
        // createToken() est une méthode fournie par le trait HasApiTokens
        // Le premier paramètre est le nom du token (utile pour avoir plusieurs tokens)
        // Le deuxième paramètre optionnel est un tableau de "abilities" (permissions)
        // 
        // Dans notre cas, nous donnons toutes les permissions avec ['*']
        // car nous gérerons les permissions au niveau des rôles plutôt qu'au niveau des tokens
        $token = $admin->createToken('admin-token', ['*'])->plainTextToken;

        // Retourner une réponse JSON avec les informations de l'admin et le token
        // Le client devra stocker ce token et l'inclure dans l'en-tête Authorization
        // de toutes les requêtes futures : Authorization: Bearer {token}
        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'admin' => new AdminResource($admin),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Déconnecter l'administrateur actuellement authentifié
     * 
     * Cette méthode supprime le token actuel de l'admin, ce qui le déconnecte
     * effectivement. Le token ne sera plus valide pour les requêtes futures.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // $request->user('admin-api') récupère l'admin actuellement authentifié
        // en utilisant le guard admin-api
        // 
        // currentAccessToken() retourne le token utilisé pour cette requête
        // delete() supprime ce token de la base de données
        // 
        // Après cette opération, le token ne sera plus valide et l'admin
        // devra se reconnecter pour obtenir un nouveau token
        $request->user('admin-api')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ], 200);
    }

    /**
     * Récupérer le profil de l'administrateur actuellement authentifié
     * 
     * Cette méthode est typiquement appelée par le frontend pour obtenir
     * les informations de l'utilisateur connecté, par exemple pour afficher
     * son nom dans la barre de navigation.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Récupérer le profil de l'admin connecté
     */
    public function me(Request $request)
    {
        // Ici aussi, nous utilisons AdminResource pour transformer le modèle
        // Au lieu de retourner directement $request->user('admin-api'),
        // nous l'enveloppons dans une AdminResource
        return response()->json([
            'success' => true,
            'data' => new AdminResource($request->user('admin-api')),
        ], 200);
    }
}