<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * Controller de gestion du profil client
 * 
 * Ce controller permet aux clients de :
 * - Consulter leur profil
 * - Modifier leurs informations personnelles
 * - Changer leur mot de passe
 * - Gérer leur photo de profil
 * - Consulter leurs statistiques personnelles
 * 
 * Architecture :
 * Ce controller utilise l'injection de dépendances pour le NotificationService.
 * Il utilise également plusieurs helpers pour formater les données.
 *
 * @OA\Tag(
 *     name="Client Profile",
 *     description="Client profile management"
 * )
 */
class ProfileController extends Controller
{
    /**
     * Service de notifications injecté via le constructeur
     */
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    /**
     * Obtient le profil complet du client authentifié
     * 
     * GET /api/client/profile
     * 
     * @OA\Get(
     *      path="/api/client/profile",
     *      operationId="getClientProfile",
     *      tags={"Client Profile"},
     *      summary="Get client profile",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Client profile details",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="email", type="string"),
     *                  @OA\Property(property="address", type="string"),
     *                  @OA\Property(property="profile_picture", type="string"),
     *                  @OA\Property(property="statistics", type="object"),
     *                  @OA\Property(property="member_since", type="string")
     *              )
     *          )
     *      )
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Charger les relations nécessaires
        $client->load(['orders']);
        
        // Calculer des statistiques personnelles
        $totalSpent = $client->orders()
            ->where('payment_status', 'COMPLETED')
            ->sum('total_amount');
        
        $ordersCount = $client->orders()->count();
        
        $averageOrderValue = $ordersCount > 0 ? $totalSpent / $ordersCount : 0;
        
        // Utilisation du helper format_currency pour formater les montants
        $data = [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->email, // Vous devriez avoir un champ phone dans votre table
            'address' => $client->address,
            'profile_picture' => $client->profile_picture 
                ? Storage::url($client->profile_picture) 
                : null,
            
            // Statistiques formatées avec le helper
            'statistics' => [
                'total_orders' => $ordersCount,
                'total_spent' => format_currency($totalSpent),
                'total_spent_raw' => (float) $totalSpent,
                'average_order_value' => format_currency($averageOrderValue),
                'average_order_value_raw' => round($averageOrderValue, 2),
            ],
            
            // Utilisation du helper time_ago pour la date de création
            'member_since' => time_ago($client->created_at),
            'created_at' => $client->created_at->toIso8601String(),
        ];
        
        // Utilisation du helper generate_api_response
        return generate_api_response(true, $data);
    }
    
    /**
     * Met à jour le profil du client
     * 
     * PUT /api/client/profile
     * 
     * @OA\Put(
     *      path="/api/client/profile",
     *      operationId="updateClientProfile",
     *      tags={"Client Profile"},
     *      summary="Update client profile",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string"),
     *              @OA\Property(property="phone", type="string"),
     *              @OA\Property(property="address", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Client")
     *          )
     *      )
     * )
     */
    public function update(Request $request): JsonResponse
    {
        $client = $request->user('client-api');
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:clients,email,' . $client->id,
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ]);
        
        // Utilisation du helper format_phone_number si un téléphone est fourni
        if (isset($validated['phone'])) {
            $formattedPhone = format_phone_number($validated['phone']);
            
            if (!$formattedPhone) {
                return generate_api_response(
                    false,
                    null,
                    'Format de numéro de téléphone invalide',
                    400
                );
            }
            
            $validated['phone'] = $formattedPhone;
        }
        
        $client->update($validated);
        
        // Si l'email a changé, envoyer une notification
        if (isset($validated['email']) && $validated['email'] !== $client->getOriginal('email')) {
            // Utilisation du service de notification
            $this->notificationService->sendEmailNotification(
                $validated['email'],
                'Email modifié',
                'emails.email-changed',
                ['client' => $client]
            );
        }
        
        return generate_api_response(
            true,
            $client->fresh(),
            'Profil mis à jour avec succès'
        );
    }
    
    /**
     * Met à jour la photo de profil
     * 
     * POST /api/client/profile/photo
     * 
     * @OA\Post(
     *      path="/api/client/profile/photo",
     *      operationId="updateClientPhoto",
     *      tags={"Client Profile"},
     *      summary="Update profile photo",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(property="photo", type="string", format="binary")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Photo updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Photo de profil mise à jour"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="profile_picture", type="string")
     *              )
     *          )
     *      )
     * )
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $client = $request->user('client-api');
        
        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ]);
        
        // Supprimer l'ancienne photo si elle existe
        if ($client->profile_picture) {
            Storage::disk('public')->delete($client->profile_picture);
        }
        
        // Stocker la nouvelle photo
        $path = $request->file('photo')->store('profile-pictures', 'public');
        
        $client->update(['profile_picture' => $path]);
        
        return generate_api_response(
            true,
            [
                'profile_picture' => Storage::url($path),
            ],
            'Photo de profil mise à jour'
        );
    }
    
    /**
     * Change le mot de passe du client
     * 
     * PUT /api/client/profile/password
     * 
     * @OA\Put(
     *      path="/api/client/profile/password",
     *      operationId="changeClientPassword",
     *      tags={"Client Profile"},
     *      summary="Change password",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="current_password", type="string"),
     *              @OA\Property(property="new_password", type="string"),
     *              @OA\Property(property="new_password_confirmation", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Password changed",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Mot de passe modifié avec succès")
     *          )
     *      )
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $client = $request->user('client-api');
        
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
        ]);
        
        // Vérifier que le mot de passe actuel est correct
        if (!Hash::check($validated['current_password'], $client->password)) {
            return generate_api_response(
                false,
                null,
                'Le mot de passe actuel est incorrect',
                400
            );
        }
        
        // Mettre à jour le mot de passe
        $client->update([
            'password' => Hash::make($validated['new_password']),
        ]);
        
        // Envoyer une notification de sécurité
        $this->notificationService->sendEmailNotification(
            $client->email,
            'Mot de passe modifié',
            'emails.password-changed',
            ['client' => $client]
        );
        
        return generate_api_response(
            true,
            null,
            'Mot de passe modifié avec succès'
        );
    }
    
    /**
     * Obtient l'historique des commandes du client
     * 
     * GET /api/client/profile/orders
     * 
     * @OA\Get(
     *      path="/api/client/profile/orders",
     *      operationId="getClientOrderHistory",
     *      tags={"Client Profile"},
     *      summary="Get order history",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer", default=1)),
     *      @OA\Parameter(name="per_page", in="query", description="Items per page", required=false, @OA\Schema(type="integer", default=10)),
     *      @OA\Parameter(name="status", in="query", description="Filter by status", required=false, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="Order history",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="orders", type="array", @OA\Items(ref="#/components/schemas/Order")),
     *                  @OA\Property(property="pagination", type="object")
     *              )
     *          )
     *      )
     * )
     */
    public function orderHistory(Request $request): JsonResponse
    {
        $client = $request->user('client-api');
        
        $perPage = $request->get('per_page', 10);
        $status = $request->get('status');
        
        $query = $client->orders()
            ->with(['orderLines.product', 'delivery'])
            ->latest();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $orders = $query->paginate($perPage);
        
        // Transformer les données avec les helpers
        $orders->getCollection()->transform(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => generate_order_number($order->id),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => format_currency($order->total_amount),
                'total_amount_raw' => (float) $order->total_amount,
                'items_count' => $order->orderLines->count(),
                'created_at' => $order->created_at->toIso8601String(),
                'created_ago' => time_ago($order->created_at),
                'tracking_code' => $order->delivery ? $order->delivery->tracking_code : null,
            ];
        });
        
        return generate_api_response(true, [
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }
}