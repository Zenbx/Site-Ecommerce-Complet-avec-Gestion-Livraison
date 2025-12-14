<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle Admin
 * 
 * Cette classe définit comment un modèle Admin doit être transformé
 * en JSON lorsqu'il est retourné dans une réponse API.
 * 
 * Les Resources permettent de :
 * - Contrôler exactement quels champs sont exposés
 * - Formater les données de manière cohérente
 * - Renommer les champs si nécessaire
 * - Inclure des données calculées ou dérivées
 * - Gérer les relations entre modèles
 */
class AdminResource extends JsonResource
{
    /**
     * Transforme la resource en tableau.
     * 
     * Cette méthode est appelée automatiquement par Laravel quand vous
     * retournez une instance de AdminResource dans une réponse HTTP.
     * 
     * Le paramètre $this fait référence au modèle Admin qui a été passé
     * à cette Resource. Vous pouvez accéder à toutes les propriétés et
     * méthodes du modèle via $this.
     * 
     * @param Request $request La requête HTTP actuelle
     * @return array<string, mixed> Le tableau qui sera converti en JSON
     */
    public function toArray(Request $request): array
    {
        return [
            // L'ID de l'admin
            // Nous utilisons $this->id car $this fait référence au modèle Admin
            'id' => $this->id,
            
            // Le nom de l'admin
            'name' => $this->name,
            
            // L'email de l'admin
            'email' => $this->email,
            
            // Le rôle de l'admin (GESTIONNAIRE, SUPERVISEUR, ou ADMIN)
            // Notez que nous n'incluons PAS le mot de passe ici
            // Même s'il est déjà caché par la propriété $hidden du modèle,
            // ne pas l'inclure dans la Resource est une couche de sécurité supplémentaire
            'role' => $this->role,
            
            // Les timestamps formatés de manière lisible
            // Au lieu de retourner le format brut de PostgreSQL comme
            // "2024-12-14 10:30:45", nous utilisons la méthode format()
            // fournie par Carbon (la bibliothèque de dates de Laravel)
            // pour créer un format ISO 8601 standard
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // L'opérateur ?-> est l'opérateur de nullsafe de PHP 8
            // Il signifie "si created_at n'est pas null, appelle format(),
            // sinon retourne null". C'est plus sûr que d'appeler directement
            // ->format() car si created_at était null pour une raison quelconque,
            // cela causerait une erreur.
        ];
    }
    
    /**
     * Personnaliser l'enveloppe de la resource.
     * 
     * Par défaut, Laravel enveloppe les resources dans un objet "data".
     * Cette méthode nous permet de personnaliser cette enveloppe si nécessaire.
     * 
     * Pour l'instant, nous utilisons le comportement par défaut, mais il est
     * bon de savoir que cette personnalisation est possible.
     * 
     * @param Request $request
     * @param array $data
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            // D'autres métadonnées peuvent être ajoutées ici si nécessaire
        ];
    }
}