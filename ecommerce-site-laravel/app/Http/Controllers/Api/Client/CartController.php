<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controller pour la gestion du panier d'achat des clients
 * 
 * Dans un système e-commerce, le panier est une structure temporaire où
 * les clients accumulent les produits qu'ils souhaitent acheter avant
 * de finaliser leur commande. Ce controller gère toutes les opérations
 * sur le panier : ajouter des produits, modifier les quantités, retirer
 * des produits, et vider complètement le panier.
 */
class CartController extends Controller
{
    /**
     * Récupère le panier actif du client connecté
     * 
     * GET /api/client/cart
     * 
     * Un client ne peut avoir qu'un seul panier ACTIVE à la fois.
     * Si le client n'a pas encore de panier actif, cette méthode
     * en crée un automatiquement.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        // Récupérer le client connecté via le guard client-api
        $client = $request->user('client-api');
        
        // Chercher le panier actif du client, ou en créer un nouveau s'il n'existe pas
        // firstOrCreate() cherche d'abord un enregistrement correspondant aux conditions
        // S'il n'en trouve pas, il en crée un nouveau avec ces données
        $cart = Cart::firstOrCreate(
            [
                'client_id' => $client->id,
                'status' => 'ACTIVE',
            ],
            [
                'client_id' => $client->id,
                'status' => 'ACTIVE',
            ]
        );
        
        // Charger les lignes du panier avec les produits associés
        // Cette technique d'eager loading évite le problème N+1
        // Au lieu de faire une requête pour chaque ligne du panier,
        // nous chargeons toutes les lignes et tous les produits en deux requêtes seulement
        $cart->load(['cartLines.product']);
        
        // Calculer le total du panier
        // Nous parcourons toutes les lignes et multiplions la quantité par le prix unitaire
        $total = $cart->cartLines->sum(function ($line) {
            return $line->quantity * $line->unit_price;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'cart' => new CartResource($cart),
                'items' => $cart->cartLines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'product' => [
                            'id' => $line->product->id,
                            'name' => $line->product->name,
                            'image_url' => $line->product->image_url,
                            'current_price' => $line->product->price,
                        ],
                        'quantity' => $line->quantity,
                        'unit_price' => number_format($line->unit_price, 2, '.', ''),
                        'subtotal' => number_format($line->quantity * $line->unit_price, 2, '.', ''),
                    ];
                }),
                'summary' => [
                    'items_count' => $cart->cartLines->sum('quantity'),
                    'subtotal' => number_format($total, 2, '.', ''),
                    'total' => number_format($total, 2, '.', ''),
                ],
            ],
        ], 200);
    }
    
    /**
     * Ajoute un produit au panier ou augmente sa quantité s'il existe déjà
     * 
     * POST /api/client/cart/items
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function addItem(Request $request): JsonResponse
    {
        // Valider les données de la requête
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $client = $request->user('client-api');
        
        // Vérifier que le produit existe et est actif
        $product = Product::findOrFail($validated['product_id']);
        
        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'est plus disponible',
            ], 400);
        }
        
        // Vérifier que la quantité demandée est disponible en stock
        if ($product->quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuffisant. Seulement {$product->quantity} unités disponibles",
            ], 400);
        }
        
        // Obtenir ou créer le panier actif du client
        $cart = Cart::firstOrCreate([
            'client_id' => $client->id,
            'status' => 'ACTIVE',
        ]);
        
        // Utiliser une transaction pour garantir l'intégrité des données
        // Si une erreur survient pendant l'opération, tout sera annulé automatiquement
        DB::beginTransaction();
        
        try {
            // Chercher si ce produit est déjà dans le panier
            $cartLine = CartLine::where('cart_id', $cart->id)
                                ->where('product_id', $product->id)
                                ->first();
            
            if ($cartLine) {
                // Si le produit existe déjà, augmenter la quantité
                $newQuantity = $cartLine->quantity + $validated['quantity'];
                
                // Vérifier à nouveau le stock avec la nouvelle quantité
                if ($product->quantity < $newQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Vous avez déjà {$cartLine->quantity} unités dans votre panier. Stock insuffisant pour en ajouter {$validated['quantity']} de plus",
                    ], 400);
                }
                
                $cartLine->quantity = $newQuantity;
                $cartLine->save();
                
                $message = 'Quantité mise à jour dans le panier';
            } else {
                // Si le produit n'existe pas dans le panier, créer une nouvelle ligne
                $cartLine = CartLine::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $validated['quantity'],
                    'unit_price' => $product->price,
                ]);
                
                $message = 'Produit ajouté au panier';
            }
            
            DB::commit();
            
            // Recharger le panier avec toutes ses relations
            $cart->load(['cartLines.product']);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => new CartResource($cart),
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'ajout au panier',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Met à jour la quantité d'un article dans le panier
     * 
     * PATCH /api/client/cart/items/{cartLine}
     * 
     * @param Request $request
     * @param CartLine $cartLine
     * @return JsonResponse
     */
    public function updateItem(Request $request, CartLine $cartLine): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Vérifier que cette ligne de panier appartient bien au panier actif du client
        if ($cartLine->cart->client_id !== $client->id || $cartLine->cart->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans votre panier',
            ], 404);
        }
        
        // Valider la nouvelle quantité
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        
        // Vérifier le stock disponible
        if ($cartLine->product->quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuffisant. Seulement {$cartLine->product->quantity} unités disponibles",
            ], 400);
        }
        
        // Mettre à jour la quantité
        $cartLine->quantity = $validated['quantity'];
        $cartLine->save();
        
        // Recharger le panier
        $cart = $cartLine->cart->load(['cartLines.product']);
        
        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data' => new CartResource($cart),
        ], 200);
    }
    
    /**
     * Retire un article du panier
     * 
     * DELETE /api/client/cart/items/{cartLine}
     * 
     * @param Request $request
     * @param CartLine $cartLine
     * @return JsonResponse
     */
    public function removeItem(Request $request, CartLine $cartLine): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Vérifier que cette ligne appartient au client connecté
        if ($cartLine->cart->client_id !== $client->id || $cartLine->cart->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans votre panier',
            ], 404);
        }
        
        $cart = $cartLine->cart;
        
        // Supprimer la ligne du panier
        $cartLine->delete();
        
        // Recharger le panier
        $cart->load(['cartLines.product']);
        
        return response()->json([
            'success' => true,
            'message' => 'Article retiré du panier',
            'data' => new CartResource($cart),
        ], 200);
    }
    
    /**
     * Vide complètement le panier du client
     * 
     * DELETE /api/client/cart
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        $client = $request->user('client-api');
        
        // Trouver le panier actif
        $cart = Cart::where('client_id', $client->id)
                    ->where('status', 'ACTIVE')
                    ->first();
        
        if (!$cart) {
            return response()->json([
                'success' => true,
                'message' => 'Votre panier est déjà vide',
            ], 200);
        }
        
        // Supprimer toutes les lignes du panier
        // Grâce à la contrainte onDelete('cascade') dans notre migration,
        // toutes les lignes seront automatiquement supprimées
        $cart->cartLines()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès',
        ], 200);
    }
}