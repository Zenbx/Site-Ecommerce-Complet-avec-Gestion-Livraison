<?php

// ============================================
// 3. STORE PRODUCT REQUEST
// app/Http/Requests/StoreProductRequest.php
// ============================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Admin;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'serial_id' => 'required|string|max:100|unique:product,serial_id',
            'description' => 'nullable|string|max:1000',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire',
            'quantity.required' => 'La quantité est obligatoire',
            'quantity.min' => 'La quantité ne peut pas être négative',
            'price.required' => 'Le prix est obligatoire',
            'price.min' => 'Le prix ne peut pas être négatif',
            'serial_id.required' => 'Le numéro de série est obligatoire',
            'serial_id.unique' => 'Ce numéro de série existe déjà',
            'image_url.image' => 'Le fichier doit être une image',
            'image_url.mimes' => 'L\'image doit être au format jpeg, png, jpg ou webp',
            'image_url.max' => 'L\'image ne doit pas dépasser 2MB',
        ];
    }
}
