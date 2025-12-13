<?php

// ============================================
// 4. ORDER RESOURCE
// app/Http/Resources/OrderResource.php
// ============================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $orderNumber = 'ORD-' . date('Y', strtotime($this->created_at)) . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);

        $items = $this->orderLines->map(function ($line) {
            return [
                'id' => $line->id,
                'product' => [
                    'id' => $line->product->id,
                    'name' => $line->product->name,
                    'image_url' => $line->product->image_url,
                ],
                'quantity' => $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'total' => (float) ($line->quantity * $line->unit_price),
            ];
        });

        return [
            'id' => $this->id,
            'order_number' => $orderNumber,
            'customer' => $this->when($request->user() instanceof \App\Models\Admin, [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
            ]),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment?->payment_method,
            'items' => $items,
            'subtotal' => (float) $this->total_amount,
            'tax' => 0,
            'shipping_cost' => (float) $this->delivery_fee,
            'discount' => 0,
            'total_amount' => (float) ($this->total_amount + $this->delivery_fee),
            'delivery' => new DeliveryResource($this->whenLoaded('delivery')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}



