<?php

namespace App\Http\Controllers\Api\DeliveryPerson;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPerson;
use App\Http\Resources\DeliveryPersonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Controller d'authentification pour les livreurs
 */
class AuthController extends Controller
{
    /**
     * Connecter un livreur
     *
     * @OA\Post(
     *      path="/api/auth/delivery-person/login",
     *      operationId="loginDeliveryPerson",
     *      tags={"Authentication"},
     *      summary="Login delivery person",
     *      description="Authenticate a delivery person and return an access token.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email", "password"},
     *              @OA\Property(property="email", type="string", format="email", example="driver@example.com"),
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
     *                  @OA\Property(property="delivery_person", ref="#/components/schemas/DeliveryPerson"),
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
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $deliveryPerson = DeliveryPerson::where('email', $request->email)->first();

    if (!$deliveryPerson || !Hash::check($request->password, $deliveryPerson->password)) {
        throw ValidationException::withMessages([
            'email' => ['Les informations d\'identification fournies sont incorrectes.'],
        ]);
    }

    // Vérifier si le livreur est actif (optionnel)
    // if (!$deliveryPerson->is_available) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Votre compte est désactivé. Contactez l\'administrateur.',
    //     ], 403);
    // }

    $token = $deliveryPerson->createToken('delivery-token', ['*'])->plainTextToken;

    $response = [
        'success' => true,
        'message' => 'Connexion réussie',
        'data' => [
            'delivery_person' => [
                'id' => $deliveryPerson->id,
                'name' => $deliveryPerson->name,
                'email' => $deliveryPerson->email,
                'is_available' => $deliveryPerson->is_available,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ],
    ];

    // ⚠️ IMPORTANT : Vérifier si le mot de passe doit être changé
    if ($deliveryPerson->must_change_password) {
        $response['must_change_password'] = true;
        $response['warning'] = 'Vous devez changer votre mot de passe temporaire avant de continuer.';
    }

    return response()->json($response, 200);
}

    /**
     * Déconnecter le livreur
     *
     * @OA\Post(
     *      path="/api/auth/delivery-person/logout",
     *      operationId="logoutDeliveryPerson",
     *      tags={"Authentication"},
     *      summary="Logout delivery person",
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
        $request->user('delivery-api')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ], 200);
    }

    /**
     * Récupérer le profil du livreur connecté
     *
     * @OA\Get(
     *      path="/api/auth/delivery-person/me",
     *      operationId="meDeliveryPerson",
     *      tags={"Authentication"},
     *      summary="Get delivery person profile",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Profile retrieved",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/DeliveryPerson")
     *          )
     *      )
     * )
     */
    public function me(Request $request)
    {
        $deliveryPerson = $request->user('delivery-api');

        return response()->json([
            'success' => true,
            'data' => new DeliveryPersonResource($deliveryPerson), 
        ], 200);
    }


}