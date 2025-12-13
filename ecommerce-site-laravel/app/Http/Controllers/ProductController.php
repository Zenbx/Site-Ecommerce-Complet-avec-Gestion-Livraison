<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Liste des produits (catalogue public)",
     *     tags={"Produits"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filtrer par catégorie",
     *         required=false,
     *         @OA\Schema(type="string", example="Électronique")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par nom",
     *         required=false,
     *         @OA\Schema(type="string", example="MacBook")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des produits",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="MacBook Pro 14"),
     *                     @OA\Property(property="price", type="number", format="float", example=2500000),
     *                     @OA\Property(property="quantity", type="integer", example=15),
     *                     @OA\Property(property="category", type="string", example="Électronique"),
     *                     @OA\Property(property="brand", type="string", example="Apple"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }

        if ($request->brand) {
            $query->where('brand', $request->brand);
        }

        $query->where('is_active', true);

        $products = $query->orderBy('added_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage()
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Détails d'un produit",
     *     tags={"Produits"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du produit",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du produit",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="MacBook Pro 14"),
     *                 @OA\Property(property="description", type="string", example="Apple M2 Pro, 16GB RAM"),
     *                 @OA\Property(property="price", type="number", format="float", example=2500000),
     *                 @OA\Property(property="quantity", type="integer", example=15),
     *                 @OA\Property(property="serial_id", type="string", example="MBP-14-001"),
     *                 @OA\Property(property="category", type="string", example="Électronique"),
     *                 @OA\Property(property="brand", type="string", example="Apple")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Produit non trouvé")
     * )
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/products",
     *     summary="Créer un nouveau produit (Admin)",
     *     tags={"Admin - Produits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","quantity","price","serial_id"},
     *             @OA\Property(property="name", type="string", example="Nouveau Produit"),
     *             @OA\Property(property="quantity", type="integer", example=10),
     *             @OA\Property(property="price", type="number", format="float", example=50000),
     *             @OA\Property(property="serial_id", type="string", example="PROD-001"),
     *             @OA\Property(property="description", type="string", example="Description du produit"),
     *             @OA\Property(property="brand", type="string", example="Brand X"),
     *             @OA\Property(property="category", type="string", example="Électronique"),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Produit créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Produit créé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'serial_id' => 'required|string|unique:product',
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => $product
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/admin/products/{id}",
     *     summary="Modifier un produit (Admin)",
     *     tags={"Admin - Produits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Produit Modifié"),
     *             @OA\Property(property="price", type="number", format="float", example=55000),
     *             @OA\Property(property="quantity", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produit mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Produit mis à jour"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Produit non trouvé")
     * )
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'serial_id' => 'sometimes|string|unique:product,serial_id,' . $id,
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour',
            'data' => $product
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/admin/products/{id}",
     *     summary="Supprimer un produit (Admin)",
     *     tags={"Admin - Produits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produit supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Produit supprimé")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Produit non trouvé")
     * )
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé'
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/admin/products/{id}/stock",
     *     summary="Mettre à jour le stock (Admin)",
     *     tags={"Admin - Produits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock mis à jour"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="old_stock", type="integer", example=15),
     *                 @OA\Property(property="new_stock", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Produit non trouvé")
     * )
     */
    public function updateStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::findOrFail($id);
        $oldStock = $product->quantity;
        $product->quantity = $request->quantity;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock mis à jour',
            'data' => [
                'product_id' => $product->id,
                'old_stock' => $oldStock,
                'new_stock' => $product->quantity
            ]
        ]);
    }
}