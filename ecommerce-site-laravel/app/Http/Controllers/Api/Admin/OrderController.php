<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\DeliveryPerson;
use App\Events\DeliveryAssigned;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Controller pour la gestion des commandes côté administrateur
 * 
 * Ce controller donne aux admins un contrôle total sur toutes les commandes
 * du système : consultation, modification de statut, assignation de livraisons,
 * génération de rapports, etc.
 *
 * @OA\Tag(
 *     name="Admin Orders",
 *     description="Order management for admins"
 * )
 */
class OrderController extends Controller
{
    /**
     * Liste toutes les commandes avec filtres et recherche
     * 
     * GET /api/admin/orders
     *
     * @OA\Get(
     *      path="/api/admin/orders",
     *      operationId="getAdminOrders",
     *      tags={"Admin Orders"},
     *      summary="List orders (Admin)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer", default=1)),
     *      @OA\Parameter(name="per_page", in="query", description="Items per page", required=false, @OA\Schema(type="integer", default=25)),
     *      @OA\Parameter(name="status", in="query", description="Filter by status", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="payment_status", in="query", description="Filter by payment status", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="client_id", in="query", description="Filter by client ID", required=false, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="date_from", in="query", description="Date from (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="date_to", in="query", description="Date to (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="search", in="query", description="Search by order ID or client info", required=false, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="List of orders",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Order")),
     *              @OA\Property(property="meta", type="object"),
     *              @OA\Property(property="statistics", type="object")
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['client', 'orderLines.product', 'delivery.deliveryPerson']);
        
        // Filtrer par statut
        if ($request->has('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }
        
        // Filtrer par statut de paiement
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        
        // Filtrer par client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }
        
        // Filtrer par plage de dates
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        
        // Recherche par numéro de commande (ID)
        if ($request->has('search')) {
            $search = $request->input('search');
            // Si la recherche commence par "ORD-", extraire l'ID
            if (Str::startsWith($search, 'ORD-')) {
                $orderId = (int) Str::after($search, 'ORD-');
                $query->where('id', $orderId);
            } else {
                // Sinon, chercher dans le nom du client
                $query->whereHas('client', function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%");
                });
            }
        }
        
        // Tri
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Pagination
        $perPage = min((int) $request->input('per_page', 25), 100);
        $orders = $query->paginate($perPage);
        
        // Calculer des statistiques pour le dashboard admin
        $stats = [
            'total_orders' => Order::count(),
            'pending' => Order::where('status', 'PENDING')->count(),
            'processing' => Order::where('status', 'PROCESSING')->count(),
            'shipped' => Order::where('status', 'SHIPPED')->count(),
            'delivered' => Order::where('status', 'DELIVERED')->count(),
            'cancelled' => Order::where('status', 'CANCELLED')->count(),
            'total_revenue' => Order::whereIn('status', ['DELIVERED', 'SHIPPED'])
                                    ->sum('total_amount'),
        ];
        
        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'statistics' => $stats,
        ], 200);
    }
    
    /**
     * Affiche les détails complets d'une commande
     * 
     * GET /api/admin/orders/{order}
     *
     * @OA\Get(
     *      path="/api/admin/orders/{order}",
     *      operationId="getAdminOrder",
     *      tags={"Admin Orders"},
     *      summary="Get order details (Admin)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="order", in="path", description="Order ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Order details",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/Order")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function show(Order $order): JsonResponse
    {
        // Charger toutes les relations nécessaires
        $order->load([
            'client',
            'orderLines.product',
            'payment',
            'delivery.deliveryPerson',
        ]);
        
        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ], 200);
    }
    
    /**
     * Change le statut d'une commande
     * 
     * PATCH /api/admin/orders/{order}/status
     * 
     * @OA\Patch(
     *      path="/api/admin/orders/{order}/status",
     *      operationId="updateOrderStatus",
     *      tags={"Admin Orders"},
     *      summary="Update order status",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="order", in="path", description="Order ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"status"},
     *              @OA\Property(property="status", type="string", enum={"PENDING", "CONFIRMED", "PROCESSING", "SHIPPED", "DELIVERED", "CANCELLED"}),
     *              @OA\Property(property="note", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Status updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Statut de commande mis à jour"),
     *              @OA\Property(property="data", ref="#/components/schemas/Order")
     *          )
     *      ),
     *      @OA\Response(response=400, description="Invalid status transition")
     * )
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:PENDING,CONFIRMED,PROCESSING,SHIPPED,DELIVERED,CANCELLED',
            'note' => 'nullable|string|max:500',
        ]);
        
        $oldStatus = $order->status;
        $newStatus = $validated['status'];
        
        // Vérifier que la transition de statut est valide
        // Certaines transitions ne devraient pas être permises
        // Par exemple, on ne peut pas passer de DELIVERED à PENDING
        $validTransitions = [
            'PENDING' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['PROCESSING', 'CANCELLED'],
            'PROCESSING' => ['SHIPPED', 'CANCELLED'],
            'SHIPPED' => ['DELIVERED', 'CANCELLED'],
            'DELIVERED' => [], // Une commande livrée ne peut plus changer de statut
            'CANCELLED' => [], // Une commande annulée ne peut plus changer de statut
        ];
        
        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => "Transition de statut invalide : {$oldStatus} → {$newStatus}",
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Mettre à jour le statut
            $order->update(['status' => $newStatus]);
            
            // Si la commande est annulée, remettre les produits en stock
            if ($newStatus === 'CANCELLED') {
                foreach ($order->orderLines as $orderLine) {
                    $orderLine->product->increment('quantity', $orderLine->quantity);
                }
                
                // Marquer le paiement comme remboursé
                $order->update(['payment_status' => 'REFUNDED']);
            }
            
            // Vous pourriez aussi logger ce changement de statut dans une table
            // d'audit pour garder un historique de qui a fait quoi et quand
            // OrderStatusHistory::create([
            //     'order_id' => $order->id,
            //     'old_status' => $oldStatus,
            //     'new_status' => $newStatus,
            //     'changed_by_admin_id' => $request->user('admin-api')->id,
            //     'note' => $validated['note'] ?? null,
            // ]);
            
            DB::commit();
            
            $order->refresh();
            $order->load(['orderLines.product', 'delivery.deliveryPerson']);
            
            return response()->json([
                'success' => true,
                'message' => "Statut de commande mis à jour : {$oldStatus} → {$newStatus}",
                'data' => new OrderResource($order),
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du statut',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
    
    /**
     * Assigne une commande à un livreur
     * 
     * POST /api/admin/orders/{order}/assign-delivery
     * 
     * @OA\Post(
     *      path="/api/admin/orders/{order}/assign-delivery",
     *      operationId="assignDelivery",
     *      tags={"Admin Orders"},
     *      summary="Assign delivery driver",
     *      description="Create a delivery entry and assign a driver.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="order", in="path", description="Order ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"delivery_person_id", "delivery_address"},
     *              @OA\Property(property="delivery_person_id", type="integer", example=5),
     *              @OA\Property(property="delivery_address", type="string", example="123 Main St, City"),
     *              @OA\Property(property="scheduled_date", type="string", format="date", example="2023-12-25"),
     *              @OA\Property(property="notes", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Delivery assigned",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Livraison assignée avec succès"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="delivery_id", type="integer"),
     *                  @OA\Property(property="tracking_code", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=400, description="Delivery already assigned or driver unavailable")
     * )
     */
    public function assignDelivery(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'delivery_person_id' => 'required|exists:delivery_persons,id',
            'delivery_address' => 'required|string|max:500',
            'scheduled_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Vérifier que la commande n'a pas déjà une livraison assignée
        if ($order->delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande a déjà une livraison assignée',
            ], 400);
        }
        
        // Vérifier que le livreur est disponible
        $deliveryPerson = DeliveryPerson::findOrFail($validated['delivery_person_id']);
        if (!$deliveryPerson->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Ce livreur n\'est pas disponible actuellement',
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Générer un code de suivi unique
            // Format: TRACK-YYYYMMDD-XXXXX où XXXXX est un nombre aléatoire
            $trackingCode = 'TRACK-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            
            // Générer un token QR unique pour la confirmation de livraison
            $qrToken = Str::random(32);
            
            // Créer l'enregistrement de livraison
            $delivery = Delivery::create([
                'order_id' => $order->id,
                'delivery_person_id' => $validated['delivery_person_id'],
                'delivery_address' => $validated['delivery_address'],
                'status' => 'ASSIGNED',
                'tracking_code' => $trackingCode,
                'qr_token' => $qrToken,
                'qr_expires_at' => now()->addDays(7), // Le QR code expire dans 7 jours
                'qr_status' => 'PENDING',
            ]);
            
            // Mettre à jour le statut de la commande
            if ($order->status === 'PENDING') {
                $order->update(['status' => 'CONFIRMED']);
            }
            
            DB::commit();
            
            broadcast(new DeliveryAssigned($delivery));
            $delivery->load(['deliveryPerson', 'order.orderLines.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Livraison assignée avec succès',
                'data' => [
                    'delivery_id' => $delivery->id,
                    'tracking_code' => $delivery->tracking_code,
                    'qr_token' => $delivery->qr_token,
                    'delivery_person' => [
                        'id' => $deliveryPerson->id,
                        'name' => $deliveryPerson->name,
                        'phone' => $deliveryPerson->phone,
                    ],
                ],
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'assignation de la livraison',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
}