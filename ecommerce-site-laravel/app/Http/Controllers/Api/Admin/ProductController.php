<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
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
 */
class ProductController extends Controller
{
    /**
     * Affiche la liste de tous les produits avec pagination et filtres
     * 
     * GET /api/admin/products
     * 
     * Cette méthode supporte plusieurs paramètres de requête optionnels :
     * - page : numéro de la page pour la pagination
     * - per_page : nombre de produits par page (défaut 15, max 100)
     * - search : recherche textuelle dans le nom et la description
     * - category : filtrer par catégorie
     * - brand : filtrer par marque
     * - is_active : filtrer par statut actif/inactif
     * - in_stock : filtrer par disponibilité en stock
     * - sort_by : champ de tri (name, price, quantity, created_at)
     * - sort_order : ordre de tri (asc ou desc)
     * 
     * @param Request $request
     * @return JsonResponse
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
     * @param StoreProductRequest $request Les données validées du nouveau produit
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        // À ce stade, grâce à StoreProductRequest, nous savons avec certitude
        // que toutes les données sont valides selon les règles que nous avons définies
        
        // validated() retourne seulement les champs qui ont passé la validation
        // C'est plus sûr que all() qui retournerait tous les champs de la requête
        $validatedData = $request->validated();
        
        // Si is_active n'est pas fourni dans la requête, nous le mettons à true par défaut
        // car un nouveau produit devrait généralement être actif dans le catalogue
        $validatedData['is_active'] = $validatedData['is_active'] ?? true;
        
        // Créer le produit dans la base de données
        // create() insère un nouvel enregistrement et retourne le modèle créé
        $product = Product::create($validatedData);
        
        // Transformer le produit créé avec ProductResource et retourner une réponse 201 Created
        // Le code 201 est le code HTTP standard pour "ressource créée avec succès"
        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Affiche les détails d'un produit spécifique
     * 
     * GET /api/admin/products/{id}
     * 
     * @param Product $product Le produit à afficher (injecté automatiquement par Laravel)
     * @return JsonResponse
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
     * @param UpdateProductRequest $request Les données validées de mise à jour
     * @param Product $product Le produit à mettre à jour
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        // Récupérer les données validées
        $validatedData = $request->validated();
        
        // Mettre à jour le produit avec les nouvelles données
        // update() modifie seulement les champs présents dans $validatedData
        // Les autres champs restent inchangés
        $product->update($validatedData);
        
        // Rafraîchir le modèle pour obtenir les valeurs les plus récentes de la base
        // Cela est nécessaire si nous avons des triggers ou des valeurs par défaut en base
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
     * @param Product $product Le produit à supprimer
     * @return JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        // Avant de supprimer, nous pourrions vouloir vérifier certaines conditions
        // Par exemple, ne pas permettre la suppression si le produit est dans des commandes actives
        
        // Vérifier si le produit apparaît dans des lignes de commande
        // has('orderLines') vérifie si la relation orderLines existe et contient des éléments
        if ($product->orderLines()->exists()) {
            // Si le produit a été commandé, nous ne le supprimons pas vraiment
            // Nous le marquons simplement comme inactif pour préserver l'historique
            $product->update(['is_active' => false]);
            
            return response()->json([
                'success' => true,
                'message' => 'Le produit a été désactivé car il apparaît dans des commandes existantes',
                'data' => new ProductResource($product),
            ], 200);
        }
        
        // Si le produit n'a jamais été commandé, nous pouvons le supprimer complètement
        $product->delete();
        
        // Pour une suppression, le code HTTP 204 No Content est approprié
        // Il indique que la requête a réussi mais qu'il n'y a pas de contenu à retourner
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
     * Cette méthode démontre comment ajouter des endpoints personnalisés
     * au-delà des sept méthodes RESTful standard
     * 
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function updateStock(Request $request, Product $product): JsonResponse
    {
        // Valider la requête directement dans le controller
        // Pour une opération simple comme celle-ci, créer une Form Request séparée
        // serait peut-être excessif, donc nous validons ici
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'operation' => 'sometimes|string|in:set,add,subtract',
        ]);
        
        $operation = $validated['operation'] ?? 'set';
        $quantity = $validated['quantity'];
        
        // Effectuer l'opération demandée
        switch ($operation) {
            case 'add':
                // Ajouter à la quantité existante
                $product->quantity += $quantity;
                break;
            case 'subtract':
                // Soustraire de la quantité existante, mais ne jamais aller en dessous de zéro
                $product->quantity = max(0, $product->quantity - $quantity);
                break;
            case 'set':
            default:
                // Définir directement la quantité
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