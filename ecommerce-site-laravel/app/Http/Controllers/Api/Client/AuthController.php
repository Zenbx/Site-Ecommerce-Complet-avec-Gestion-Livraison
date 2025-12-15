<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Http\Requests\Client\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Controller d'authentification pour les clients
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 */
class AuthController extends Controller
{
    /**
     * Inscrire un nouveau client
     * 
     * Contrairement aux admins qui sont créés manuellement par un super-admin,
     * les clients peuvent s'inscrire eux-mêmes via cette méthode.
     *
     * @OA\Post(
     *      path="/api/auth/client/register",
     *      operationId="registerClient",
     *      tags={"Authentication"},
     *      summary="Register a new client",
     *      description="Create a new client account and return an access token.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "email", "password", "address"},
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="secret123"),
     *              @OA\Property(property="address", type="string", example="123 Main St, City")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Client registered successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Inscription réussie"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="client", type="object",
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="John Doe"),
     *                      @OA\Property(property="email", type="string", example="john@example.com")
     *                  ),
     *                  @OA\Property(property="token", type="string", example="1|AbCdEf123456..."),
     *                  @OA\Property(property="token_type", type="string", example="Bearer")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation error")
     * )
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
     *
     * @OA\Post(
     *      path="/api/auth/client/login",
     *      operationId="loginClient",
     *      tags={"Authentication"},
     *      summary="Login client",
     *      description="Authenticate a client and return an access token.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email", "password"},
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="secret123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Login successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Connexion réussie"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="client", type="object",
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="John Doe"),
     *                      @OA\Property(property="email", type="string", example="john@example.com")
     *                  ),
     *                  @OA\Property(property="token", type="string", example="1|AbCdEf123456..."),
     *                  @OA\Property(property="token_type", type="string", example="Bearer")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Invalid credentials")
     * )
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
     *
     * @OA\Post(
     *      path="/api/auth/client/logout",
     *      operationId="logoutClient",
     *      tags={"Authentication"},
     *      summary="Logout client",
     *      description="Invalidate the current access token.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Logout successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *          )
     *      )
     * )
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
     *
     * @OA\Get(
     *      path="/api/auth/client/me",
     *      operationId="meClient",
     *      tags={"Authentication"},
     *      summary="Get client profile",
     *      description="Retrieve information about the currently authenticated client.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Client profile retrieved",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/Client")
     *          )
     *      )
     * )
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