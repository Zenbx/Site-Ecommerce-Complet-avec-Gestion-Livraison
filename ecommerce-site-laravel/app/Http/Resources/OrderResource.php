<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle Order
 * 
 * Cette Resource démontre comment gérer les relations imbriquées
 * en utilisant d'autres Resources pour transformer les modèles liés.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Numéro de commande généré (nous devrons créer cela dans le modèle)
            'order_number' => 'ORD-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            
            // Relation avec le client
            // Si la relation 'client' a été chargée, nous l'incluons transformée via ClientResource
            // Sinon, nous incluons seulement l'ID du client
            'client' => $this->when(
                $this->relationLoaded('client'),
                fn() => new ClientResource($this->client),
                ['id' => $this->client_id] // Fallback si la relation n'est pas chargée
            ),
            
            // Montants
            'subtotal' => number_format((float)$this->total_amount, 2, '.', ''),
            'delivery_fee' => number_format((float)$this->delivery_fee, 2, '.', ''),
            'total' => number_format((float)($this->total_amount + $this->delivery_fee), 2, '.', ''),
            
            // Statuts
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            
            // Traduction des statuts en français pour l'affichage
            'status_label' => $this->getStatusLabel(),
            'payment_status_label' => $this->getPaymentStatusLabel(),
            
            // Relation avec les lignes de commande
            // Nous utilisons OrderLineResource::collection() pour transformer
            // une collection de OrderLine en une collection de OrderLineResource
            'items' => $this->when(
                $this->relationLoaded('orderLines'),
                fn() => OrderLineResource::collection($this->orderLines)
            ),
            
            // Nombre d'articles dans la commande
            'items_count' => $this->when(
                $this->relationLoaded('orderLines'),
                fn() => $this->orderLines->count(),
                0
            ),
            
            // Relation avec le paiement
            'payment' => $this->when(
                $this->relationLoaded('payment'),
                fn() => new PaymentResource($this->payment)
            ),
            
            // Relation avec la livraison
            'delivery' => $this->when(
                $this->relationLoaded('delivery'),
                fn() => new DeliveryResource($this->delivery)
            ),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Retourne le libellé français du statut de commande
     */
    protected function getStatusLabel(): string
    {
        return match($this->status) {
            'PENDING' => 'En attente',
            'CONFIRMED' => 'Confirmée',
            'PROCESSING' => 'En préparation',
            'SHIPPED' => 'Expédiée',
            'DELIVERED' => 'Livrée',
            'CANCELLED' => 'Annulée',
            default => $this->status,
        };
    }
    
    /**
     * Retourne le libellé français du statut de paiement
     */
    protected function getPaymentStatusLabel(): string
    {
        return match($this->payment_status) {
            'PENDING' => 'En attente',
            'COMPLETED' => 'Payé',
            'FAILED' => 'Échoué',
            'REFUNDED' => 'Remboursé',
            default => $this->payment_status,
        };
    }
    
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}