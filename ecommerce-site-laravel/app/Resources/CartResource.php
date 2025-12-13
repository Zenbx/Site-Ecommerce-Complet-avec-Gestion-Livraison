<?php

// ============================================
// 3. CART RESOURCE
// app/Http/Resources/CartResource.php
// ============================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->cartLines->map(function ($line) {
            return [
                'id' => $line->id,
                'product' => [
                    'id' => $line->product->id,
                    'name' => $line->product->name,
                    'price' => (float) $line->product->price,
                    'image_url' => $line->product->image_url,
                    'stock' => $line->product->quantity,
                ],
                'quantity' => $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'total' => (float) ($line->quantity * $line->unit_price),
            ];
        });

        $subtotal = $items->sum('total');

        return [
            'items' => $items,
            'summary' => [
                'subtotal' => $subtotal,
                'tax' => 0,
                'shipping' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'items_count' => $items->sum('quantity'),
            ],
        ];
    }
}
