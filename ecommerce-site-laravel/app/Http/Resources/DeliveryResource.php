<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle Delivery
 * 
 * Cette Resource transforme les informations d'une livraison pour les exposer
 * via l'API. Elle est utilisée par trois audiences différentes :
 * - Les admins pour superviser toutes les livraisons (Angular)
 * - Les clients pour suivre leurs livraisons (Angular/Mobile)
 * - Les livreurs pour gérer leurs livraisons assignées (React Native)
 * 
 * La Resource adapte son contenu en fonction de qui fait la requête et de
 * quelles relations ont été chargées, pour optimiser les performances.
 */
class DeliveryResource extends JsonResource
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
            // Identifiant unique de la livraison
            'id' => $this->id,
            
            // Code de suivi unique pour cette livraison
            // Le client peut utiliser ce code pour suivre sa livraison
            // Format typique : TRACK-YYYYMMDD-XXXXX
            'tracking_code' => $this->tracking_code,
            
            // Statut actuel de la livraison
            'status' => $this->status,
            
            // Libellé en français du statut pour l'affichage
            'status_label' => $this->getStatusLabel(),
            
            // Informations sur la commande associée
            // Nous adaptons le niveau de détail en fonction de ce qui a été chargé
            'order' => $this->when(
                $this->relationLoaded('order'),
                function() {
                    // Si la relation order a été chargée, nous retournons ses détails
                    return [
                        'id' => $this->order->id,
                        'order_number' => 'ORD-' . str_pad($this->order->id, 6, '0', STR_PAD_LEFT),
                        'total_amount' => number_format($this->order->total_amount, 2, '.', ''),
                        'status' => $this->order->status,
                        
                        // Informations du client seulement si chargées
                        'customer' => $this->when(
                            $this->order->relationLoaded('client'),
                            fn() => [
                                'id' => $this->order->client->id,
                                'name' => $this->order->client->name,
                                'phone' => $this->order->client->email, // Vous devriez avoir un champ phone
                                'email' => $this->order->client->email,
                            ]
                        ),
                        
                        // Articles de la commande seulement si chargés
                        'items' => $this->when(
                            $this->order->relationLoaded('orderLines'),
                            fn() => $this->order->orderLines->map(function($line) {
                                return [
                                    'product_name' => $line->product->name ?? 'Produit inconnu',
                                    'quantity' => $line->quantity,
                                    'unit_price' => number_format($line->unit_price, 2, '.', ''),
                                    'image_url' => $line->product->image_url ?? null,
                                ];
                            })
                        ),
                        
                        // Nombre d'articles si les orderLines ne sont pas chargées
                        'items_count' => $this->when(
                            !$this->order->relationLoaded('orderLines'),
                            fn() => $this->order->orderLines()->count()
                        ),
                    ];
                },
                // Si la relation order n'est pas chargée, retourner seulement l'ID
                ['id' => $this->order_id]
            ),
            
            // Informations sur le livreur assigné
            'delivery_person' => $this->when(
                $this->relationLoaded('deliveryPerson') && $this->deliveryPerson,
                function() {
                    return [
                        'id' => $this->deliveryPerson->id,
                        'name' => $this->deliveryPerson->name,
                        'phone' => $this->deliveryPerson->email, // Vous devriez avoir un champ phone
                        'photo_url' => $this->deliveryPerson->photo_url,
                        'is_available' => $this->deliveryPerson->is_available,
                    ];
                }
            ),
            
            // Adresse de livraison
            // C'est une information critique que le livreur doit voir
            'delivery_address' => $this->delivery_address,
            
            // Informations sur le QR code de confirmation
            // Le QR code est généré pour chaque livraison et permet au livreur
            // de confirmer qu'il livre bien le bon colis au bon client
            'qr_code' => [
                'token' => $this->qr_token,
                'status' => $this->qr_status,
                'expires_at' => $this->qr_expires_at?->format('Y-m-d H:i:s'),
                'scanned_at' => $this->qr_scanned_at?->format('Y-m-d H:i:s'),
                // URL du QR code si vous générez des images QR
                'url' => $this->qr_token ? url("/api/qr/{$this->qr_token}") : null,
            ],
            
            // Preuve de livraison
            // Photo de confirmation ou signature du destinataire
            'proof_of_delivery' => [
                'image_url' => $this->confirmation_img_url,
                'submitted_at' => $this->confirmation_img_url ? $this->updated_at->format('Y-m-d H:i:s') : null,
            ],
            
            // Timestamps importants pour le suivi
            'timeline' => [
                // Date de création de la livraison (assignation initiale)
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                
                // Date de livraison effective
                'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
                
                // Dernière mise à jour
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
                
                // Temps écoulé depuis la création
                'elapsed_time' => $this->created_at?->diffForHumans(),
                
                // Durée totale de livraison (si complétée)
                'total_duration' => $this->delivered_at 
                    ? $this->created_at->diffForHumans($this->delivered_at, true)
                    : null,
            ],
            
            // Estimation de livraison
            // Basée sur des calculs ou des données historiques
            'estimated_delivery' => $this->when(
                !$this->delivered_at,
                function() {
                    // Vous pourriez calculer une estimation basée sur :
                    // - La distance entre le livreur et le client
                    // - L'historique des temps de livraison du livreur
                    // - Le trafic actuel (si intégration API de cartographie)
                    // Pour l'instant, estimation simple : 2h après création
                    return $this->created_at->addHours(2)->format('Y-m-d H:i:s');
                }
            ),
        ];
    }
    
    /**
     * Retourne le libellé français du statut de livraison
     * 
     * Cette méthode transforme les codes de statut techniques en messages
     * conviviaux pour l'interface utilisateur
     * 
     * @return string
     */
    protected function getStatusLabel(): string
    {
        return match($this->status) {
            'PENDING' => 'En attente d\'assignation',
            'ASSIGNED' => 'Assignée au livreur',
            'PICKED_UP' => 'Colis récupéré',
            'IN_TRANSIT' => 'En cours de livraison',
            'DELIVERED' => 'Livrée avec succès',
            'FAILED' => 'Échec de livraison',
            default => $this->status,
        };
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