<?php

namespace App\Http\Controllers\Api\DeliveryPerson;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPerson;
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
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $deliveryPerson = DeliveryPerson::where('email', $request->email)->first();

        if (!$deliveryPerson || !Hash::check($request->password, $deliveryPerson->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        $token = $deliveryPerson->createToken('delivery-token', ['*'])->plainTextToken;

        return response()->json([
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
        ], 200);
    }

    /**
     * Déconnecter le livreur
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
     */
    public function me(Request $request)
    {
        $deliveryPerson = $request->user('delivery-api');

        return response()->json([
            'success' => true,
            'data' => new DeliveryPersonResource($deliveryPerson), 
        ], 200);
    }

    /**
     * Mettre à jour la disponibilité du livreur
     * 
     * Les livreurs peuvent marquer qu'ils sont disponibles ou non
     * pour recevoir de nouvelles livraisons
     */
    public function updateAvailability(Request $request)
    {
        $request->validate([
            'is_available' => 'required|boolean',
        ]);

        $deliveryPerson = $request->user('delivery-api');
        $deliveryPerson->is_available = $request->is_available;
        $deliveryPerson->save();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilité mise à jour',
            'data' => [
                'is_available' => $deliveryPerson->is_available,
            ],
        ], 200);
    }
}