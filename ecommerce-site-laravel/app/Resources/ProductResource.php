<?php

// ============================================
// 1. PRODUCT RESOURCE
// app/Http/Resources/ProductResource.php
// ============================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => \Illuminate\Support\Str::slug($this->name),
            'description' => $this->description,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'serial_id' => $this->serial_id,
            'brand' => $this->brand,
            'category' => $this->category,
            'image_url' => $this->image_url,
            'is_active' => (bool) $this->is_active,
            'in_stock' => $this->quantity > 0,
            'added_at' => $this->added_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}



