<?php

// ============================================
// 4. CATEGORY CONTROLLER
// app/Http/Controllers/CategoryController.php
// ============================================

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * LISTE DES CATÉGORIES
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
