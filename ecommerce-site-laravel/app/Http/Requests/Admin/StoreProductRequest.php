<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request pour la création d'un nouveau produit
 * 
 * Cette classe définit toutes les règles qu'un produit doit respecter
 * lors de sa création par un administrateur.
 */
class StoreProductRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * 
     * Seuls les administrateurs authentifiés peuvent créer des produits.
     * Cette vérification est déjà faite par le middleware auth:admin-api
     * sur la route, donc nous retournons simplement true ici.
     * 
     * Si nous voulions restreindre davantage, par exemple permettre
     * seulement aux ADMIN et SUPERVISEUR de créer des produits,
     * nous pourrions vérifier le rôle ici :
     * return in_array($this->user('admin-api')->role, ['ADMIN', 'SUPERVISEUR']);
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la création d'un produit.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            
            // La quantité doit être un nombre entier positif ou zéro
            'quantity' => 'required|integer|min:0',
            
            // Le prix doit être un nombre décimal positif
            // numeric accepte à la fois les entiers et les décimaux
            'price' => 'required|numeric|min:0',
            
            // Le serial_id doit être unique dans la table products
            'serial_id' => 'required|string|max:100|unique:products,serial_id',
            
            // La description est optionnelle (nullable)
            'description' => 'nullable|string',
            
            // La marque est optionnelle
            'brand' => 'nullable|string|max:255',
            
            // La catégorie est requise et doit exister dans la table categories
            'category_id' => 'required|integer|exists:categories,id',
            
            // L'URL de l'image est optionnelle mais si fournie, doit être une URL valide
            'image_url' => 'nullable|url|max:500',
            
            // is_active est optionnel et par défaut à true
            // Si fourni, doit être un booléen (true, false, 1, 0, "1", "0")
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
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
            'image_url.url' => 'L\'URL de l\'image n\'est pas valide.',
        ];
    }
}