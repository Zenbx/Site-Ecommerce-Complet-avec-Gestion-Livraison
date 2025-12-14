<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Form Request pour l'inscription d'un nouveau client
 * 
 * Cette classe définit toutes les règles qu'un nouveau client doit respecter
 * lors de son inscription. Elle inclut des règles de sécurité strictes pour
 * le mot de passe et vérifie l'unicité de l'email.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // N'importe qui peut s'inscrire comme client
        return true;
    }

    /**
     * Règles de validation pour l'inscription client.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Le nom est obligatoire, doit être une chaîne, et maximum 255 caractères
            'name' => 'required|string|max:255',
            
            // L'email doit être unique dans la table clients
            // Si un client existe déjà avec cet email, la validation échouera
            // C'est crucial pour éviter les doublons et les conflits de connexion
            'email' => 'required|string|email|max:255|unique:clients,email',
            
            // Le mot de passe utilise l'objet Password de Laravel qui fournit
            // des règles de sécurité modernes et configurables
            // min(8) : au moins 8 caractères
            // letters() : doit contenir au moins une lettre
            // numbers() : doit contenir au moins un chiffre
            // confirmed : doit correspondre au champ password_confirmation
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers()
            ],
            
            // L'adresse est obligatoire car nous en avons besoin pour les livraisons
            'address' => 'required|string|max:500',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'address.required' => 'L\'adresse de livraison est obligatoire.',
            'address.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Noms d'attributs personnalisés.
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'adresse email',
            'password' => 'mot de passe',
            'address' => 'adresse',
        ];
    }
}