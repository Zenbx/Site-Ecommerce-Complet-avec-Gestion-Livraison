<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Delivery $delivery;
    public string $reason;

    public function __construct(Delivery $delivery, string $reason)
    {
        $this->delivery = $delivery;
        $this->reason = $reason;
    }
}

