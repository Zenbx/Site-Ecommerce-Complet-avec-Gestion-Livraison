<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controller pour la gestion des commandes côté client
 * 
 * Ce controller permet aux clients de créer des commandes à partir de leur panier,
 * de consulter leurs commandes passées, de suivre le statut de leurs livraisons,
 * et d'annuler des commandes si elles sont encore dans un statut qui le permet.
 *
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management for clients"
 * )
 */
class OrderController extends Controller
{
    /**
     * Récupère toutes les commandes du client connecté
     * 
     * GET /api/client/orders
     * 
     * @OA\Get(
     *      path="/api/client/orders",
     *      operationId="getClientOrders",
     *      tags={"Orders"},
     *      summary="List orders",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer", default=1)),
     *      @OA\Parameter(name="per_page", in="query", description="Items per page", required=false, @OA\Schema(type="integer", default=10)),
     *      @OA\Parameter(name="status", in="query", description="Filter by status (comma separated)", required=false, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="List of orders",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Order")),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Construire la requête de base
        $query = Order::where('client_id', $client->id);
        
        // Filtrer par statut si spécifié
        // Le client peut envoyer ?status=PENDING,PROCESSING pour voir seulement
        // les commandes en attente ou en cours de traitement
        if ($request->has('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }
        
        // Trier par date de création décroissante (les plus récentes d'abord)
        $query->orderBy('created_at', 'desc');
        
        // Paginer les résultats
        $perPage = min((int) $request->input('per_page', 10), 50);
        $orders = $query->paginate($perPage);
        
        // Pour chaque commande, charger les relations nécessaires
        // Nous chargeons orderLines.product pour afficher les détails des produits
        // Nous chargeons delivery pour montrer les informations de livraison
        $orders->load(['orderLines.product', 'delivery.deliveryPerson']);
        
        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 200);
    }
    
    /**
     * Affiche les détails d'une commande spécifique
     * 
     * GET /api/client/orders/{order}
     * 
     * @OA\Get(
     *      path="/api/client/orders/{order}",
     *      operationId="getClientOrder",
     *      tags={"Orders"},
     *      summary="Get order details",
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
    public function show(Request $request, Order $order): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Vérifier que la commande appartient bien au client connecté
        // C'est une mesure de sécurité essentielle pour empêcher un client
        // de voir les commandes d'un autre client en devinant des IDs
        if ($order->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }
        
        // Charger toutes les relations nécessaires pour afficher les détails complets
        $order->load([
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
     * Crée une nouvelle commande à partir du panier actif du client
     * 
     * POST /api/client/orders
     * 
     * @OA\Post(
     *      path="/api/client/orders",
     *      operationId="createOrder",
     *      tags={"Orders"},
     *      summary="Create order from cart",
     *      description="Converts the active cart into a new order.",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"delivery_address"},
     *              @OA\Property(property="delivery_address", type="string", example="123 Main St, City"),
     *              @OA\Property(property="delivery_fee", type="number", example=10.00),
     *              @OA\Property(property="notes", type="string", example="Please leave at door")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Order created",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Commande créée avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Order")
     *          )
     *      ),
     *      @OA\Response(response=400, description="Cart is empty or invalid stock")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Valider les données de la requête
        $validated = $request->validate([
            'delivery_address' => 'required|string|max:500',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $client = $request->user('client-api');
        
        // Récupérer le panier actif du client avec toutes ses lignes et produits
        $cart = Cart::with(['cartLines.product'])
                    ->where('client_id', $client->id)
                    ->where('status', 'ACTIVE')
                    ->first();
        
        // Vérifier que le client a un panier actif
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 400);
        }
        
        // Vérifier que le panier contient au moins un article
        if ($cart->cartLines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 400);
        }
        
        // Utiliser une transaction pour garantir l'intégrité des données
        // C'est absolument crucial pour cette opération complexe
        DB::beginTransaction();
        
        try {
            // Étape 1 : Vérifier la disponibilité en stock de tous les produits
            // Nous faisons cela avant de créer quoi que ce soit pour échouer rapidement
            // si un produit n'est plus disponible
            foreach ($cart->cartLines as $cartLine) {
                $product = $cartLine->product;
                
                // Vérifier que le produit est toujours actif
                if (!$product->is_active) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Le produit '{$product->name}' n'est plus disponible",
                    ], 400);
                }
                
                // Vérifier que la quantité demandée est disponible en stock
                if ($product->quantity < $cartLine->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour '{$product->name}'. Seulement {$product->quantity} unités disponibles",
                    ], 400);
                }
            }
            
            // Étape 2 : Calculer le montant total de la commande
            // Le montant total est la somme de tous les articles
            // Plus tard, vous pourriez ajouter des taxes, des frais de livraison, des remises, etc.
            $totalAmount = $cart->cartLines->sum(function ($line) {
                return $line->quantity * $line->unit_price;
            });
            
            // Les frais de livraison peuvent être calculés en fonction de l'adresse,
            // du poids total, de la distance, etc. Pour l'instant, nous utilisons
            // une valeur fournie par le client ou zéro par défaut
            $deliveryFee = $validated['delivery_fee'] ?? 0;
            
            // Étape 3 : Créer l'enregistrement de commande
            $order = Order::create([
                'client_id' => $client->id,
                'total_amount' => $totalAmount,
                'delivery_fee' => $deliveryFee,
                'status' => 'PENDING',
                'payment_status' => 'PENDING',
            ]);
            
            // Étape 4 : Créer les lignes de commande à partir des lignes de panier
            // Nous copions chaque ligne du panier vers une nouvelle ligne de commande
            // Nous stockons le prix au moment de la commande (unit_price) car le prix
            // du produit pourrait changer plus tard dans le catalogue
            foreach ($cart->cartLines as $cartLine) {
                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $cartLine->product_id,
                    'quantity' => $cartLine->quantity,
                    'unit_price' => $cartLine->unit_price,
                ]);
                
                // Étape 5 : Décrémenter le stock du produit
                // Nous utilisons decrement() plutôt que de récupérer, modifier et sauvegarder
                // car c'est une opération atomique au niveau de la base de données
                // Cela évite les problèmes de concurrence si deux clients commandent
                // le dernier exemplaire en même temps
                $cartLine->product->decrement('quantity', $cartLine->quantity);
            }
            
            // Étape 6 : Marquer le panier comme converti
            // Nous ne supprimons pas le panier, nous changeons juste son statut
            // Cela permet de garder un historique et éventuellement de permettre
            // au client de "recommander la même chose" en créant un nouveau panier
            // à partir d'un ancien panier converti
            $cart->update(['status' => 'CONVERTED']);
            
            // Tout s'est bien passé, valider la transaction
            DB::commit();
            
            // Charger les relations pour la réponse
            $order->load(['orderLines.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => new OrderResource($order),
            ], 201);
            
        } catch (\Exception $e) {
            // En cas d'erreur, annuler toutes les modifications
            DB::rollBack();
            
            // En production, vous devriez logger cette erreur pour investigation
            // Log::error('Order creation failed', ['error' => $e->getMessage(), 'client_id' => $client->id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de votre commande',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
    
    /**
     * Annule une commande si elle est dans un statut qui le permet
     * 
     * POST /api/client/orders/{order}/cancel
     * 
     * @OA\Post(
     *      path="/api/client/orders/{order}/cancel",
     *      operationId="cancelOrder",
     *      tags={"Orders"},
     *      summary="Cancel order",
     *      description="Cancel an order if status is PENDING or CONFIRMED.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="order", in="path", description="Order ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=false,
     *          @OA\JsonContent(
     *              @OA\Property(property="reason", type="string", example="Changed my mind")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Order cancelled",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Commande annulée avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Order")
     *          )
     *      ),
     *      @OA\Response(response=400, description="Order cannot be cancelled")
     * )
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Vérifier que la commande appartient au client
        if ($order->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }
        
        // Vérifier que la commande peut être annulée
        // Les commandes déjà annulées, livrées, ou en cours de livraison
        // ne peuvent pas être annulées par le client
        $cancellableStatuses = ['PENDING', 'CONFIRMED'];
        if (!in_array($order->status, $cancellableStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être annulée. Veuillez contacter le service client.',
            ], 400);
        }
        
        // Valider la raison d'annulation si fournie
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Changer le statut de la commande à CANCELLED
            $order->update([
                'status' => 'CANCELLED',
                'payment_status' => 'REFUNDED', // Marquer le paiement comme remboursé
            ]);
            
            // Remettre les produits en stock
            // Puisque le client n'achète plus ces produits, nous devons
            // réincrémenter le stock pour qu'ils soient à nouveau disponibles
            foreach ($order->orderLines as $orderLine) {
                $orderLine->product->increment('quantity', $orderLine->quantity);
            }
            
            // Si une raison a été fournie, vous pourriez la stocker
            // dans une table order_cancellations pour analyse future
            // OrderCancellation::create([
            //     'order_id' => $order->id,
            //     'reason' => $validated['reason'] ?? 'Non spécifiée',
            //     'cancelled_by' => 'client',
            // ]);
            
            DB::commit();
            
            $order->refresh();
            $order->load(['orderLines.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès. Le montant sera remboursé dans 3-5 jours ouvrés.',
                'data' => new OrderResource($order),
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'annulation',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
    
    /**
     * Récupère le statut de suivi d'une commande
     * 
     * GET /api/client/orders/{order}/tracking
     * 
     * @OA\Get(
     *      path="/api/client/orders/{order}/tracking",
     *      operationId="trackOrder",
     *      tags={"Orders"},
     *      summary="Track order",
     *      description="Get real-time tracking information.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="order", in="path", description="Order ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Tracking info",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="order_number", type="string"),
     *                  @OA\Property(property="status", type="string"),
     *                  @OA\Property(property="tracking_available", type="boolean"),
     *                  @OA\Property(property="delivery", ref="#/components/schemas/Delivery")
     *              )
     *          )
     *      )
     * )
     */
    public function tracking(Request $request, Order $order): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Vérifier que la commande appartient au client
        if ($order->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }
        
        // Charger la livraison avec le livreur assigné
        $order->load(['delivery.deliveryPerson']);
        
        // Si la commande n'a pas encore de livraison assignée
        if (!$order->delivery) {
            return response()->json([
                'success' => true,
                'data' => [
                    'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'status' => $order->status,
                    'tracking_available' => false,
                    'message' => 'Votre commande est en cours de préparation. Les informations de suivi seront bientôt disponibles.',
                ],
            ], 200);
        }
        
        $delivery = $order->delivery;
        
        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'status' => $order->status,
                'tracking_available' => true,
                'delivery' => [
                    'status' => $delivery->status,
                    'tracking_code' => $delivery->tracking_code,
                    'delivery_address' => $delivery->delivery_address,
                    'delivery_person' => $delivery->deliveryPerson ? [
                        'name' => $delivery->deliveryPerson->name,
                        'phone' => $delivery->deliveryPerson->phone,
                    ] : null,
                    'qr_code_url' => $delivery->qr_token ? url("/qr/{$delivery->qr_token}") : null,
                    'estimated_delivery' => $delivery->created_at->addDays(2)->format('Y-m-d'),
                ],
            ],
        ], 200);
    }
}