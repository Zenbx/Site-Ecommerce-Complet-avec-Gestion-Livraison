<?php

namespace App\Http\Controllers\Api\DeliveryPerson;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\StatisticsService;
use App\Models\DeliveryPerson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * Controller de gestion du profil livreur
 * 
 * Les livreurs ont des besoins spécifiques différents des clients.
 * Ils doivent pouvoir gérer leur disponibilité, leurs informations
 * de véhicule, et consulter leurs performances.
 * 
 * Architecture :
 * Ce controller injecte deux services :
 * - NotificationService pour les notifications
 * - StatisticsService pour calculer les performances du livreur
 */
class ProfileController extends Controller
{
    protected $notificationService;
    protected $statisticsService;
    
    /**
     * Injection de multiples services dans le constructeur
     * 
     * Ceci est un excellent exemple d'injection de dépendances multiples.
     * Laravel résout automatiquement ces dépendances grâce à son conteneur.
     */
    public function __construct(
        NotificationService $notificationService,
        StatisticsService $statisticsService
    ) {
        $this->notificationService = $notificationService;
        $this->statisticsService = $statisticsService;
    }
    
    /**
     * Obtient le profil complet du livreur
     * 
     * GET /api/delivery-person/profile
     * 
     * Cette méthode retourne non seulement les informations basiques
     * du livreur, mais aussi ses statistiques de performance calculées
     * par le StatisticsService.
     */
    public function show(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        // Utilisation du StatisticsService pour obtenir les statistiques
        // Notez comment nous utilisons $this-> pour accéder au service
        // qui a été injecté dans le constructeur
        $stats = $this->statisticsService->getDeliveryPersonStats($deliveryPerson, 'all');
        
        $data = [
            'id' => $deliveryPerson->id,
            'name' => $deliveryPerson->name,
            'email' => $deliveryPerson->email,
            'phone' => $deliveryPerson->email, // Devrait être un vrai champ phone
            'address' => $deliveryPerson->address,
            'id_card_number' => $deliveryPerson->id_card_number,
            'photo_url' => $deliveryPerson->photo_url 
                ? Storage::url($deliveryPerson->photo_url) 
                : null,
            'is_available' => $deliveryPerson->is_available,
            
            // Statistiques calculées par le service
            'statistics' => $stats,
            
            // Utilisation du helper time_ago
            'member_since' => time_ago($deliveryPerson->created_at),
            'created_at' => $deliveryPerson->created_at->toIso8601String(),
        ];
        
        return generate_api_response(true, $data);
    }
    
    /**
     * Met à jour le profil du livreur
     * 
     * PUT /api/delivery-person/profile
     */
    public function update(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:delivery_persons,email,' . $deliveryPerson->id,
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ]);
        
        // Utilisation du helper format_phone_number
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
        
        $deliveryPerson->update($validated);
        
        // Notification si email changé
        if (isset($validated['email']) && $validated['email'] !== $deliveryPerson->getOriginal('email')) {
            $this->notificationService->sendEmailNotification(
                $validated['email'],
                'Email modifié',
                'emails.email-changed',
                ['delivery_person' => $deliveryPerson]
            );
        }
        
        return generate_api_response(
            true,
            $deliveryPerson->fresh(),
            'Profil mis à jour avec succès'
        );
    }
    
    /**
     * Met à jour la disponibilité du livreur
     * 
     * PATCH /api/delivery-person/profile/availability
     * 
     * Cette méthode est cruciale pour que les livreurs puissent indiquer
     * s'ils sont disponibles pour recevoir de nouvelles livraisons.
     * Quand un livreur se met indisponible, le système arrête de lui
     * assigner de nouvelles livraisons.
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $validated = $request->validate([
            'is_available' => 'required|boolean',
            'reason' => 'nullable|string|max:255', // Optionnel : raison de l'indisponibilité
        ]);
        
        $deliveryPerson->update([
            'is_available' => $validated['is_available'],
        ]);
        
        // Logger la raison si fournie
        if (isset($validated['reason'])) {
            logger()->info('Livreur disponibilité changée', [
                'delivery_person_id' => $deliveryPerson->id,
                'is_available' => $validated['is_available'],
                'reason' => $validated['reason'],
            ]);
        }
        
        $message = $validated['is_available'] 
            ? 'Vous êtes maintenant disponible pour recevoir des livraisons'
            : 'Vous êtes maintenant indisponible. Vous ne recevrez plus de nouvelles livraisons.';
        
        return generate_api_response(
            true,
            [
                'is_available' => $deliveryPerson->is_available,
                'updated_at' => $deliveryPerson->updated_at->toIso8601String(),
            ],
            $message
        );
    }
    
    /**
     * Met à jour la photo de profil du livreur
     * 
     * POST /api/delivery-person/profile/photo
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        if ($deliveryPerson->photo_url) {
            Storage::disk('public')->delete($deliveryPerson->photo_url);
        }
        
        $path = $request->file('photo')->store('delivery-persons/photos', 'public');
        
        $deliveryPerson->update(['photo_url' => $path]);
        
        return generate_api_response(
            true,
            ['photo_url' => Storage::url($path)],
            'Photo de profil mise à jour'
        );
    }
    
    /**
     * Change le mot de passe du livreur
     * 
     * PUT /api/delivery-person/profile/password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
        ]);
        
        if (!Hash::check($validated['current_password'], $deliveryPerson->password)) {
            return generate_api_response(
                false,
                null,
                'Le mot de passe actuel est incorrect',
                400
            );
        }
        
        $deliveryPerson->update([
            'password' => Hash::make($validated['new_password']),
        ]);
        
        $this->notificationService->sendEmailNotification(
            $deliveryPerson->email,
            'Mot de passe modifié',
            'emails.password-changed',
            ['delivery_person' => $deliveryPerson]
        );
        
        return generate_api_response(
            true,
            null,
            'Mot de passe modifié avec succès'
        );
    }
    
    /**
     * Obtient l'historique des livraisons du livreur
     * 
     * GET /api/delivery-person/profile/deliveries
     * 
     * Cette méthode est similaire à orderHistory pour les clients,
     * mais retourne l'historique des livraisons effectuées par le livreur.
     */
    public function deliveryHistory(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        
        $query = $deliveryPerson->deliveries()
            ->with(['order.client'])
            ->latest();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $deliveries = $query->paginate($perPage);
        
        // Transformation avec les helpers
        $deliveries->getCollection()->transform(function ($delivery) {
            $deliveryTime = null;
            if ($delivery->status === 'DELIVERED' && $delivery->delivered_at) {
                $minutes = $delivery->created_at->diffInMinutes($delivery->delivered_at);
                $deliveryTime = $minutes . ' minutes';
            }
            
            return [
                'id' => $delivery->id,
                'tracking_code' => $delivery->tracking_code,
                'order_number' => generate_order_number($delivery->order_id),
                'customer_name' => $delivery->order->client->name,
                'delivery_address' => $delivery->delivery_address,
                'status' => $delivery->status,
                'created_at' => $delivery->created_at->toIso8601String(),
                'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                'delivery_time' => $deliveryTime,
                'amount' => format_currency($delivery->order->total_amount),
            ];
        });
        
        return generate_api_response(true, [
            'deliveries' => $deliveries->items(),
            'pagination' => [
                'current_page' => $deliveries->currentPage(),
                'total' => $deliveries->total(),
                'per_page' => $deliveries->perPage(),
                'last_page' => $deliveries->lastPage(),
            ],
        ]);
    }
    
    /**
     * Obtient les statistiques de performance du livreur
     * 
     * GET /api/delivery-person/profile/statistics
     * 
     * Cette méthode utilise intensivement le StatisticsService pour
     * calculer toutes les métriques de performance du livreur.
     */
    public function statistics(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $period = $request->get('period', 'month'); // today, week, month, year, all
        
        // Utilisation du StatisticsService
        // Ceci est un excellent exemple de délégation de la logique métier
        // complexe à un service dédié plutôt que de mettre tout dans le controller
        $stats = $this->statisticsService->getDeliveryPersonStats($deliveryPerson, $period);
        
        return generate_api_response(true, $stats);
    }
}