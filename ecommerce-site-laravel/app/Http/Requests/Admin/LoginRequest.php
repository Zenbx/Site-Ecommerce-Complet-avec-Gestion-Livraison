<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request pour la connexion des administrateurs
 * 
 * Cette classe encapsule toutes les règles de validation pour l'authentification
 * d'un administrateur. En séparant la validation dans sa propre classe, nous
 * gardons notre AuthController propre et focalisé sur la logique métier.
 */
class LoginRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * 
     * Pour la connexion, n'importe qui peut essayer de se connecter.
     * L'authentification elle-même sera vérifiée dans le controller
     * en comparant le mot de passe avec celui en base de données.
     * 
     * Si cette méthode retourne false, Laravel rejettera automatiquement
     * la requête avec une erreur 403 Forbidden avant même d'exécuter
     * la validation ou le code du controller.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Tout le monde peut tenter de se connecter
        return true;
    }

    /**
     * Règles de validation pour la connexion admin.
     * 
     * Ces règles définissent ce qui constitue une tentative de connexion valide.
     * Notez que nous ne vérifions PAS ici si le mot de passe est correct,
     * nous vérifions seulement que les données sont dans le bon format.
     * 
     * La vérification du mot de passe se fera dans le controller car c'est
     * de la logique métier, pas de la validation de format.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // L'email est obligatoire (required)
            // Il doit être une chaîne de caractères (string)
            // Il doit être au format email valide (email)
            // Maximum 255 caractères pour éviter les attaques par déni de service
            'email' => 'required|string|email|max:255',
            
            // Le mot de passe est obligatoire
            // Il doit être une chaîne de caractères
            // Nous ne vérifions pas la longueur minimale ici car c'est une connexion,
            // pas une création de compte. Le mot de passe pourrait avoir été créé
            // avant qu'on impose une règle de longueur minimale.
            'password' => 'required|string',
        ];
    }

    /**
     * Messages d'erreur personnalisés pour cette validation.
     * 
     * Par défaut, Laravel génère des messages d'erreur génériques en anglais.
     * Cette méthode nous permet de définir des messages personnalisés en français
     * qui sont plus clairs pour nos utilisateurs.
     * 
     * La syntaxe est : 'champ.règle' => 'message personnalisé'
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ];
    }

    /**
     * Noms d'attributs personnalisés pour les messages d'erreur.
     * 
     * Cette méthode permet de remplacer le nom technique du champ
     * par un nom plus convivial dans les messages d'erreur génériques.
     * 
     * Par exemple, au lieu de "The email field is required",
     * Laravel dira "L'adresse email est obligatoire" en utilisant
     * la traduction définie ici combinée avec nos messages personnalisés.
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'adresse email',
            'password' => 'mot de passe',
        ];
    }
}