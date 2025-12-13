<?php

// ============================================
// 4. UPDATE PRODUCT REQUEST
// app/Http/Requests/UpdateProductRequest.php
// ============================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Admin;
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        
        return [
            'name' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'serial_id' => 'sometimes|string|max:100|unique:product,serial_id,' . $productId,
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
            'quantity.min' => 'La quantité ne peut pas être négative',
            'price.min' => 'Le prix ne peut pas être négatif',
            'serial_id.unique' => 'Ce numéro de série existe déjà',
            'image_url.image' => 'Le fichier doit être une image',
            'image_url.max' => 'L\'image ne doit pas dépasser 2MB',
        ];
    }
}
