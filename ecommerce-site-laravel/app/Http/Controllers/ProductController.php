<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Liste des produits (Admin)
    public function index(Request $request)
    {
        $query = Product::query();

        // Filtres
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('in_stock') && $request->in_stock) {
            $query->where('quantity', '>', 0);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
            ]
        ], 200);
    }

    // Détails d'un produit
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ], 200);
    }

    // Créer un produit (Admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'serial_id' => 'required|string|unique:product',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('image_url');

        // Upload de l'image
        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => $product
        ], 201);
    }

    // Modifier un produit (Admin)
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'serial_id' => 'sometimes|string|unique:product,serial_id,' . $id,
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('image_url');

        // Upload de l'image
        if ($request->hasFile('image_url')) {
            // Supprimer l'ancienne image
            if ($product->image_url) {
                $oldPath = str_replace('/storage', 'public', $product->image_url);
                Storage::delete($oldPath);
            }

            $path = $request->file('image_url')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour',
            'data' => $product
        ], 200);
    }

    // Supprimer un produit (Admin)
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        // Supprimer l'image
        if ($product->image_url) {
            $path = str_replace('/storage', 'public', $product->image_url);
            Storage::delete($path);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès'
        ], 200);
    }

    // Mise à jour du stock
    public function updateStock(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStock = $product->quantity;

        switch ($request->operation) {
            case 'set':
                $product->quantity = $request->stock;
                break;
            case 'add':
                $product->quantity += $request->stock;
                break;
            case 'subtract':
                $product->quantity = max(0, $product->quantity - $request->stock);
                break;
        }

        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock mis à jour',
            'data' => [
                'product_id' => $product->id,
                'old_stock' => $oldStock,
                'new_stock' => $product->quantity
            ]
        ], 200);
    }
}