<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryPersonResource;
use App\Models\DeliveryPerson;
use App\Services\SupabaseStorageService;
use App\Mail\DeliveryPersonWelcome;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Controller pour la gestion des livreurs par les administrateurs
 * 
 * Ce controller permet aux admins de gérer l'équipe de livreurs :
 * - Créer de nouveaux comptes livreurs
 * - Consulter les profils et statistiques des livreurs
 * - Modifier les informations des livreurs
 * - Activer/désactiver des livreurs
 * - Suivre les performances et la disponibilité
 *
 * @OA\Tag(
 *     name="Admin Delivery People",
 *     description="Delivery person management"
 * )
 */
class DeliveryPersonController extends Controller
{
    /**
     * Liste tous les livreurs avec filtres et statistiques
     * 
     * GET /api/admin/delivery-persons
     * 
     * @OA\Get(
     *      path="/api/admin/delivery-persons",
     *      operationId="getDeliveryPeople",
     *      tags={"Admin Delivery People"},
     *      summary="List delivery people",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="is_available", in="query", description="Filter by availability", required=false, @OA\Schema(type="boolean")),
     *      @OA\Parameter(name="search", in="query", description="Search by name or email", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="sort_by", in="query", description="Sort by field", required=false, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="List of delivery people",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/DeliveryPerson"))
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = DeliveryPerson::query();
        
        // Filtrer par disponibilité
        if ($request->has('is_available')) {
            $isAvailable = filter_var($request->input('is_available'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_available', $isAvailable);
        }
        
        // Recherche par nom ou email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }
        
        // Charger le nombre de livraisons pour chaque livreur
        // withCount() est très efficace car il utilise une sous-requête SQL
        $query->withCount([
            'deliveries',
            'deliveries as completed_deliveries_count' => function($q) {
                $q->where('status', 'DELIVERED');
            },
            'deliveries as pending_deliveries_count' => function($q) {
                $q->whereIn('status', ['PENDING', 'ASSIGNED', 'PICKED_UP', 'IN_TRANSIT']);
            }
        ]);
        
        // Tri
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'created_at', 'deliveries_count'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }
        
        // Pagination
        $perPage = min((int) $request->input('per_page', 25), 100);
        $deliveryPersons = $query->paginate($perPage);
        
        // Calculer des statistiques globales
        $stats = [
            'total' => DeliveryPerson::count(),
            'available' => DeliveryPerson::where('is_available', true)->count(),
            'busy' => DeliveryPerson::whereHas('deliveries', function($q) {
                $q->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT']);
            })->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => DeliveryPersonResource::collection($deliveryPersons),
            'meta' => [
                'current_page' => $deliveryPersons->currentPage(),
                'last_page' => $deliveryPersons->lastPage(),
                'per_page' => $deliveryPersons->perPage(),
                'total' => $deliveryPersons->total(),
            ],
            'statistics' => $stats,
        ], 200);
    }
    
    /**
     * Crée un nouveau livreur dans le système
     * 
     * POST /api/admin/delivery-persons
     * 
     * @OA\Post(
     *      path="/api/admin/delivery-persons",
     *      operationId="createDeliveryPerson",
     *      tags={"Admin Delivery People"},
     *      summary="Create delivery person",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/DeliveryPerson")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Delivery person created",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Livreur créé avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/DeliveryPerson")
     *          )
     *      )
     * )
     */
   // app/Http/Controllers/Api/Admin/DeliveryPersonController.php

public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:delivery_persons,email',
        'id_card_number' => 'required|string|max:50|unique:delivery_persons,id_card_number',
        'address' => 'required|string|max:500',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    // Upload photo dans le dossier delivery_person_profiles
    if ($request->hasFile('photo')) {
        $validated['photo_url'] = SupabaseStorageService::upload(
            $request->file('photo'),
            'delivery_person_profiles'
        );
    }

    $temporaryPassword = 'Delivery' . rand(100000, 999999) . '!';

    $validated['password'] = $temporaryPassword;
    $validated['must_change_password'] = true;

    $deliveryPerson = DeliveryPerson::create($validated);

     Mail::to($deliveryPerson->email)
        ->send(new DeliveryPersonWelcome(
            $deliveryPerson,
            $temporaryPassword
        ));

    return response()->json([
        'success' => true,
        'message' => 'Livreur créé avec succès',
        'data' => [
            'delivery_person' => new DeliveryPersonResource($deliveryPerson),
            'credentials' => [
                'email' => $deliveryPerson->email,
            ],
            'note' => 'Le livreur doit changer ce mot de passe à sa première connexion',
        ],
    ], 201);
}
    /**
     * Affiche les détails complets d'un livreur
     * 
     * GET /api/admin/delivery-persons/{deliveryPerson}
     * 
     * @OA\Get(
     *      path="/api/admin/delivery-persons/{deliveryPerson}",
     *      operationId="getDeliveryPerson",
     *      tags={"Admin Delivery People"},
     *      summary="Get delivery person details",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="deliveryPerson", in="path", description="Delivery Person ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Delivery person details",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/DeliveryPerson")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Delivery person not found")
     * )
     */
    public function show(DeliveryPerson $deliveryPerson): JsonResponse
    {
        // Charger les statistiques détaillées
        $deliveryPerson->loadCount([
            'deliveries',
            'deliveries as completed_deliveries' => function($q) {
                $q->where('status', 'DELIVERED');
            },
            'deliveries as failed_deliveries' => function($q) {
                $q->where('status', 'FAILED');
            },
        ]);
        
        // Charger les dernières livraisons
        $deliveryPerson->load(['deliveries' => function($q) {
            $q->with('order')->latest()->take(10);
        }]);
        
        return response()->json([
            'success' => true,
            'data' => new DeliveryPersonResource($deliveryPerson),
        ], 200);
    }
    
    /**
     * Met à jour les informations d'un livreur
     * 
     * PUT/PATCH /api/admin/delivery-persons/{deliveryPerson}
     * 
     * @OA\Put(
     *      path="/api/admin/delivery-persons/{deliveryPerson}",
     *      operationId="updateDeliveryPerson",
     *      tags={"Admin Delivery People"},
     *      summary="Update delivery person",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="deliveryPerson", in="path", description="Delivery Person ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/DeliveryPerson")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Delivery person updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Livreur mis à jour avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/DeliveryPerson")
     *          )
     *      )
     * )
     */
    public function update(Request $request, DeliveryPerson $deliveryPerson): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('delivery_persons')->ignore($deliveryPerson->id),
            ],
            'password' => 'sometimes|required|string|min:8',
            'id_card_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('delivery_persons')->ignore($deliveryPerson->id),
            ],
            'address' => 'sometimes|required|string|max:500',
            'photo_url' => 'nullable|url|max:500',
            'is_available' => 'sometimes|boolean',
        ]);
        
        $deliveryPerson->update($validated);
        $deliveryPerson->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Livreur mis à jour avec succès',
            'data' => new DeliveryPersonResource($deliveryPerson),
        ], 200);
    }
    
    /**
     * Supprime ou désactive un livreur
     * 
     * DELETE /api/admin/delivery-persons/{deliveryPerson}
     * 
     * @OA\Delete(
     *      path="/api/admin/delivery-persons/{deliveryPerson}",
     *      operationId="deleteDeliveryPerson",
     *      tags={"Admin Delivery People"},
     *      summary="Delete delivery person",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="deliveryPerson", in="path", description="Delivery Person ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Delivery person deleted",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Livreur supprimé avec succès")
     *          )
     *      )
     * )
     */
    public function destroy(DeliveryPerson $deliveryPerson): JsonResponse
    {
        // Vérifier si le livreur a des livraisons en cours
        $hasActiveDeliveries = $deliveryPerson->deliveries()
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])
            ->exists();
        
        if ($hasActiveDeliveries) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce livreur car il a des livraisons en cours. Veuillez d\'abord les réassigner.',
            ], 400);
        }
        
        // Ne pas supprimer réellement si le livreur a un historique de livraisons
        if ($deliveryPerson->deliveries()->exists()) {
            $deliveryPerson->update(['is_available' => false]);
            
            return response()->json([
                'success' => true,
                'message' => 'Livreur désactivé avec succès (historique de livraisons préservé)',
                'data' => new DeliveryPersonResource($deliveryPerson),
            ], 200);
        }
        
        // Sinon, supprimer complètement
        $deliveryPerson->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Livreur supprimé avec succès',
        ], 200);
    }
    
    /**
     * Change la disponibilité d'un livreur
     * 
     * PATCH /api/admin/delivery-persons/{deliveryPerson}/availability
     */
    public function updateAvailability(Request $request, DeliveryPerson $deliveryPerson): JsonResponse
    {
        $validated = $request->validate([
            'is_available' => 'required|boolean',
        ]);
        
        $deliveryPerson->update(['is_available' => $validated['is_available']]);
        
        $status = $validated['is_available'] ? 'disponible' : 'indisponible';
        
        return response()->json([
            'success' => true,
            'message' => "Livreur marqué comme {$status}",
            'data' => new DeliveryPersonResource($deliveryPerson),
        ], 200);
    }
    
    /**
     * Récupère les statistiques de performance d'un livreur
     * 
     * GET /api/admin/delivery-persons/{deliveryPerson}/statistics
     */
    public function statistics(Request $request, DeliveryPerson $deliveryPerson): JsonResponse
    {
        // Période de calcul des statistiques (par défaut : 30 derniers jours)
        $period = $request->input('period', 30);
        $startDate = now()->subDays($period);
        
        $deliveries = $deliveryPerson->deliveries()->where('created_at', '>=', $startDate);
        
        $stats = [
            'period_days' => $period,
            'total_deliveries' => $deliveries->count(),
            'completed' => $deliveries->clone()->where('status', 'DELIVERED')->count(),
            'failed' => $deliveries->clone()->where('status', 'FAILED')->count(),
            'in_progress' => $deliveries->clone()->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])->count(),
            'success_rate' => 0,
            'average_delivery_time' => null,
        ];
        
        // Calculer le taux de succès
        if ($stats['total_deliveries'] > 0) {
            $stats['success_rate'] = round(($stats['completed'] / $stats['total_deliveries']) * 100, 2);
        }
        
        // Calculer le temps moyen de livraison
        // C'est la différence entre created_at et delivered_at pour les livraisons complétées
        $completedDeliveries = $deliveries->clone()
            ->where('status', 'DELIVERED')
            ->whereNotNull('delivered_at')
            ->get();
        
        if ($completedDeliveries->isNotEmpty()) {
            $totalMinutes = $completedDeliveries->sum(function($delivery) {
                return $delivery->created_at->diffInMinutes($delivery->delivered_at);
            });
            
            $averageMinutes = $totalMinutes / $completedDeliveries->count();
            $stats['average_delivery_time'] = round($averageMinutes) . ' minutes';
        }
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }
}