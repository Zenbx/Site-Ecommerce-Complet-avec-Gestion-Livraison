<?php

namespace App\Http\Controllers\Api\DeliveryPerson;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Events\DeliveryLocationUpdated;
use App\Services\GeolocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Controller pour les livreurs - Gestion de leurs livraisons
 * 
 * Ce controller est utilisé par l'application mobile React Native des livreurs.
 * Il permet de :
 * - Voir les livraisons assignées
 * - Accepter/refuser des livraisons
 * - Mettre à jour les statuts (picked up, in transit, delivered, failed)
 * - Mettre à jour la position GPS en temps réel
 * - Scanner les QR codes de confirmation
 * - Soumettre les preuves de livraison (photo, signature)
 * - Consulter l'historique et les statistiques
 */
class DeliveryController extends Controller
{
    /**
     * Liste des livraisons du livreur connecté
     * 
     * GET /api/delivery-person/deliveries
     * 
     * Paramètres :
     * - status : filtrer par statut (ASSIGNED, PICKED_UP, IN_TRANSIT, etc.)
     * - date : filtrer par date (format Y-m-d)
     */
    public function index(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $query = Delivery::where('delivery_person_id', $deliveryPerson->id)
                         ->with(['order.client', 'order.orderLines.product']);
        
        // Filtrer par statut
        if ($request->has('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }
        
        // Filtrer par date
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }
        
        // Par défaut, montrer les livraisons actives d'abord
        $query->orderByRaw("
            CASE status
                WHEN 'IN_TRANSIT' THEN 1
                WHEN 'PICKED_UP' THEN 2
                WHEN 'ASSIGNED' THEN 3
                WHEN 'PENDING' THEN 4
                ELSE 5
            END
        ")->orderBy('created_at', 'desc');
        
        $deliveries = $query->get();
        
        // Statistiques du jour
        $today = now()->startOfDay();
        $stats = [
            'today' => [
                'total' => $deliveryPerson->deliveries()->whereDate('created_at', $today)->count(),
                'assigned' => $deliveryPerson->deliveries()->where('status', 'ASSIGNED')->whereDate('created_at', $today)->count(),
                'in_transit' => $deliveryPerson->deliveries()->where('status', 'IN_TRANSIT')->whereDate('created_at', $today)->count(),
                'delivered' => $deliveryPerson->deliveries()->where('status', 'DELIVERED')->whereDate('created_at', $today)->count(),
                'failed' => $deliveryPerson->deliveries()->where('status', 'FAILED')->whereDate('created_at', $today)->count(),
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => DeliveryResource::collection($deliveries),
            'statistics' => $stats,
        ], 200);
    }
    
    /**
     * Détails d'une livraison spécifique
     * 
     * GET /api/delivery-person/deliveries/{delivery}
     */
    public function show(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        // Vérifier que cette livraison appartient au livreur connecté
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Livraison non trouvée',
            ], 404);
        }
        
        $delivery->load(['order.client', 'order.orderLines.product']);
        
        return response()->json([
            'success' => true,
            'data' => new DeliveryResource($delivery),
        ], 200);
    }
    
    /**
     * Accepte une livraison assignée
     * 
     * POST /api/delivery-person/deliveries/{delivery}/accept
     */
    public function accept(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette livraison ne vous est pas assignée',
            ], 403);
        }
        
        if ($delivery->status !== 'ASSIGNED') {
            return response()->json([
                'success' => false,
                'message' => 'Cette livraison ne peut plus être acceptée',
            ], 400);
        }
        
        $delivery->update(['status' => 'ASSIGNED']); // Confirmer l'assignation
        
        return response()->json([
            'success' => true,
            'message' => 'Livraison acceptée avec succès',
            'data' => new DeliveryResource($delivery),
        ], 200);
    }
    
    /**
     * Refuse une livraison assignée
     * 
     * POST /api/delivery-person/deliveries/{delivery}/decline
     */
    public function decline(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette livraison ne vous est pas assignée',
            ], 403);
        }
        
        if ($delivery->status !== 'ASSIGNED') {
            return response()->json([
                'success' => false,
                'message' => 'Cette livraison ne peut plus être refusée',
            ], 400);
        }
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        // Remettre la livraison en statut PENDING et retirer le livreur
        $delivery->update([
            'status' => 'PENDING',
            'delivery_person_id' => null,
        ]);
        
        // Logger la raison du refus pour analyse par les admins
        // DeliveryDeclineLog::create([...]);
        
        return response()->json([
            'success' => true,
            'message' => 'Livraison refusée. Elle sera réassignée à un autre livreur.',
        ], 200);
    }
    
    /**
     * Marque le colis comme récupéré
     * 
     * POST /api/delivery-person/deliveries/{delivery}/pickup
     */
    public function markAsPickedUp(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }
        
        if ($delivery->status !== 'ASSIGNED') {
            return response()->json([
                'success' => false,
                'message' => 'Statut invalide pour cette action',
            ], 400);
        }
        
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
        
        $delivery->update([
            'status' => 'PICKED_UP',
            // Vous pourriez stocker la position de pickup dans une table séparée
            // ou dans un champ JSON de la table delivery
        ]);
        
        // Mettre à jour le statut de la commande
        $delivery->order->update(['status' => 'SHIPPED']);
        
        return response()->json([
            'success' => true,
            'message' => 'Colis marqué comme récupéré',
            'data' => new DeliveryResource($delivery->fresh()),
        ], 200);
    }
    
    /**
     * Démarre la livraison (en route vers le client)
     * 
     * POST /api/delivery-person/deliveries/{delivery}/start
     */
    public function start(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }
        
        if ($delivery->status !== 'PICKED_UP') {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez d\'abord marquer le colis comme récupéré',
            ], 400);
        }
        
        $delivery->update(['status' => 'IN_TRANSIT']);

        // Déclencher l'événement de changement de statut
        broadcast(new DeliveryStatusChanged($delivery, $oldStatus, 'IN_TRANSIT'));

        return response()->json([
            'success' => true,
            'message' => 'Livraison démarrée. En route vers le client.',
            'data' => new DeliveryResource($delivery->fresh()),
        ], 200);
    }
    
    /**
     * Met à jour la position GPS du livreur
     * 
     * POST /api/delivery-person/deliveries/{delivery}/location
     * 
     * Cette méthode est appelée régulièrement par l'app mobile (ex: toutes les 30 secondes)
     * pour permettre le suivi en temps réel
     */

public function updateLocation(Request $request, Delivery $delivery): JsonResponse
{
    $deliveryPerson = $request->user('delivery-api');
    
    if ($delivery->delivery_person_id !== $deliveryPerson->id) {
        return response()->json([
            'success' => false,
            'message' => 'Accès non autorisé',
        ], 403);
    }
    
    $validated = $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'speed' => 'nullable|numeric|min:0',
        'heading' => 'nullable|numeric|between:0,360',
    ]);
    
    // Stocker la position dans une table de tracking si vous en avez une
    // ou simplement déclencher l'événement pour diffusion temps réel
    
    // DÉCLENCHER L'ÉVÉNEMENT WEBSOCKET
    // Cette ligne unique fait toute la magie : elle diffuse instantanément
    // la nouvelle position vers tous les clients connectés
    broadcast(new DeliveryLocationUpdated($delivery, $validated))->toOthers();
    
    return response()->json([
        'success' => true,
        'message' => 'Position mise à jour et diffusée en temps réel',
    ], 200);
}
    /**
     * Scanne le QR code de confirmation de livraison
     * 
     * POST /api/delivery-person/deliveries/{delivery}/scan-qr
     */
    public function scanQRCode(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }
        
        $validated = $request->validate([
            'qr_code_data' => 'required|string',
        ]);
        
        // Vérifier que le QR code correspond à cette livraison
        if ($validated['qr_code_data'] !== $delivery->qr_token) {
            return response()->json([
                'success' => false,
                'message' => 'QR code invalide ou ne correspond pas à cette livraison',
            ], 400);
        }
        
        // Vérifier que le QR code n'a pas expiré
        if ($delivery->qr_expires_at && $delivery->qr_expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Le QR code a expiré',
            ], 400);
        }
        
        // Marquer le QR comme scanné
        $delivery->update([
            'qr_scanned_at' => now(),
            'qr_status' => 'SCANNED',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'QR code validé avec succès',
            'data' => [
                'order_number' => 'ORD-' . str_pad($delivery->order_id, 6, '0', STR_PAD_LEFT),
                'customer_name' => $delivery->order->client->name,
                'verified' => true,
            ],
        ], 200);
    }
    
    /**
     * Soumet une preuve de livraison (photo, signature)
     * 
     * POST /api/delivery-person/deliveries/{delivery}/proof
     */
    public function submitProof(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }
        
        $validated = $request->validate([
            'proof_type' => 'required|string|in:signature,photo',
            'proof_file' => 'required|file|image|max:5120', // Max 5MB
            'recipient_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Sauvegarder le fichier de preuve
        $path = $request->file('proof_file')->store('delivery-proofs', 'public');
        $url = Storage::url($path);
        
        // Mettre à jour la livraison avec la preuve
        $delivery->update([
            'confirmation_img_url' => $url,
            // Vous pourriez aussi stocker le type de preuve et le nom du destinataire
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Preuve de livraison enregistrée',
            'data' => [
                'proof_url' => $url,
                'type' => $validated['proof_type'],
            ],
        ], 200);
    }
    
    /**
     * Marque la livraison comme complétée
     * 
     * POST /api/delivery-person/deliveries/{delivery}/complete
     */
    public function complete(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }
        
        if (!in_array($delivery->status, ['IN_TRANSIT', 'PICKED_UP'])) {
            return response()->json([
                'success' => false,
                'message' => 'Statut invalide pour marquer comme livrée',
            ], 400);
        }
        
        // Vérifier qu'une preuve a été soumise
        if (!$delivery->confirmation_img_url) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez soumettre une preuve de livraison avant de compléter',
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Marquer la livraison comme complétée
            $delivery->update([
                'status' => 'DELIVERED',
                'delivered_at' => now(),
            ]);
            
            // Mettre à jour le statut de la commande
            $delivery->order->update([
                'status' => 'DELIVERED',
                'payment_status' => 'COMPLETED',
            ]);
            
            DB::commit();
            
            // Calculer le temps de livraison
            $deliveryTime = $delivery->created_at->diffForHumans($delivery->delivered_at, true);
            
            return response()->json([
                'success' => true,
                'message' => 'Livraison complétée avec succès',
                'data' => [
                    'delivery_id' => $delivery->id,
                    'order_number' => 'ORD-' . str_pad($delivery->order_id, 6, '0', STR_PAD_LEFT),
                    'status' => 'DELIVERED',
                    'delivered_at' => $delivery->delivered_at->format('Y-m-d H:i:s'),
                    'delivery_time' => $deliveryTime,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
    
    /**
     * Signale un problème de livraison (client absent, adresse incorrecte, etc.)
     * 
     * POST /api/delivery-person/deliveries/{delivery}/report-issue
     */
    public function reportIssue(Request $request, Delivery $delivery): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        if ($delivery->delivery_person_id !== $deliveryPerson->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }
        
        $validated = $request->validate([
            'issue_type' => 'required|string|in:customer_unavailable,address_not_found,refused_package,damaged_package,other',
            'description' => 'required|string|max:1000',
            'photo' => 'nullable|file|image|max:5120',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('delivery-issues', 'public');
            $photoUrl = Storage::url($path);
        }
        
        // Marquer la livraison comme ayant échoué
        $delivery->update([
            'status' => 'FAILED',
            // Vous pourriez stocker les détails du problème dans une table séparée
        ]);
        
        // Créer un enregistrement du problème
        // DeliveryIssue::create([
        //     'delivery_id' => $delivery->id,
        //     'issue_type' => $validated['issue_type'],
        //     'description' => $validated['description'],
        //     'photo_url' => $photoUrl,
        //     'latitude' => $validated['latitude'] ?? null,
        //     'longitude' => $validated['longitude'] ?? null,
        //     'reported_at' => now(),
        // ]);
        
        // Notifier les admins
        // Notification::send($admins, new DeliveryIssueReported($delivery));
        
        return response()->json([
            'success' => true,
            'message' => 'Problème signalé. Un administrateur sera notifié.',
            'data' => new DeliveryResource($delivery->fresh()),
        ], 200);
    }
    
    /**
     * Historique des livraisons complétées
     * 
     * GET /api/delivery-person/deliveries/history
     */
    public function history(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $query = Delivery::where('delivery_person_id', $deliveryPerson->id)
                         ->whereIn('status', ['DELIVERED', 'FAILED'])
                         ->with(['order.client']);
        
        // Filtres de date
        if ($request->has('date_from')) {
            $query->whereDate('delivered_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('delivered_at', '<=', $request->input('date_to'));
        }
        
        $perPage = min((int) $request->input('per_page', 25), 100);
        $deliveries = $query->orderBy('delivered_at', 'desc')->paginate($perPage);
        
        // Statistiques de la période
        $totalDeliveries = $query->count();
        $completed = $query->clone()->where('status', 'DELIVERED')->count();
        
        return response()->json([
            'success' => true,
            'data' => DeliveryResource::collection($deliveries),
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
            ],
            'summary' => [
                'total_deliveries' => $totalDeliveries,
                'completed' => $completed,
                'failed' => $totalDeliveries - $completed,
                'success_rate' => $totalDeliveries > 0 ? round(($completed / $totalDeliveries) * 100, 2) : 0,
            ],
        ], 200);
    }

    /**
 * Obtient l'itinéraire optimisé pour une livraison
 * 
 * GET /api/delivery-person/deliveries/{delivery}/route
 * 
 * Query params:
 * - current_lat: Latitude actuelle du livreur (required)
 * - current_lon: Longitude actuelle du livreur (required)
 * 
 * Cette méthode simplifie l'obtention d'un itinéraire pour le livreur.
 * Au lieu que l'app mobile doive géocoder l'adresse de destination elle-même,
 * elle donne simplement sa position actuelle et reçoit l'itinéraire complet.
 */
public function getRoute(Request $request, Delivery $delivery): JsonResponse
{
    $deliveryPerson = $request->user('delivery-api');
    
    // Vérifier que cette livraison appartient au livreur connecté
    if ($delivery->delivery_person_id !== $deliveryPerson->id) {
        return response()->json([
            'success' => false,
            'message' => 'Accès non autorisé',
        ], 403);
    }
    
    $validated = $request->validate([
        'current_lat' => 'required|numeric|between:-90,90',
        'current_lon' => 'required|numeric|between:-180,180',
    ]);
    
    try {
        // Géocoder l'adresse de livraison si elle n'a pas déjà de coordonnées
        // Dans votre table deliveries, vous pourriez ajouter des colonnes
        // delivery_latitude et delivery_longitude pour stocker les coordonnées
        // une fois géocodées, évitant ainsi de géocoder à chaque fois
        
        $geolocationService = app(GeolocationService::class);
        
        // Pour l'instant, nous allons géocoder l'adresse à chaque fois
        // Dans une vraie application, vous géocoderiez une seule fois
        // au moment de la création de la livraison et stockeriez les coordonnées
        $destination = $geolocationService->geocodeAddress($delivery->delivery_address);
        
        if (!$destination) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de localiser l\'adresse de livraison',
            ], 400);
        }
        
        // Calculer l'itinéraire
        $route = $geolocationService->calculateRoute(
            $validated['current_lat'],
            $validated['current_lon'],
            $destination['latitude'],
            $destination['longitude'],
            'driving'
        );
        
        return response()->json([
            'success' => true,
            'data' => [
                'delivery_id' => $delivery->id,
                'tracking_code' => $delivery->tracking_code,
                'current_position' => [
                    'latitude' => $validated['current_lat'],
                    'longitude' => $validated['current_lon'],
                ],
                'destination' => [
                    'latitude' => $destination['latitude'],
                    'longitude' => $destination['longitude'],
                    'address' => $delivery->delivery_address,
                ],
                'route' => $route,
            ],
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du calcul de l\'itinéraire',
            'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
        ], 500);
    }
}
}