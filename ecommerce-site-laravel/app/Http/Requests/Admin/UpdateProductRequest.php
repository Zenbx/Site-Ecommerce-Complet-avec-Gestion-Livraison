<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request pour la mise à jour d'un produit existant
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la mise à jour d'un produit.
     * 
     * Pour la mise à jour, tous les champs sont optionnels (parfois on veut
     * mettre à jour seulement le prix, parfois seulement la quantité, etc.)
     * mais s'ils sont fournis, ils doivent respecter les mêmes règles
     * que lors de la création.
     */
    public function rules(): array
    {
        // Récupérer l'ID du produit depuis l'URL
        // Si l'URL est /api/admin/products/5, alors $productId sera 5
        $productId = $this->route('product');

        return [
            'name' => 'sometimes|required|string|max:255',
            'quantity' => 'sometimes|required|integer|min:0',
            'price' => 'sometimes|required|numeric|min:0',
            
            // Pour serial_id, nous devons exclure le produit actuel de la vérification d'unicité
            // Rule::unique crée une règle d'unicité plus sophistiquée
            // ignore($productId) dit "ignore le produit avec cet ID dans la vérification"
            // Cela permet au produit de garder son propre serial_id lors de la mise à jour
            'serial_id' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'serial_id')->ignore($productId),
            ],
            
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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