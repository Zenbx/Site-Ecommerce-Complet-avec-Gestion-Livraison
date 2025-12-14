<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle OrderLine
 * 
 * Représente une ligne de commande avec le produit et la quantité commandée
 */
class OrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Informations du produit
            'product' => $this->when(
                $this->relationLoaded('product'),
                function() {
                    return [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                        'image_url' => $this->product->image_url,
                    ];
                },
                ['id' => $this->product_id]
            ),
            
            // Quantité commandée
            'quantity' => $this->quantity,
            
            // Prix unitaire au moment de la commande
            // Important : nous stockons le prix pour préserver l'historique
            // même si le prix du produit change dans le catalogue
            'unit_price' => number_format($this->unit_price, 2, '.', ''),
            
            // Total de cette ligne
            'subtotal' => number_format($this->quantity * $this->unit_price, 2, '.', ''),
        ];
    }
}