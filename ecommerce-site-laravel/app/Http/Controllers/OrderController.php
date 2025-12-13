<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // CLIENT-ORDER-01: Créer une commande
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_address' => 'required|string',
            'payment_method' => 'required|in:MOMO,OM,CASH',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $client = $request->user();

        // Récupérer le panier actif
        $cart = Cart::with('cartLines.product')
            ->where('client_id', $client->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cart || $cart->cartLines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Le panier est vide'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Vérifier le stock pour tous les produits
            foreach ($cart->cartLines as $line) {
                if ($line->product->quantity < $line->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$line->product->name}"
                    ], 400);
                }
            }

            // Calculer le total
            $totalAmount = $cart->cartLines->sum(function ($line) {
                return $line->unit_price * $line->quantity;
            });

            // Créer la commande
            $order = Order::create([
                'client_id' => $client->id,
                'total_amount' => $totalAmount,
                'delivery_fee' => 0,
                'status' => 'PENDING',
                'payment_status' => 'PENDING'
            ]);

            // Créer les lignes de commande et déduire le stock
            foreach ($cart->cartLines as $line) {
                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $line->product_id,
                    'unit_price' => $line->unit_price,
                    'quantity' => $line->quantity
                ]);

                // Déduire le stock
                $line->product->decrement('quantity', $line->quantity);
            }

            // Créer le paiement
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'amount' => $totalAmount,
                'transaction_reference' => 'TXN-' . strtoupper(Str::random(10))
            ]);

            // Créer la livraison
            $tracking_code = 'TRK-' . strtoupper(Str::random(8));
            $qr_token = Str::random(32);
            
            Delivery::create([
                'order_id' => $order->id,
                'delivery_address' => $request->delivery_address,
                'tracking_code' => $tracking_code,
                'status' => 'PENDING',
                'qr_token' => $qr_token,
                'qr_expires_at' => now()->addDays(7),
                'qr_status' => 'ACTIVE'
            ]);

            // Marquer le panier comme converti
            $cart->status = 'CONVERTED';
            $cart->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => "ORD-{$order->id}",
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total_amount' => (float) $order->total_amount,
                    'tracking_code' => $tracking_code,
                    'qr_token' => $qr_token,
                    'created_at' => $order->created_at->toIso8601String()
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // CLIENT-ORDER-02: Mes commandes
    public function index(Request $request)
    {
        $client = $request->user();
        
        $orders = Order::with(['orderLines.product', 'delivery'])
            ->where('client_id', $client->id)
            ->when($request->status, function ($query, $status) {
                return $query->where('status', strtoupper($status));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        $data = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => "ORD-{$order->id}",
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => (float) $order->total_amount,
                'items_count' => $order->orderLines->count(),
                'tracking_code' => $order->delivery?->tracking_code,
                'created_at' => $order->created_at->toIso8601String()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage()
            ]
        ]);
    }

    // CLIENT-ORDER-03: Détails d'une commande
    public function show(Request $request, $id)
    {
        $client = $request->user();
        
        $order = Order::with(['orderLines.product', 'delivery.deliveryPerson', 'payment'])
            ->where('client_id', $client->id)
            ->findOrFail($id);

        $items = $order->orderLines->map(function ($line) {
            return [
                'id' => $line->id,
                'product' => [
                    'id' => $line->product->id,
                    'name' => $line->product->name,
                    'image' => $line->product->image_url
                ],
                'quantity' => $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'total' => (float) ($line->unit_price * $line->quantity)
            ];
        });

        $delivery = $order->delivery;
        $tracking = null;

        if ($delivery) {
            $tracking = [
                'delivery_person' => $delivery->deliveryPerson ? [
                    'name' => $delivery->deliveryPerson->name,
                    'phone' => 'Hidden for privacy'
                ] : null,
                'current_status' => $delivery->status,
                'tracking_code' => $delivery->tracking_code,
                'timeline' => [
                    [
                        'status' => 'PENDING',
                        'timestamp' => $order->created_at->toIso8601String(),
                        'description' => 'Commande reçue'
                    ]
                ]
            ];

            if ($delivery->status !== 'PENDING') {
                $tracking['timeline'][] = [
                    'status' => $delivery->status,
                    'timestamp' => $delivery->updated_at->toIso8601String(),
                    'description' => $this->getStatusDescription($delivery->status)
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => "ORD-{$order->id}",
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment?->payment_method,
                'items' => $items,
                'subtotal' => (float) $order->total_amount,
                'tax' => 0,
                'shipping_cost' => (float) $order->delivery_fee,
                'discount' => 0,
                'total_amount' => (float) ($order->total_amount + $order->delivery_fee),
                'delivery_address' => $delivery?->delivery_address,
                'tracking' => $tracking,
                'created_at' => $order->created_at->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String()
            ]
        ]);
    }

    // CLIENT-ORDER-05: Annuler une commande
    public function cancel(Request $request, $id)
    {
        $client = $request->user();
        
        $order = Order::where('client_id', $client->id)->findOrFail($id);

        if (!in_array($order->status, ['PENDING', 'CONFIRMED'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être annulée'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Remettre le stock
            foreach ($order->orderLines as $line) {
                $line->product->increment('quantity', $line->quantity);
            }

            $order->status = 'CANCELLED';
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => "ORD-{$order->id}",
                    'status' => $order->status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }

    // ADMIN: Liste des commandes
    public function adminIndex(Request $request)
    {
        $orders = Order::with(['client', 'orderLines', 'delivery.deliveryPerson'])
            ->when($request->status, function ($query, $status) {
                return $query->whereIn('status', explode(',', strtoupper($status)));
            })
            ->when($request->date_from, function ($query, $date) {
                return $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                return $query->whereDate('created_at', '<=', $date);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 25);

        $data = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => "ORD-{$order->id}",
                'customer' => [
                    'id' => $order->client->id,
                    'name' => $order->client->name,
                    'email' => $order->client->email
                ],
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => (float) $order->total_amount,
                'items_count' => $order->orderLines->count(),
                'delivery_person' => $order->delivery?->deliveryPerson ? [
                    'id' => $order->delivery->deliveryPerson->id,
                    'name' => $order->delivery->deliveryPerson->name
                ] : null,
                'created_at' => $order->created_at->toIso8601String()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage()
            ]
        ]);
    }

    // ADMIN: Changer le statut
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:PENDING,CONFIRMED,PROCESSING,SHIPPED,DELIVERED,CANCELLED',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
                'updated_at' => $order->updated_at->toIso8601String()
            ]
        ]);
    }

    private function getStatusDescription($status)
    {
        return match($status) {
            'ASSIGNED' => 'Assignée au livreur',
            'PICKED_UP' => 'Colis récupéré',
            'IN_TRANSIT' => 'En route vers vous',
            'DELIVERED' => 'Livraison effectuée',
            'FAILED' => 'Échec de livraison',
            default => 'En attente'
        };
    }
}