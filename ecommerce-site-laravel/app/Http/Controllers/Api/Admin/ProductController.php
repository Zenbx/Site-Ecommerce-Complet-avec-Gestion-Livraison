<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Services\SupabaseStorageService;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller pour la gestion des produits par les administrateurs
 * 
 * Ce controller implémente toutes les opérations CRUD (Create, Read, Update, Delete)
 * pour les produits. Il est protégé par le middleware auth:admin-api qui garantit
 * que seuls les administrateurs authentifiés peuvent accéder à ces endpoints.
 *
 * @OA\Tag(
 *     name="Admin Products",
 *     description="Product management for admins"
 * )
 */
class ProductController extends Controller
{
    
    protected $storageService;

    public function __construct(SupabaseStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Affiche la liste de tous les produits avec pagination et filtres
     * 
     * GET /api/admin/products
     * 
     * @OA\Get(
     *      path="/api/admin/products",
     *      operationId="getAdminProducts",
     *      tags={"Admin Products"},
     *      summary="List products (Admin)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer", default=1)),
     *      @OA\Parameter(name="per_page", in="query", description="Items per page", required=false, @OA\Schema(type="integer", default=15)),
     *      @OA\Parameter(name="search", in="query", description="Search term", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="category", in="query", description="Category ID", required=false, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="brand", in="query", description="Brand name", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="is_active", in="query", description="Filter by active status", required=false, @OA\Schema(type="boolean")),
     *      @OA\Parameter(name="in_stock", in="query", description="Filter by stock availability", required=false, @OA\Schema(type="boolean")),
     *      @OA\Response(
     *          response=200,
     *          description="List of products",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Commencer une requête sur le modèle Product
        // Nous n'exécutons pas encore la requête, nous la construisons progressivement
        $query = Product::query();
        
        // Si un paramètre 'search' est présent dans l'URL, nous filtrons
        // les produits dont le nom ou la description contient le terme de recherche
        // where() avec une fonction de callback nous permet de grouper les conditions OR
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                // ILIKE est l'équivalent PostgreSQL de LIKE mais insensible à la casse
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
            });
        }
        
        // Filtrer par catégorie si spécifiée
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        
        // Filtrer par marque si spécifiée
        if ($request->has('brand')) {
            $query->where('brand', $request->input('brand'));
        }
        
        // Filtrer par statut actif/inactif
        // Le paramètre peut être 'true', 'false', '1', '0'
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }
        
        // Filtrer pour afficher seulement les produits en stock
        if ($request->has('in_stock') && filter_var($request->input('in_stock'), FILTER_VALIDATE_BOOLEAN)) {
            $query->where('quantity', '>', 0);
        }
        
        // Tri des résultats
        // Par défaut, nous trions par date de création décroissante (les plus récents d'abord)
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Pour des raisons de sécurité, nous validons que le champ de tri est autorisé
        // Cela empêche un utilisateur malveillant d'essayer de trier par des colonnes sensibles
        $allowedSortFields = ['name', 'price', 'quantity', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }
        
        // Déterminer le nombre de résultats par page
        // Nous permettons entre 1 et 100 éléments par page, avec 15 par défaut
        $perPage = min((int) $request->input('per_page', 15), 100);
        
        // Exécuter la requête avec pagination
        // paginate() fait deux requêtes SQL : une pour compter le total et une pour récupérer la page
        $products = $query->paginate($perPage);
        
        // Transformer la collection paginée en utilisant notre ProductCollection
        // Laravel détecte automatiquement que c'est une collection paginée et
        // ajoute les métadonnées de pagination (current_page, last_page, total, etc.)
        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Crée un nouveau produit dans le catalogue
     * 
     * POST /api/admin/products
     * 
     * @OA\Post(
     *      path="/api/admin/products",
     *      operationId="createProduct",
     *      tags={"Admin Products"},
     *      summary="Create product",
     *      description="Add a new product to the catalog.",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Product")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Product created",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Produit créé avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Product")
     *          )
     *      )
     * )
     */
    public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'quantity' => 'required|integer|min:0',
        'brand' => 'nullable|string|max:100',
        'category_id' => 'required|exists:categories,id',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    // Gestion de l’upload
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store(
            'products',
            'public'
        );

        $validated['image_url'] = asset('storage/' . $path);
    }

    $validated['serial_id'] = strtoupper(Str::random(12));
    $validated['is_active'] = true;

    $product = Product::create($validated);

    return generate_api_response(true, [
        'id' => $product->id,
        'image_url' => $product->image_url,
    ], 'Produit créé avec succès', 201);
}

    /**
     * Affiche les détails d'un produit spécifique
     * 
     * GET /api/admin/products/{id}
     * 
     * @OA\Get(
     *      path="/api/admin/products/{id}",
     *      operationId="getAdminProduct",
     *      tags={"Admin Products"},
     *      summary="Get product details (Admin)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Product ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Product details",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/Product")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(Product $product): JsonResponse
    {
        // Laravel a automatiquement récupéré le produit grâce au route model binding
        // Si l'ID dans l'URL n'existe pas, Laravel retourne automatiquement une erreur 404
        // Nous n'avons donc pas besoin de vérifier si $product existe
        
        // Si nous voulions charger des relations, nous le ferions ici :
        // $product->load(['cartLines', 'orderLines']);
        // Cela chargerait les relations de manière efficace en une seule requête supplémentaire
        
        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ], 200);
    }

    /**
     * Met à jour un produit existant
     * 
     * PUT/PATCH /api/admin/products/{id}
     * 
     * @OA\Put(
     *      path="/api/admin/products/{id}",
     *      operationId="updateProduct",
     *      tags={"Admin Products"},
     *      summary="Update product",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Product ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Product")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Product updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Produit mis à jour avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Product")
     *          )
     *      )
     * )
     */
     public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validatedData = $request->validated();
        
        // Gérer le remplacement de l'image
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($product->image_url) {
                $oldPath = $this->storageService->extractPathFromUrl($product->image_url);
                if ($oldPath) {
                    $this->storageService->delete($oldPath);
                }
            }
            
            // Upload la nouvelle image
            $uploadResult = $this->storageService->upload($request->file('image'));
            
            if (!$uploadResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'upload de l\'image',
                    'error' => $uploadResult['error']
                ], 500);
            }
            
            $validatedData['image_url'] = $uploadResult['url'];
        }
        
        // Mettre à jour le produit
        $product->update($validatedData);
        $product->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès',
            'data' => new ProductResource($product),
        ], 200);
    }
    /**
     * Supprime un produit du catalogue
     * 
     * DELETE /api/admin/products/{id}
     * 
     * @OA\Delete(
     *      path="/api/admin/products/{id}",
     *      operationId="deleteProduct",
     *      tags={"Admin Products"},
     *      summary="Delete product",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Product ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Product deleted or deactivated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Produit supprimé avec succès")
     *          )
     *      )
     * )
     */
    public function destroy(Product $product): JsonResponse
    {
        // Vérifier si le produit apparaît dans des commandes
        if ($product->orderLines()->exists()) {
            $product->update(['is_active' => false]);
            
            return response()->json([
                'success' => true,
                'message' => 'Le produit a été désactivé car il apparaît dans des commandes existantes',
                'data' => new ProductResource($product),
            ], 200);
        }
        
        // Supprimer l'image de Supabase avant de supprimer le produit
        if ($product->image_url) {
            $imagePath = $this->storageService->extractPathFromUrl($product->image_url);
            if ($imagePath) {
                $this->storageService->delete($imagePath);
            }
        }
        
        $product->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès',
        ], 200);
    }
    
    /**
     * Méthode personnalisée pour mettre à jour seulement le stock d'un produit
     * 
     * PATCH /api/admin/products/{id}/stock
     * 
     * @OA\Patch(
     *      path="/api/admin/products/{id}/stock",
     *      operationId="updateProductStock",
     *      tags={"Admin Products"},
     *      summary="Update product stock",
     *      description="Update quantity of a product.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Product ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"quantity"},
     *              @OA\Property(property="quantity", type="integer", example=50),
     *              @OA\Property(property="operation", type="string", enum={"set", "add", "subtract"}, default="set")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Stock updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Stock mis à jour avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Product")
     *          )
     *      )
     * )
     */
       public function updateStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'operation' => 'sometimes|string|in:set,add,subtract',
        ]);
        
        $operation = $validated['operation'] ?? 'set';
        $quantity = $validated['quantity'];
        
        switch ($operation) {
            case 'add':
                $product->quantity += $quantity;
                break;
            case 'subtract':
                $product->quantity = max(0, $product->quantity - $quantity);
                break;
            case 'set':
            default:
                $product->quantity = $quantity;
                break;
        }
        
        $product->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Stock mis à jour avec succès',
            'data' => new ProductResource($product),
        ], 200);
    }
}
