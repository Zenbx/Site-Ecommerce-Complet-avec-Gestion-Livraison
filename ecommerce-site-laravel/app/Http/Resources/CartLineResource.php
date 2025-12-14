<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle CartLine
 */
class CartLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            'product' => $this->when(
                $this->relationLoaded('product'),
                function() {
                    return [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                        'price' => number_format($this->product->price, 2, '.', ''),
                        'image_url' => $this->product->image_url,
                        'stock' => $this->product->quantity,
                        'in_stock' => $this->product->quantity > 0,
                    ];
                }
            ),
            
            'quantity' => $this->quantity,
            'unit_price' => number_format($this->unit_price, 2, '.', ''),
            'subtotal' => number_format($this->quantity * $this->unit_price, 2, '.', ''),
            
            'added_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}