<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement diffusé quand le statut d'une livraison change
 * 
 * Diffusé vers le client qui attend sa livraison pour lui montrer
 * la progression en temps réel (assigned → picked_up → in_transit → delivered)
 */
class DeliveryStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $delivery;
    public $oldStatus;
    public $newStatus;

    public function __construct(Delivery $delivery, string $oldStatus, string $newStatus)
    {
        $this->delivery = $delivery;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Diffuser sur trois canaux :
     * 1. Canal de la livraison pour les admins qui suivent cette livraison
     * 2. Canal global des livraisons pour le tableau de bord
     * 3. Canal du client pour qu'il voie la progression de sa commande
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('delivery.' . $this->delivery->id),
            new PrivateChannel('deliveries'),
            new PrivateChannel('client.' . $this->delivery->order->client_id),
        ];
    }
    
    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'order_id' => $this->delivery->order_id,
            'tracking_code' => $this->delivery->tracking_code,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->getStatusLabel(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
    
    protected function getStatusLabel(): string
    {
        return match($this->newStatus) {
            'PENDING' => 'En attente',
            'ASSIGNED' => 'Assignée au livreur',
            'PICKED_UP' => 'Colis récupéré',
            'IN_TRANSIT' => 'En cours de livraison',
            'DELIVERED' => 'Livrée',
            'FAILED' => 'Échec de livraison',
            'CANCELLED' => 'Annulation de livraison',
            default => $this->newStatus,
        };
    }
    
    public function broadcastAs(): string
    {
        return 'status.changed';
    }
}