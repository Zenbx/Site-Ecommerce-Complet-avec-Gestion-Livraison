<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle Client
 * 
 * Cette Resource démontre comment inclure des données calculées
 * et des relations dans vos réponses API.
 */
class ClientResource extends JsonResource
{
    /**
     * Transforme la resource en tableau.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'address' => $this->address,
            
            // Données calculées qui n'existent pas en tant que colonnes
            // mais qui sont dérivées d'autres données
            
            // Nombre total de commandes passées par ce client
            // $this->orders est la relation que nous avons définie dans le modèle Client
            // count() compte simplement le nombre d'éléments dans cette relation
            // Le when() est une méthode conditionnelle fournie par les Resources
            // Elle n'inclut ce champ que si la relation orders a été chargée
            'orders_count' => $this->when(
                $this->relationLoaded('orders'),
                fn() => $this->orders->count(),
                0 // Valeur par défaut si la relation n'est pas chargée
            ),
            
            // Montant total dépensé par ce client
            // Nous utilisons l'accesseur getTotalSpentAttribute que nous avons
            // défini dans le modèle Client (si vous vous souvenez de l'étape des modèles)
            'total_spent' => $this->when(
                $this->relationLoaded('orders'),
                fn() => $this->orders()
                    ->where('status', 'DELIVERED')
                    ->sum('total_amount'),
                0
            ),
            
            // Indique si le client a un panier actif
            'has_active_cart' => $this->when(
                $this->relationLoaded('activeCart'),
                fn() => $this->activeCart !== null,
                false
            ),
            
            // Timestamps formatés
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Informations sur l'ancienneté du compte
            // diffForHumans() est une méthode de Carbon qui retourne une chaîne
            // comme "il y a 3 jours" ou "il y a 2 mois"
            'member_since' => $this->created_at?->diffForHumans(),
        ];
    }
    
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}