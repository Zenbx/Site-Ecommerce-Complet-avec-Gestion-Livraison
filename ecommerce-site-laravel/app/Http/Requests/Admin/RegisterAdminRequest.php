<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\Validation\ValidatesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * FormRequest pour la création d'un nouvel admin par un admin existant
 * 
 * Ce FormRequest valide toutes les données nécessaires pour créer un compte
 * administrateur. Il s'assure que les données sont dans le bon format et
 * respectent toutes les règles de sécurité que nous avons définies.
 */
class RegisterAdminRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé à faire cette requête
     * 
     * Cette méthode est appelée automatiquement par Laravel avant même de
     * vérifier la validation des données. Elle nous permet d'ajouter une
     * couche de sécurité supplémentaire en vérifiant que l'admin connecté
     * a bien le rôle ADMIN et pas seulement GESTIONNAIRE ou SUPERVISEUR.
     * 
     * Notez que nous vérifions spécifiquement le guard 'admin-api' pour
     * être sûrs de récupérer l'admin connecté via Sanctum, pas un autre
     * type d'utilisateur qui pourrait être connecté via un autre guard.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Récupérer l'admin actuellement authentifié via le guard admin-api
        // La méthode user() avec le paramètre 'admin-api' nous donne l'admin
        // connecté qui a envoyé cette requête avec son Bearer token
        $admin = $this->user('admin-api');
        
        // Vérifier que cet admin existe ET qu'il a le rôle ADMIN
        // Le double ampersand signifie que les deux conditions doivent être vraies
        // Si $admin est null (pas connecté) ou si son rôle n'est pas ADMIN,
        // cette méthode retournera false et Laravel rejettera la requête
        // avec une erreur 403 Forbidden avant même de valider les données
        return $admin && $admin->role === 'ADMIN';
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête
     * 
     * Ces règles définissent exactement quelles données sont acceptables
     * pour créer un nouvel admin. Laravel vérifiera automatiquement toutes
     * ces règles et retournera une erreur 422 avec les détails des champs
     * qui ne passent pas la validation si quelque chose ne va pas.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Le nom de l'admin est requis, doit être une chaîne de caractères,
            // et doit avoir une longueur maximale de 255 caractères pour correspondre
            // à la limite définie dans notre schéma de base de données PostgreSQL
            'name' => ['required', 'string', 'max:255'],
            
            // L'email est requis, doit être au format email valide,
            // doit être unique dans la table admins (deux admins ne peuvent pas
            // avoir le même email), et a une longueur maximale de 255 caractères
            // La règle unique:admins,email vérifie dans la table admins que
            // aucun enregistrement existant n'a déjà cet email
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            
            // Le mot de passe utilise l'objet Password de Laravel qui permet
            // de définir des règles de complexité modernes et sécurisées
            // Nous exigeons au minimum 8 caractères, ce qui est le standard
            // de sécurité généralement accepté pour les mots de passe
            // La règle 'confirmed' signifie qu'il doit y avoir un champ
            // password_confirmation dans la requête avec la même valeur
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            
            // Le rôle est requis et doit être l'une des trois valeurs définies
            // dans notre énumération PostgreSQL admin_role
            // Cela empêche quelqu'un d'essayer de créer un admin avec un rôle
            // inventé comme 'SUPER_ADMIN' ou 'GOD' qui n'existe pas dans notre système
            'role' => ['required', 'string', 'in:ADMIN,GESTIONNAIRE,SUPERVISEUR'],
        ];
    }

    /**
     * Obtenir les messages d'erreur personnalisés pour les règles de validation
     * 
     * Ces messages remplaceront les messages d'erreur par défaut de Laravel
     * avec des messages en français plus clairs et plus professionnels.
     * Cela améliore grandement l'expérience utilisateur de votre API.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'administrateur est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre administrateur.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
            
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au minimum 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            
            'role.required' => 'Le rôle de l\'administrateur est obligatoire.',
            'role.in' => 'Le rôle doit être ADMIN, GESTIONNAIRE ou SUPERVISEUR.',
        ];
    }
}