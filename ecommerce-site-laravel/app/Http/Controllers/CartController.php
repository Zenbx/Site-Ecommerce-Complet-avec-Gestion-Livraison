<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // CLIENT-CART-01: Obtenir le panier
    public function index(Request $request)
    {
        $client = $request->user();
        
        $cart = Cart::with(['cartLines.product'])
            ->where('client_id', $client->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'summary' => [
                        'subtotal' => 0,
                        'tax' => 0,
                        'shipping' => 0,
                        'discount' => 0,
                        'total' => 0,
                        'items_count' => 0
                    ]
                ]
            ]);
        }

        $items = $cart->cartLines->map(function ($line) {
            return [
                'id' => $line->id,
                'product' => [
                    'id' => $line->product->id,
                    'name' => $line->product->name,
                    'price' => (float) $line->product->price,
                    'image' => $line->product->image_url,
                    'stock' => $line->product->quantity
                ],
                'quantity' => $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'total' => (float) ($line->unit_price * $line->quantity)
            ];
        });

        $subtotal = $items->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'summary' => [
                    'subtotal' => $subtotal,
                    'tax' => 0,
                    'shipping' => 0,
                    'discount' => 0,
                    'total' => $subtotal,
                    'items_count' => $items->sum('quantity')
                ]
            ]
        ]);
    }

    // CLIENT-CART-02: Ajouter au panier
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:product,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $client = $request->user();
        $product = Product::findOrFail($request->product_id);

        // Vérifier le stock
        if ($product->quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Récupérer ou créer le panier actif
            $cart = Cart::firstOrCreate([
                'client_id' => $client->id,
                'status' => 'ACTIVE'
            ]);

            // Vérifier si le produit existe déjà dans le panier
            $cartLine = CartLine::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($cartLine) {
                // Mettre à jour la quantité
                $newQuantity = $cartLine->quantity + $request->quantity;
                
                if ($product->quantity < $newQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuffisant pour cette quantité'
                    ], 400);
                }

                $cartLine->quantity = $newQuantity;
                $cartLine->save();
            } else {
                // Créer une nouvelle ligne
                $cartLine = CartLine::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'unit_price' => $product->price
                ]);
            }

            $cart->touch();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté au panier',
                'data' => [
                    'cart_item_id' => $cartLine->id,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name
                    ],
                    'quantity' => $cartLine->quantity,
                    'total' => (float) ($cartLine->unit_price * $cartLine->quantity)
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout au panier'
            ], 500);
        }
    }

    // CLIENT-CART-03: Modifier la quantité
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $client = $request->user();
        
        $cartLine = CartLine::whereHas('cart', function ($query) use ($client) {
            $query->where('client_id', $client->id)
                  ->where('status', 'ACTIVE');
        })->findOrFail($id);

        // Vérifier le stock
        if ($cartLine->product->quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant'
            ], 400);
        }

        $cartLine->quantity = $request->quantity;
        $cartLine->save();

        $cartLine->cart->touch();

        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data' => [
                'cart_item_id' => $cartLine->id,
                'quantity' => $cartLine->quantity,
                'total' => (float) ($cartLine->unit_price * $cartLine->quantity)
            ]
        ]);
    }

    // CLIENT-CART-04: Retirer du panier
    public function destroy(Request $request, $id)
    {
        $client = $request->user();
        
        $cartLine = CartLine::whereHas('cart', function ($query) use ($client) {
            $query->where('client_id', $client->id)
                  ->where('status', 'ACTIVE');
        })->findOrFail($id);

        $cartLine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit retiré du panier'
        ]);
    }

    // CLIENT-CART-05: Vider le panier
    public function clear(Request $request)
    {
        $client = $request->user();
        
        $cart = Cart::where('client_id', $client->id)
            ->where('status', 'ACTIVE')
            ->first();

        if ($cart) {
            $cart->cartLines()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé'
        ]);
    }
}