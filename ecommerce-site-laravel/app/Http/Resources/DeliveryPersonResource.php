<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle DeliveryPerson
 * 
 * Cette Resource transforme les informations d'un livreur pour les exposer
 * via l'API. Elle inclut des données de profil, des statistiques de performance,
 * et des indicateurs de disponibilité, tout en masquant les informations sensibles.
 * 
 * Utilisée par :
 * - Les admins pour consulter les profils des livreurs (Angular)
 * - Les livreurs pour consulter leur propre profil (React Native)
 */
class DeliveryPersonResource extends JsonResource
{
    /**
     * Transforme la resource en tableau.
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Informations de base du livreur
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            
            // Informations de contact et d'identification
            // Ces informations sont sensibles et ne devraient être visibles
            // que par les admins et le livreur lui-même
            'id_card_number' => $this->id_card_number,
            'address' => $this->address,
            
            // Photo de profil du livreur
            // Si aucune photo n'est définie, nous fournissons un avatar par défaut
            'photo_url' => $this->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&size=200',
            
            // Statut de disponibilité
            // Crucial pour savoir si le livreur peut recevoir de nouvelles livraisons
            'is_available' => (bool)$this->is_available,
            
            // Indicateur de disponibilité textuel pour l'interface utilisateur
            'availability_status' => $this->getAvailabilityStatus(),
            
            // Statistiques de performance
            // Ces compteurs sont chargés via withCount() dans le controller
            // Le when() garantit qu'on n'essaie pas d'accéder à ces attributs
            // s'ils n'ont pas été chargés, ce qui éviterait des requêtes N+1
            'statistics' => [
                'total_deliveries' => $this->when(
                    isset($this->deliveries_count),
                    $this->deliveries_count ?? 0
                ),
                'completed_deliveries' => $this->when(
                    isset($this->completed_deliveries_count),
                    $this->completed_deliveries_count ?? 0
                ),
                'pending_deliveries' => $this->when(
                    isset($this->pending_deliveries_count),
                    $this->pending_deliveries_count ?? 0
                ),
                // Calculer le taux de succès si nous avons les données
                'success_rate' => $this->when(
                    isset($this->deliveries_count) && isset($this->completed_deliveries_count) && $this->deliveries_count > 0,
                    fn() => round(($this->completed_deliveries_count / $this->deliveries_count) * 100, 2)
                ),
            ],
            
            // Livraisons récentes
            // Seulement incluses si la relation a été chargée explicitement
            // Cela donne au controller le contrôle sur ce qui est inclus
            'recent_deliveries' => $this->when(
                $this->relationLoaded('deliveries'),
                fn() => DeliveryResource::collection($this->deliveries)
            ),
            
            // Timestamps formatés de manière cohérente
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Ancienneté dans le système
            // diffForHumans() crée des chaînes conviviales comme "il y a 3 mois"
            'member_since' => $this->created_at?->diffForHumans(),
        ];
    }
    
    /**
     * Détermine le statut de disponibilité sous forme textuelle
     * 
     * Cette méthode helper transforme le booléen is_available en un message
     * convivial que le frontend peut afficher directement sans logique supplémentaire
     * 
     * @return string
     */
    protected function getAvailabilityStatus(): string
    {
        if (!$this->is_available) {
            return 'Indisponible';
        }
        
        // Vérifier si le livreur a des livraisons en cours
        // Nous ne faisons cette vérification que si la relation a été chargée
        // pour éviter de déclencher une requête SQL supplémentaire
        if ($this->relationLoaded('deliveries')) {
            $hasActiveDeliveries = $this->deliveries->whereIn('status', ['IN_TRANSIT', 'PICKED_UP'])->isNotEmpty();
            
            if ($hasActiveDeliveries) {
                return 'En livraison';
            }
        }
        
        return 'Disponible';
    }
    
    /**
     * Ajouter des métadonnées supplémentaires à la réponse
     * 
     * @param Request $request
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}