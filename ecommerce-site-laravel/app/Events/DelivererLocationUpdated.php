<?php

namespace App\Events;

use App\Models\DeliveryPerson;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryPersonLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deliveryPerson;
    public $location;

    /**
     * Create a new event instance.
     */
    public function __construct(DeliveryPerson $deliveryPerson)
    {
        $this->deliveryPerson = $deliveryPerson;
        $this->location = [
            'id' => $deliveryPerson->id,
            'name' => $deliveryPerson->name,
            'latitude' => (float) $deliveryPerson->current_latitude,
            'longitude' => (float) $deliveryPerson->current_longitude,
            'address' => $deliveryPerson->current_address,
            'is_online' => $deliveryPerson->is_online,
            'is_available' => $deliveryPerson->is_available,
            'updated_at' => $deliveryPerson->last_location_update?->toISOString(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('delivery-persons-location');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'delivery_person' => $this->location
        ];
    }
}