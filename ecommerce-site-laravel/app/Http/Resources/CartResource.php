<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle Cart
 */
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            
            // Lignes du panier avec les produits
            'items' => $this->when(
                $this->relationLoaded('cartLines'),
                fn() => CartLineResource::collection($this->cartLines)
            ),
            
            // Résumé du panier
            'summary' => $this->when(
                $this->relationLoaded('cartLines'),
                function() {
                    $subtotal = $this->cartLines->sum(function($line) {
                        return $line->quantity * $line->unit_price;
                    });
                    
                    return [
                        'items_count' => $this->cartLines->sum('quantity'),
                        'subtotal' => number_format($subtotal, 2, '.', ''),
                        'total' => number_format($subtotal, 2, '.', ''),
                    ];
                }
            ),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}