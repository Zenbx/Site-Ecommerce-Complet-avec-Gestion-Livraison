<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Controller du catalogue produits côté client
 * 
 * Ce controller fournit les endpoints publics pour consulter le catalogue.
 * Les clients peuvent lister les produits, rechercher, filtrer par catégorie,
 * et voir les détails des produits.
 * 
 * Performance :
 * Ce controller utilise intensivement le cache car le catalogue est consulté
 * très fréquemment mais change relativement peu. Mettre en cache les listes
 * de produits améliore considérablement les performances.
 */
class ProductController extends Controller
{
    /**
     * Liste tous les produits disponibles avec filtres et recherche
     * 
     * GET /api/client/products
     * 
     * Query params :
     * - page : numéro de page (pagination)
     * - per_page : nombre d'éléments par page
     * - search : terme de recherche dans nom et description
     * - category_id : filtrer par catégorie
     * - min_price : prix minimum
     * - max_price : prix maximum
     * - sort_by : price, name, popularity, newest
     * - sort_order : asc, desc
     * 
     * Cette méthode montre comment gérer des filtres et une recherche
     * complexes tout en gardant le code lisible et performant.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Construction de la clé de cache basée sur les paramètres
        // Ceci permet de cacher différentes combinaisons de filtres
        $cacheKey = 'products_' . md5(json_encode([
            'page' => $request->get('page', 1),
            'per_page' => $perPage,
            'search' => $search,
            'category_id' => $categoryId,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]));
        
        // Cacher pendant 10 minutes
        // Les produits ne changent pas très fréquemment, donc on peut
        // se permettre un cache relativement long
        $result = Cache::remember($cacheKey, now()->addMinutes(10), function() use (
            $perPage, $search, $categoryId, $minPrice, $maxPrice, $sortBy, $sortOrder
        ) {
            $query = Product::query()
                ->where('is_active', true)
                ->where('quantity', '>', 0); // Seulement les produits en stock
            
            // Filtrage par recherche
            if ($search) {
                // Utilisation du helper sanitize_search_query pour sécuriser
                $cleanSearch = sanitize_search_query($search);
                
                $query->where(function($q) use ($cleanSearch) {
                    $q->where('name', 'LIKE', "%{$cleanSearch}%")
                      ->orWhere('description', 'LIKE', "%{$cleanSearch}%")
                      ->orWhere('brand', 'LIKE', "%{$cleanSearch}%");
                });
            }
            
            // Filtrage par catégorie
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
            
            // Filtrage par fourchette de prix
            if ($minPrice) {
                $query->where('price', '>=', $minPrice);
            }
            
            if ($maxPrice) {
                $query->where('price', '<=', $maxPrice);
            }
            
            // Tri
            $allowedSorts = ['price', 'name', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }
            
            return $query->paginate($perPage);
        });
        
        // Transformation des données avec les helpers
        $result->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => format_currency($product->price),
                'price_raw' => (float) $product->price,
                'quantity' => $product->quantity,
                'brand' => $product->brand,
                'category' => $product->category,
                'image_url' => $product->image_url,
                'is_active' => $product->is_active,
                'in_stock' => $product->quantity > 0,
                'stock_status' => $this->getStockStatus($product->quantity),
            ];
        });
        
        return generate_api_response(true, [
            'products' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'last_page' => $result->lastPage(),
            ],
        ]);
    }
    
    /**
     * Affiche les détails d'un produit spécifique
     * 
     * GET /api/client/products/{id}
     * 
     * Cette méthode retourne toutes les informations détaillées d'un produit,
     * incluant ses spécifications, ses avis clients, et des produits similaires.
     */
    public function show(Product $product): JsonResponse
    {
        // Vérifier que le produit est actif
        if (!$product->is_active) {
            return generate_api_response(
                false,
                null,
                'Ce produit n\'est plus disponible',
                404
            );
        }
        
        $cacheKey = 'product_details_' . $product->id;
        
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function() use ($product) {
            // Charger les relations
            $product->load(['category']);
            
            // Obtenir des produits similaires
            $similarProducts = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->limit(4)
                ->get()
                ->map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => format_currency($p->price),
                        'price_raw' => (float) $p->price,
                        'image_url' => $p->image_url,
                    ];
                });
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => format_currency($product->price),
                'price_raw' => (float) $product->price,
                'quantity' => $product->quantity,
                'serial_id' => $product->serial_id,
                'brand' => $product->brand,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'image_url' => $product->image_url,
                'in_stock' => $product->quantity > 0,
                'stock_status' => $this->getStockStatus($product->quantity),
                'added_at' => $product->added_at->toIso8601String(),
                'added_ago' => time_ago($product->added_at),
                'similar_products' => $similarProducts,
            ];
        });
        
        return generate_api_response(true, $data);
    }
    
    /**
     * Recherche rapide de produits (autocomplete)
     * 
     * GET /api/client/products/search
     * 
     * Cette méthode est optimisée pour l'autocomplete dans l'interface.
     * Elle retourne rapidement des résultats partiels pour afficher
     * des suggestions pendant que l'utilisateur tape.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');
        
        if (!$query || strlen($query) < 2) {
            return generate_api_response(
                false,
                null,
                'La recherche doit contenir au moins 2 caractères',
                400
            );
        }
        
        // Utilisation du helper sanitize_search_query
        $cleanQuery = sanitize_search_query($query);
        
        $cacheKey = 'search_' . md5($cleanQuery);
        
        $results = Cache::remember($cacheKey, now()->addMinutes(5), function() use ($cleanQuery) {
            return Product::where('is_active', true)
                ->where('quantity', '>', 0)
                ->where(function($q) use ($cleanQuery) {
                    $q->where('name', 'LIKE', "%{$cleanQuery}%")
                      ->orWhere('brand', 'LIKE', "%{$cleanQuery}%");
                })
                ->limit(10)
                ->get(['id', 'name', 'price', 'image_url', 'brand'])
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'brand' => $product->brand,
                        'price' => format_currency($product->price),
                        'image_url' => $product->image_url,
                    ];
                });
        });
        
        return generate_api_response(true, $results);
    }
    
    /**
     * Liste toutes les catégories disponibles
     * 
     * GET /api/client/categories
     * 
     * Cette méthode retourne la liste des catégories avec le nombre
     * de produits disponibles dans chacune.
     */
    public function categories(): JsonResponse
    {
        $cacheKey = 'categories_with_counts';
        
        $categories = Cache::remember($cacheKey, now()->addHours(1), function() {
            return Category::withCount(['products' => function($query) {
                $query->where('is_active', true)
                      ->where('quantity', '>', 0);
            }])
            ->get()
            ->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products_count' => $category->products_count,
                ];
            });
        });
        
        return generate_api_response(true, $categories);
    }
    
    /**
     * Obtient les produits les plus populaires
     * 
     * GET /api/client/products/popular
     * 
     * Cette méthode retourne les produits les plus vendus récemment.
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $cacheKey = 'popular_products_' . $limit;
        
        $products = Cache::remember($cacheKey, now()->addHours(2), function() use ($limit) {
            // Pour déterminer les produits populaires, on compte le nombre
            // de fois qu'ils apparaissent dans les commandes récentes
            return Product::select('products.*')
                ->join('order_lines', 'products.id', '=', 'order_lines.product_id')
                ->join('orders', 'order_lines.order_id', '=', 'orders.id')
                ->where('products.is_active', true)
                ->where('products.quantity', '>', 0)
                ->where('orders.created_at', '>=', now()->subDays(30))
                ->groupBy('products.id')
                ->orderByRaw('COUNT(order_lines.id) DESC')
                ->limit($limit)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => format_currency($product->price),
                        'price_raw' => (float) $product->price,
                        'image_url' => $product->image_url,
                        'brand' => $product->brand,
                    ];
                });
        });
        
        return generate_api_response(true, $products);
    }
    
    /**
     * Détermine le statut de stock d'un produit
     * 
     * Cette méthode helper privée retourne un message convivial
     * sur le statut de stock du produit.
     */
    private function getStockStatus(int $quantity): string
    {
        if ($quantity === 0) {
            return 'Rupture de stock';
        } elseif ($quantity <= 5) {
            return 'Stock limité (' . $quantity . ' restants)';
        } elseif ($quantity <= 10) {
            return 'Quelques unités disponibles';
        } else {
            return 'En stock';
        }
    }
}