<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * Controller pour la gestion des catégories par les administrateurs
 * 
 * Permet de créer, modifier, supprimer et lister les catégories de produits.
 * Les catégories servent à organiser le catalogue pour les clients.
 *
 * @OA\Tag(
 *     name="Admin Categories",
 *     description="Category management for admins"
 * )
 */
class CategoryController extends Controller
{
    /**
     * Liste toutes les catégories
     * 
     * GET /api/admin/categories
     * 
     * @OA\Get(
     *      path="/api/admin/categories",
     *      operationId="getAdminCategories",
     *      tags={"Admin Categories"},
     *      summary="List categories (Admin)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="List of categories",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category"))
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $query = Category::withCount('products');

        // Filtrer par statut
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Recherche
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Inclure ou non les sous-catégories
        if ($request->get('with_children', false)) {
            $query->with('children');
        }

        $categories = $query->orderBy('name', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'last_page' => $categories->lastPage(),
            ]
        ], 200);
    }

    /**
     * DÉTAILS D'UNE CATÉGORIE
     * 
     * GET /api/admin/categories/{category}
     * 
     * @OA\Get(
     *      path="/api/admin/categories/{id}",
     *      operationId="getAdminCategory",
     *      tags={"Admin Categories"},
     *      summary="Get category details",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Category ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Category details",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/Category")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show($id)
    {
        $category = Category::with(['products', 'children', 'parent'])
            ->withCount('products')
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image_url' => $category->image_url,
                'is_active' => $category->is_active,
                'products_count' => $category->products_count,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                ] : null,
                'children' => $category->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'products_count' => $child->products->count(),
                    ];
                }),
                'created_at' => $category->created_at->format('Y-m-d H:i:s'),
            ]
        ], 200);
    }

    /**
     * CRÉER UNE CATÉGORIE
     * 
     * POST /api/admin/categories
     * 
     * @OA\Post(
     *      path="/api/admin/categories",
     *      operationId="createCategory",
     *      tags={"Admin Categories"},
     *      summary="Create category",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Category")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Category created",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Catégorie créée avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Category")
     *          )
     *      )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('image_url');
        $data['slug'] = Str::slug($request->name);

        // Upload de l'image
        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('categories', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $category = Category::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $category
        ], 201);
    }

    /**
     * MODIFIER UNE CATÉGORIE
     * 
     * PUT/PATCH /api/admin/categories/{category}
     * 
     * @OA\Put(
     *      path="/api/admin/categories/{id}",
     *      operationId="updateCategory",
     *      tags={"Admin Categories"},
     *      summary="Update category",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Category ID", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Category")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Category updated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Catégorie mise à jour avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/Category")
     *          )
     *      )
     * )
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('image_url');

        // Mettre à jour le slug si le nom change
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        // Upload de l'image
        if ($request->hasFile('image_url')) {
            // Supprimer l'ancienne image
            if ($category->image_url) {
                $oldPath = str_replace('/storage', 'public', $category->image_url);
                Storage::delete($oldPath);
            }

            $path = $request->file('image_url')->store('categories', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour',
            'data' => $category
        ], 200);
    }

    /**
     * SUPPRIMER UNE CATÉGORIE
     * 
     * DELETE /api/admin/categories/{category}
     * 
     * @OA\Delete(
     *      path="/api/admin/categories/{id}",
     *      operationId="deleteCategory",
     *      tags={"Admin Categories"},
     *      summary="Delete category",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", description="Category ID", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Category deleted",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Catégorie supprimée avec succès")
     *          )
     *      )
     * )
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }

        // Vérifier si des produits utilisent cette catégorie
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une catégorie contenant des produits'
            ], 400);
        }

        // Supprimer l'image
        if ($category->image_url) {
            $path = str_replace('/storage', 'public', $category->image_url);
            Storage::delete($path);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès'
        ], 200);
    }

    /**
     * MIGRATION DES ANCIENNES CATÉGORIES (STRING) VERS LE NOUVEAU SYSTÈME
     */
    public function migrateOldCategories()
    {
        // Récupérer toutes les catégories uniques de la colonne 'category' (string)
        $oldCategories = Product::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        $migrated = 0;

        foreach ($oldCategories as $oldCategory) {
            // Créer la nouvelle catégorie si elle n'existe pas
            $newCategory = Category::firstOrCreate(
                ['name' => $oldCategory],
                [
                    'slug' => Str::slug($oldCategory),
                    'description' => 'Catégorie migrée automatiquement',
                    'is_active' => true,
                ]
            );

            // Mettre à jour tous les produits avec cette catégorie
            Product::where('category', $oldCategory)
                ->update(['category_id' => $newCategory->id]);

            $migrated++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$migrated} catégories migrées avec succès"
        ], 200);
    }
}
