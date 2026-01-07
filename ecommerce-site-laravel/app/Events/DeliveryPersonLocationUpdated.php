<?php

namespace App\Events;

use App\Models\DeliveryPerson;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class DeliveryPersonLocationUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public DeliveryPerson $deliveryPerson;
    public float $latitude;
    public float $longitude;
    public ?float $speed;
    public ?float $heading;
    public string $timestamp;

    public function __construct(DeliveryPerson $deliveryPerson, array $location)
    {
        $this->deliveryPerson = $deliveryPerson;
        $this->latitude = $location['latitude'];
        $this->longitude = $location['longitude'];
        $this->speed = $location['speed'] ?? null;
        $this->heading = $location['heading'] ?? null;
        $this->timestamp = now()->toIso8601String();
    }

    public function broadcastOn(): array
{
    return [
        new Channel('delivery-persons-tracking'),
    ];
}

    public function broadcastWith(): array
    {
        return [
            'delivery_person' => [
                'id' => $this->deliveryPerson->id,
                'name' => $this->deliveryPerson->name,
            ],
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'speed' => $this->speed,
                'heading' => $this->heading,
            ],
            'timestamp' => $this->timestamp,
        ];
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }
}
