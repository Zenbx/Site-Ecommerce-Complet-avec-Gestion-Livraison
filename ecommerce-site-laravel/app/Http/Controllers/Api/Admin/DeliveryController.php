<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryPerson;
use App\Events\DeliveryAssigned;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controller dédié à la gestion des livraisons
 * 
 * Ce controller ne s'occupe QUE des livraisons (Delivery), pas des livreurs.
 * Il gère tout le cycle de vie d'une livraison :
 * - Consultation et filtrage des livraisons
 * - Assignation (manuelle ou automatique) à un livreur
 * - Réassignation en cas de problème
 * 
 * Philosophie : Un controller = Une ressource principale
 * Ici, la ressource c'est Delivery, donc on ne gère que ça.
 *
 * @OA\Tag(
 *     name="Admin Deliveries",
 *     description="Delivery management"
 * )
 */
class DeliveryController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des livraisons avec filtres avancés
     * 
     * GET /api/admin/deliveries
     * 
     * @OA\Get(
     *      path="/api/admin/deliveries",
     *      operationId="getDeliveries",
     *      tags={"Admin Deliveries"},
     *      summary="List deliveries",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="status", in="query", description="Filter by status (comma separated)", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="delivery_person_id", in="query", description="Filter by delivery person ID", required=false, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="date_from", in="query", description="Date from (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="date_to", in="query", description="Date to (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="search", in="query", description="Search by tracking code", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="per_page", in="query", description="Items per page", required=false, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="List of deliveries",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Delivery"))
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // On commence par une query de base avec les relations nécessaires
        // with() charge les relations en une seule requête (eager loading)
        // C'est BEAUCOUP plus performant que de les charger une par une
        $query = Delivery::with([
            'order.client',           // La commande et le client
            'deliveryPerson',         // Le livreur assigné
            'order.orderLines.product' // Les produits de la commande
        ]);

        // Filtrage par statut
        // On peut passer plusieurs statuts séparés par des virgules
        // Exemple : ?status=ASSIGNED,IN_TRANSIT
        if ($request->has('status')) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        // Filtrage par livreur
        // Utile pour voir toutes les livraisons d'un livreur
        if ($request->has('delivery_person_id')) {
            $query->where('delivery_person_id', $request->delivery_person_id);
        }

        // Filtrage par plage de dates
        // whereDate() compare uniquement la partie date, pas l'heure
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Recherche par tracking code
        // ILIKE est insensible à la casse (PostgreSQL)
        if ($request->has('search')) {
            $query->where('tracking_code', 'ILIKE', '%' . $request->search . '%');
        }

        // Tri par date de création (plus récent en premier)
        $query->orderBy('created_at', 'desc');

        // Pagination
        // On limite à 100 résultats max par page pour éviter les abus
        $perPage = min((int) $request->get('per_page', 25), 100);
        $deliveries = $query->paginate($perPage);

        // Transformation des données pour le frontend
        // On ne renvoie que ce qui est nécessaire, pas tout le modèle
        $data = $deliveries->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'tracking_code' => $delivery->tracking_code,
                'status' => $delivery->status,
                
                // Informations de la commande
                'order' => [
                    'id' => $delivery->order->id,
                    'order_number' => generate_order_number($delivery->order->id),
                    'total_amount' => format_currency($delivery->order->total_amount),
                    'items_count' => $delivery->order->orderLines->count(),
                ],
                
                // Informations du client
                'client' => [
                    'name' => $delivery->order->client->name,
                    'phone' => 'Visible lors de l\'assignation',
                ],
                
                // Informations du livreur (si assigné)
                'delivery_person' => $delivery->deliveryPerson ? [
                    'id' => $delivery->deliveryPerson->id,
                    'name' => $delivery->deliveryPerson->name,
                    'is_available' => $delivery->deliveryPerson->is_available,
                ] : null,
                
                'delivery_address' => $delivery->delivery_address,
                'created_at' => $delivery->created_at->format('Y-m-d H:i:s'),
                'delivered_at' => $delivery->delivered_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'total' => $deliveries->total(),
                'per_page' => $deliveries->perPage(),
                'last_page' => $deliveries->lastPage(),
            ]
        ], 200);
    }

    /**
     * Détails complets d'une livraison
     * 
     * GET /api/admin/deliveries/{id}
     * 
     * @OA\Get(
     *      path="/api/admin/deliveries/{id}",
     *      operationId="getDelivery",
     *      tags={"Admin Deliveries"},
     *      summary="Get delivery details",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Delivery ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Delivery details",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/Delivery")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Delivery not found")
     * )
     */
    public function show($id): JsonResponse
    {
        // findOrFail() lève une exception 404 si non trouvé
        // C'est mieux que find() car ça gère automatiquement l'erreur
        $delivery = Delivery::with([
            'order.client',
            'order.orderLines.product',
            'deliveryPerson'
        ])->findOrFail($id);

        // Construction de la timeline des événements
        // Cela permet au frontend d'afficher un historique visuel
        $timeline = $this->buildDeliveryTimeline($delivery);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $delivery->id,
                'tracking_code' => $delivery->tracking_code,
                'status' => $delivery->status,
                
                // Détails de la commande
                'order' => [
                    'id' => $delivery->order->id,
                    'order_number' => generate_order_number($delivery->order->id),
                    'total_amount' => format_currency($delivery->order->total_amount),
                    'payment_status' => $delivery->order->payment_status,
                    
                    // Liste détaillée des produits
                    'items' => $delivery->order->orderLines->map(function ($line) {
                        return [
                            'product_name' => $line->product->name,
                            'quantity' => $line->quantity,
                            'unit_price' => format_currency($line->unit_price),
                            'image_url' => $line->product->image_url,
                        ];
                    }),
                ],
                
                // Informations complètes du client
                'client' => [
                    'id' => $delivery->order->client->id,
                    'name' => $delivery->order->client->name,
                    'email' => $delivery->order->client->email,
                    'phone' => $delivery->order->client->address, // À ajuster selon ta structure
                ],
                
                // Informations du livreur
                'delivery_person' => $delivery->deliveryPerson ? [
                    'id' => $delivery->deliveryPerson->id,
                    'name' => $delivery->deliveryPerson->name,
                    'email' => $delivery->deliveryPerson->email,
                    'phone' => $delivery->deliveryPerson->address,
                    'is_available' => $delivery->deliveryPerson->is_available,
                ] : null,
                
                // Adresse de livraison
                'delivery_address' => $delivery->delivery_address,
                
                // Preuves de livraison
                'proof' => [
                    'confirmation_image' => $delivery->confirmation_img_url,
                    'qr_scanned_at' => $delivery->qr_scanned_at?->format('Y-m-d H:i:s'),
                    'qr_status' => $delivery->qr_status,
                ],
                
                // Timeline des événements
                'timeline' => $timeline,
                
                // Dates importantes
                'created_at' => $delivery->created_at->format('Y-m-d H:i:s'),
                'delivered_at' => $delivery->delivered_at?->format('Y-m-d H:i:s'),
                'updated_at' => $delivery->updated_at->format('Y-m-d H:i:s'),
            ]
        ], 200);
    }

    /**
     * Assignation AUTOMATIQUE d'un livreur
     * 
     * POST /api/admin/deliveries/{id}/auto-assign
     * 
     * @OA\Post(
     *      path="/api/admin/deliveries/{id}/auto-assign",
     *      operationId="autoAssignDelivery",
     *      tags={"Admin Deliveries"},
     *      summary="Auto assign delivery",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Delivery ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Delivery auto-assigned",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Livreur assigné automatiquement avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Delivery")
     *          )
     *      )
     * )
     */
    public function autoAssign($id): JsonResponse
    {
        $delivery = Delivery::with('order')->findOrFail($id);

        // Vérifier que la livraison n'est pas déjà assignée
        if ($delivery->delivery_person_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette livraison est déjà assignée à un livreur'
            ], 400);
        }

        // ALGORITHME D'ASSIGNATION INTELLIGENTE
        // =====================================
        
        // Étape 1 : Trouver les livreurs disponibles
        // Un livreur disponible est :
        // - Marqué comme disponible (is_available = true)
        // - N'a pas de livraison en cours (ASSIGNED, PICKED_UP, IN_TRANSIT)
        $availableDeliveryPerson = DeliveryPerson::where('is_available', true)
            ->whereDoesntHave('deliveries', function ($query) {
                // whereDoesntHave = "qui n'a pas"
                // On exclut les livreurs qui ont des livraisons en cours
                $query->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT']);
            })
            ->first(); // On prend le premier disponible

        // Étape 2 : Vérifier qu'on a trouvé quelqu'un
        if (!$availableDeliveryPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun livreur disponible pour le moment. Tous les livreurs sont occupés ou indisponibles.'
            ], 400);
        }

        // Étape 3 : Assigner le livreur
        // On utilise une transaction pour garantir la cohérence
        // Si quelque chose échoue, tout est annulé
        DB::beginTransaction();

        try {
            // Mise à jour de la livraison
            $delivery->update([
                'delivery_person_id' => $availableDeliveryPerson->id,
                'status' => 'ASSIGNED',
            ]);

            // Mise à jour du statut de la commande
            $delivery->order->update(['status' => 'SHIPPED']);

            // Déclencher l'événement pour les notifications
            // Cet événement sera capté par les listeners pour envoyer
            // des emails, des notifications push, etc.
            event(new DeliveryAssigned($delivery));

            // Envoyer la notification au livreur
            $this->notificationService->notifyDeliveryAssignment($delivery);

            // Si tout s'est bien passé, on valide la transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Livreur assigné automatiquement avec succès',
                'data' => [
                    'delivery_id' => $delivery->id,
                    'tracking_code' => $delivery->tracking_code,
                    'delivery_person' => [
                        'id' => $availableDeliveryPerson->id,
                        'name' => $availableDeliveryPerson->name,
                    ],
                    'assigned_at' => now()->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (\Exception $e) {
            // En cas d'erreur, on annule tout
            DB::rollBack();
            
            // On log l'erreur pour le débogage
            \Log::error('Erreur lors de l\'assignation automatique', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation automatique',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Assignation MANUELLE d'un livreur
     * 
     * POST /api/admin/deliveries/{id}/manual-assign
     * 
     * @OA\Post(
     *      path="/api/admin/deliveries/{id}/manual-assign",
     *      operationId="manualAssignDelivery",
     *      tags={"Admin Deliveries"},
     *      summary="Manual assign delivery",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Delivery ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="delivery_person_id", type="integer", example=1),
     *              @OA\Property(property="notes", type="string", example="Assignation prioritaire")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Delivery manually assigned",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Livreur assigné manuellement avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Delivery")
     *          )
     *      )
     * )
     */
    public function manualAssign(Request $request, $id): JsonResponse
    {
        // Validation des données
        $validated = $request->validate([
            'delivery_person_id' => 'required|exists:delivery_person,id',
            'notes' => 'nullable|string|max:500',
        ], [
            'delivery_person_id.required' => 'Vous devez sélectionner un livreur',
            'delivery_person_id.exists' => 'Le livreur sélectionné n\'existe pas',
        ]);

        $delivery = Delivery::with('order')->findOrFail($id);
        $deliveryPerson = DeliveryPerson::findOrFail($validated['delivery_person_id']);

        // Vérification de disponibilité (warning, pas bloquant)
        $warning = null;
        if (!$deliveryPerson->is_available) {
            $warning = 'Attention : Ce livreur est marqué comme indisponible';
        }

        // Vérifier si le livreur a déjà des livraisons en cours
        $activeDeliveriesCount = $deliveryPerson->deliveries()
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])
            ->count();

        if ($activeDeliveriesCount > 0) {
            $warning = "Attention : Ce livreur a déjà {$activeDeliveriesCount} livraison(s) en cours";
        }

        DB::beginTransaction();

        try {
            $delivery->update([
                'delivery_person_id' => $deliveryPerson->id,
                'status' => 'ASSIGNED',
            ]);

            $delivery->order->update(['status' => 'SHIPPED']);

            event(new DeliveryAssigned($delivery));
            $this->notificationService->notifyDeliveryAssignment($delivery);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Livreur assigné manuellement avec succès',
                'warning' => $warning,
                'data' => [
                    'delivery_id' => $delivery->id,
                    'tracking_code' => $delivery->tracking_code,
                    'delivery_person' => [
                        'id' => $deliveryPerson->id,
                        'name' => $deliveryPerson->name,
                        'active_deliveries' => $activeDeliveriesCount,
                    ],
                    'notes' => $validated['notes'] ?? null,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de l\'assignation manuelle', [
                'delivery_id' => $delivery->id,
                'delivery_person_id' => $deliveryPerson->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation'
            ], 500);
        }
    }

    /**
     * Réassignation d'une livraison
     * 
     * POST /api/admin/deliveries/{id}/reassign
     * 
     * @OA\Post(
     *      path="/api/admin/deliveries/{id}/reassign",
     *      operationId="reassignDelivery",
     *      tags={"Admin Deliveries"},
     *      summary="Reassign delivery",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Delivery ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="new_delivery_person_id", type="integer", example=2),
     *              @OA\Property(property="reason", type="string", example="Livreur indisponible")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Delivery reassigned",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Livraison réassignée avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Delivery")
     *          )
     *      )
     * )
     */
    public function reassign(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'new_delivery_person_id' => 'required|exists:delivery_person,id',
            'reason' => 'required|string|max:500|min:10',
        ], [
            'new_delivery_person_id.required' => 'Vous devez sélectionner un nouveau livreur',
            'reason.required' => 'Vous devez indiquer la raison de la réassignation',
            'reason.min' => 'La raison doit contenir au moins 10 caractères',
        ]);

        $delivery = Delivery::with(['deliveryPerson', 'order'])->findOrFail($id);

        // Vérifications préliminaires
        if (!$delivery->delivery_person_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette livraison n\'est pas encore assignée'
            ], 400);
        }

        // On ne peut pas réassigner une livraison déjà livrée ou échouée
        if (in_array($delivery->status, ['DELIVERED', 'FAILED'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de réassigner une livraison terminée'
            ], 400);
        }

        // Vérifier qu'on change bien de livreur
        if ($delivery->delivery_person_id == $validated['new_delivery_person_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Le nouveau livreur est le même que l\'actuel'
            ], 400);
        }

        $oldDeliveryPerson = $delivery->deliveryPerson;
        $newDeliveryPerson = DeliveryPerson::findOrFail($validated['new_delivery_person_id']);

        DB::beginTransaction();

        try {
            // Mise à jour de la livraison
            $delivery->update([
                'delivery_person_id' => $newDeliveryPerson->id,
                'status' => 'ASSIGNED', // On remet à ASSIGNED
            ]);

            // Log de la réassignation (pour l'historique)
            \Log::info('Livraison réassignée', [
                'delivery_id' => $delivery->id,
                'old_delivery_person' => $oldDeliveryPerson->name,
                'new_delivery_person' => $newDeliveryPerson->name,
                'reason' => $validated['reason'],
                'reassigned_by' => auth()->id(),
            ]);

            // Notifier l'ancien livreur que la livraison lui a été retirée
            // $this->notificationService->notifyDeliveryUnassigned($oldDeliveryPerson, $delivery);

            // Notifier le nouveau livreur de son assignation
            $this->notificationService->notifyDeliveryAssignment($delivery);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Livraison réassignée avec succès',
                'data' => [
                    'delivery_id' => $delivery->id,
                    'tracking_code' => $delivery->tracking_code,
                    'previous_delivery_person' => [
                        'id' => $oldDeliveryPerson->id,
                        'name' => $oldDeliveryPerson->name,
                    ],
                    'new_delivery_person' => [
                        'id' => $newDeliveryPerson->id,
                        'name' => $newDeliveryPerson->name,
                    ],
                    'reason' => $validated['reason'],
                    'reassigned_at' => now()->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de la réassignation', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réassignation'
            ], 500);
        }
    }

    /**
     * Construction de la timeline d'une livraison
     * 
     * Cette méthode privée construit un tableau d'événements
     * qui représente l'historique de la livraison.
     * Très utile pour afficher une timeline visuelle dans le frontend.
     */
    private function buildDeliveryTimeline(Delivery $delivery): array
    {
        $timeline = [];

        // Événement 1 : Création de la livraison
        $timeline[] = [
            'status' => 'PENDING',
            'label' => 'Livraison créée',
            'description' => 'La livraison a été enregistrée dans le système',
            'timestamp' => $delivery->created_at->format('Y-m-d H:i:s'),
            'icon' => 'package',
            'color' => 'gray',
        ];

        // Événement 2 : Assignation au livreur
        if ($delivery->delivery_person_id) {
            $timeline[] = [
                'status' => 'ASSIGNED',
                'label' => 'Assignée au livreur',
                'description' => "Assignée à {$delivery->deliveryPerson->name}",
                'timestamp' => $delivery->updated_at->format('Y-m-d H:i:s'),
                'icon' => 'user',
                'color' => 'blue',
            ];
        }

        // Événement 3 : Colis récupéré
        if (in_array($delivery->status, ['PICKED_UP', 'IN_TRANSIT', 'DELIVERED'])) {
            $timeline[] = [
                'status' => 'PICKED_UP',
                'label' => 'Colis récupéré',
                'description' => 'Le livreur a récupéré le colis',
                'timestamp' => $delivery->updated_at->format('Y-m-d H:i:s'),
                'icon' => 'truck',
                'color' => 'yellow',
            ];
        }

        // Événement 4 : En transit
        if (in_array($delivery->status, ['IN_TRANSIT', 'DELIVERED'])) {
            $timeline[] = [
                'status' => 'IN_TRANSIT',
                'label' => 'En route',
                'description' => 'Le livreur est en route vers le client',
                'timestamp' => $delivery->updated_at->format('Y-m-d H:i:s'),
                'icon' => 'map',
                'color' => 'orange',
            ];
        }

        // Événement 5 : Livré
        if ($delivery->status === 'DELIVERED') {
            $timeline[] = [
                'status' => 'DELIVERED',
                'label' => 'Livraison complétée',
                'description' => 'Le colis a été remis au client',
                'timestamp' => $delivery->delivered_at->format('Y-m-d H:i:s'),
                'icon' => 'check-circle',
                'color' => 'green',
            ];
        }

        // Événement alternatif : Échec
        if ($delivery->status === 'FAILED') {
            $timeline[] = [
                'status' => 'FAILED',
                'label' => 'Livraison échouée',
                'description' => 'La livraison n\'a pas pu être effectuée',
                'timestamp' => $delivery->updated_at->format('Y-m-d H:i:s'),
                'icon' => 'x-circle',
                'color' => 'red',
            ];
        }

        return $timeline;
    }

    
}