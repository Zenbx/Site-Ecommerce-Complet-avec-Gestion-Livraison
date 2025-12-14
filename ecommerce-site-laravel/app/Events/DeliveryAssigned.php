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
 * Événement diffusé quand une livraison est assignée à un livreur
 * 
 * Cet événement déclenche une notification en temps réel sur le téléphone
 * du livreur pour l'informer qu'une nouvelle livraison lui a été assignée.
 */
class DeliveryAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Diffuser sur le canal personnel du livreur
     * 
     * Chaque livreur a un canal privé delivery-person.{id} sur lequel
     * seul lui peut s'abonner. C'est là que nous envoyons les notifications
     * personnelles comme les nouvelles livraisons assignées.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('delivery-person.' . $this->delivery->delivery_person_id),
        ];
    }
    
    public function broadcastWith(): array
    {
        // Charger toutes les relations nécessaires pour l'affichage
        $this->delivery->load(['order.client', 'order.orderLines.product']);
        
        return [
            'delivery_id' => $this->delivery->id,
            'tracking_code' => $this->delivery->tracking_code,
            'order' => [
                'order_number' => 'ORD-' . str_pad($this->delivery->order->id, 6, '0', STR_PAD_LEFT),
                'total_amount' => number_format($this->delivery->order->total_amount, 2, '.', ''),
                'customer_name' => $this->delivery->order->client->name,
                'items_count' => $this->delivery->order->orderLines->count(),
            ],
            'delivery_address' => $this->delivery->delivery_address,
            'status' => $this->delivery->status,
            'assigned_at' => now()->toIso8601String(),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'delivery.assigned';
    }
}