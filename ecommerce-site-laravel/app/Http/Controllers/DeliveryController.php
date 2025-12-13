<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DeliveryPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
    // DELIVERY-01: Mes livraisons (pour le livreur)
    public function index(Request $request)
    {
        $deliveryPerson = $request->user();
        
        $deliveries = Delivery::with(['order.client', 'order.orderLines'])
            ->where('delivery_person_id', $deliveryPerson->id)
            ->when($request->status, function ($query, $status) {
                return $query->where('status', strtoupper($status));
            })
            ->when($request->date, function ($query, $date) {
                return $query->whereDate('created_at', $date);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $deliveries->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'order' => [
                    'order_number' => "ORD-{$delivery->order->id}",
                    'customer' => [
                        'name' => $delivery->order->client->name
                    ],
                    'items_count' => $delivery->order->orderLines->count(),
                    'total_amount' => (float) $delivery->order->total_amount,
                    'qr_token' => $delivery->qr_token
                ],
                'status' => $delivery->status,
                'delivery_address' => $delivery->delivery_address,
                'tracking_code' => $delivery->tracking_code,
                'created_at' => $delivery->created_at->toIso8601String()
            ];
        });

        $today = $deliveries->filter(fn($d) => $d->created_at->isToday());
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'statistics' => [
                'today' => [
                    'total' => $today->count(),
                    'assigned' => $today->where('status', 'ASSIGNED')->count(),
                    'in_transit' => $today->where('status', 'IN_TRANSIT')->count(),
                    'delivered' => $today->where('status', 'DELIVERED')->count(),
                    'failed' => $today->where('status', 'FAILED')->count()
                ]
            ]
        ]);
    }

    // DELIVERY-02: Détails d'une livraison
    public function show(Request $request, $id)
    {
        $deliveryPerson = $request->user();
        
        $delivery = Delivery::with(['order.client', 'order.orderLines.product'])
            ->where('delivery_person_id', $deliveryPerson->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $delivery->id,
                'order' => [
                    'order_number' => "ORD-{$delivery->order->id}",
                    'customer' => [
                        'name' => $delivery->order->client->name
                    ],
                    'items' => $delivery->order->orderLines->map(fn($line) => [
                        'product_name' => $line->product->name,
                        'quantity' => $line->quantity,
                        'image' => $line->product->image_url
                    ]),
                    'total_amount' => (float) $delivery->order->total_amount,
                    'payment_method' => $delivery->order->payment->payment_method ?? null,
                    'qr_token' => $delivery->qr_token
                ],
                'status' => $delivery->status,
                'delivery_address' => $delivery->delivery_address,
                'tracking_code' => $delivery->tracking_code,
                'created_at' => $delivery->created_at->toIso8601String()
            ]
        ]);
    }

    // DELIVERY-05: Marquer comme récupéré
    public function pickup(Request $request, $id)
    {
        $deliveryPerson = $request->user();
        
        $delivery = Delivery::where('delivery_person_id', $deliveryPerson->id)
            ->findOrFail($id);

        if ($delivery->status !== 'ASSIGNED') {
            return response()->json([
                'success' => false,
                'message' => 'Cette livraison ne peut pas être récupérée'
            ], 400);
        }

        $delivery->status = 'PICKED_UP';
        $delivery->save();

        return response()->json([
            'success' => true,
            'message' => 'Colis récupéré',
            'data' => [
                'delivery_id' => $delivery->id,
                'status' => $delivery->status,
                'picked_up_at' => now()->toIso8601String()
            ]
        ]);
    }

    // DELIVERY-06: Démarrer la livraison
    public function start(Request $request, $id)
    {
        $deliveryPerson = $request->user();
        
        $delivery = Delivery::where('delivery_person_id', $deliveryPerson->id)
            ->findOrFail($id);

        if ($delivery->status !== 'PICKED_UP') {
            return response()->json([
                'success' => false,
                'message' => 'Le colis doit d\'abord être récupéré'
            ], 400);
        }

        $delivery->status = 'IN_TRANSIT';
        $delivery->save();

        // Mettre à jour le statut de la commande
        $delivery->order->status = 'SHIPPED';
        $delivery->order->save();

        return response()->json([
            'success' => true,
            'message' => 'Livraison démarrée',
            'data' => [
                'delivery_id' => $delivery->id,
                'status' => $delivery->status,
                'started_at' => now()->toIso8601String()
            ]
        ]);
    }

    // DELIVERY-08: Scanner le QR Code
    public function scanQr(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'qr_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryPerson = $request->user();
        
        $delivery = Delivery::where('delivery_person_id', $deliveryPerson->id)
            ->findOrFail($id);

        // Vérifier le token QR
        if ($delivery->qr_token !== $request->qr_token) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code invalide ou ne correspond pas à cette livraison',
                'error' => 'invalid_qr_code'
            ], 400);
        }

        // Vérifier si le QR code n'a pas expiré
        if ($delivery->qr_expires_at && now()->greaterThan($delivery->qr_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code expiré',
                'error' => 'expired_qr_code'
            ], 400);
        }

        // Marquer le QR comme scanné
        $delivery->qr_scanned_at = now();
        $delivery->qr_status = 'SCANNED';
        $delivery->save();

        return response()->json([
            'success' => true,
            'message' => 'QR Code valide',
            'data' => [
                'delivery_id' => $delivery->id,
                'order_number' => "ORD-{$delivery->order->id}",
                'customer_name' => $delivery->order->client->name,
                'verified' => true
            ]
        ]);
    }

    // DELIVERY-09: Soumettre la preuve de livraison
    public function submitProof(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'proof_file' => 'required|image|max:5120',
            'recipient_name' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryPerson = $request->user();
        
        $delivery = Delivery::where('delivery_person_id', $deliveryPerson->id)
            ->findOrFail($id);

        if ($delivery->status !== 'IN_TRANSIT') {
            return response()->json([
                'success' => false,
                'message' => 'La livraison doit être en transit'
            ], 400);
        }

        // Sauvegarder l'image de preuve
        $path = $request->file('proof_file')->store('delivery_proofs', 'public');
        
        $delivery->confirmation_img_url = $path;
        $delivery->save();

        return response()->json([
            'success' => true,
            'message' => 'Preuve de livraison soumise',
            'data' => [
                'delivery_id' => $delivery->id,
                'proof' => [
                    'url' => url("storage/{$path}"),
                    'recipient_name' => $request->recipient_name,
                    'timestamp' => now()->toIso8601String()
                ]
            ]
        ]);
    }

    // DELIVERY-10: Marquer comme livrée
    public function complete(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryPerson = $request->user();
        
        $delivery = Delivery::where('delivery_person_id', $deliveryPerson->id)
            ->findOrFail($id);

        if ($delivery->status !== 'IN_TRANSIT') {
            return response()->json([
                'success' => false,
                'message' => 'La livraison doit être en transit'
            ], 400);
        }

        // Vérifier que le QR a été scanné
        if ($delivery->qr_status !== 'SCANNED') {
            return response()->json([
                'success' => false,
                'message' => 'Le QR Code doit être scanné avant de compléter la livraison'
            ], 400);
        }

        $delivery->status = 'DELIVERED';
        $delivery->delivered_at = now();
        $delivery->save();

        // Mettre à jour le statut de la commande
        $delivery->order->status = 'DELIVERED';
        $delivery->order->save();

        return response()->json([
            'success' => true,
            'message' => 'Livraison complétée avec succès',
            'data' => [
                'delivery_id' => $delivery->id,
                'order_number' => "ORD-{$delivery->order->id}",
                'status' => $delivery->status,
                'delivered_at' => $delivery->delivered_at->toIso8601String()
            ]
        ]);
    }

    // DELIVERY-11: Signaler un problème
    public function reportIssue(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'issue_type' => 'required|in:customer_unavailable,address_not_found,refused_package,damaged_package',
            'description' => 'required|string',
            'photo' => 'nullable|image|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryPerson = $request->user();
        
        $delivery = Delivery::where('delivery_person_id', $deliveryPerson->id)
            ->findOrFail($id);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('delivery_issues', 'public');
            $photoUrl = url("storage/{$path}");
        }

        $delivery->status = 'FAILED';
        $delivery->save();

        return response()->json([
            'success' => true,
            'message' => 'Problème signalé',
            'data' => [
                'delivery_id' => $delivery->id,
                'status' => $delivery->status,
                'issue' => [
                    'type' => $request->issue_type,
                    'description' => $request->description,
                    'photo' => $photoUrl,
                    'reported_at' => now()->toIso8601String()
                ]
            ]
        ]);
    }

    // DELIVERY-14: Mettre à jour la disponibilité
    public function updateAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_available' => 'required|boolean',
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryPerson = $request->user();
        $deliveryPerson->is_available = $request->is_available;
        $deliveryPerson->save();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilité mise à jour',
            'data' => [
                'is_available' => $deliveryPerson->is_available,
                'updated_at' => now()->toIso8601String()
            ]
        ]);
    }

    // ADMIN: Assigner une livraison à un livreur
    public function assignDelivery(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'delivery_person_id' => 'required|exists:delivery_person,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery = Delivery::where('order_id', $orderId)->firstOrFail();

        // Vérifier que le livreur est disponible
        $deliveryPerson = DeliveryPerson::findOrFail($request->delivery_person_id);
        if (!$deliveryPerson->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Ce livreur n\'est pas disponible'
            ], 400);
        }

        $delivery->delivery_person_id = $request->delivery_person_id;
        $delivery->status = 'ASSIGNED';
        $delivery->save();

        return response()->json([
            'success' => true,
            'message' => 'Commande assignée au livreur',
            'data' => [
                'delivery_id' => $delivery->id,
                'order_number' => "ORD-{$delivery->order_id}",
                'delivery_person' => [
                    'id' => $deliveryPerson->id,
                    'name' => $deliveryPerson->name
                ],
                'status' => $delivery->status
            ]
        ]);
    }

    // ADMIN: Liste des livraisons
    public function adminIndex(Request $request)
    {
        $deliveries = Delivery::with(['order.client', 'deliveryPerson'])
            ->when($request->status, function ($query, $status) {
                return $query->whereIn('status', explode(',', strtoupper($status)));
            })
            ->when($request->delivery_person_id, function ($query, $id) {
                return $query->where('delivery_person_id', $id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 25);

        $data = $deliveries->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'order' => [
                    'id' => $delivery->order->id,
                    'order_number' => "ORD-{$delivery->order->id}",
                    'customer' => [
                        'name' => $delivery->order->client->name
                    ],
                    'total_amount' => (float) $delivery->order->total_amount
                ],
                'delivery_person' => $delivery->deliveryPerson ? [
                    'id' => $delivery->deliveryPerson->id,
                    'name' => $delivery->deliveryPerson->name
                ] : null,
                'status' => $delivery->status,
                'delivery_address' => $delivery->delivery_address,
                'tracking_code' => $delivery->tracking_code,
                'created_at' => $delivery->created_at->toIso8601String()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'total' => $deliveries->total(),
                'per_page' => $deliveries->perPage(),
                'last_page' => $deliveries->lastPage()
            ]
        ]);
    }
}