<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'serial_id' => 'required|string|max:100|unique:products,serial_id',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            
            // NOUVELLE RÈGLE : Accepter un fichier image
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            
            // Garder image_url pour compatibilité (si on veut fournir une URL externe)
            'image_url' => 'nullable|url|max:500',
            
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.integer' => 'La quantité doit être un nombre entier.',
            'quantity.min' => 'La quantité ne peut pas être négative.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',
            'serial_id.required' => 'Le numéro de série est obligatoire.',
            'serial_id.unique' => 'Ce numéro de série existe déjà.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format: jpeg, png, jpg, gif ou webp.',
            'image.max' => 'L\'image ne doit pas dépasser 5 Mo.',
            'image_url.url' => 'L\'URL de l\'image n\'est pas valide.',
        ];
    }
}