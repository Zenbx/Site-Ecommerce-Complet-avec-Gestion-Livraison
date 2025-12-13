<?php

// ============================================
// 8. DELIVERY PERSON RESOURCE
// app/Http/Resources/DeliveryPersonResource.php
// ============================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'id_card_number' => $this->id_card_number,
            'address' => $this->address,
            'photo_url' => $this->photo_url,
            'is_available' => (bool) $this->is_available,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'statistics' => $this->when($request->user() instanceof \App\Models\Admin, [
                'total_deliveries' => $this->deliveries()->count(),
                'completed_deliveries' => $this->deliveries()->where('status', 'DELIVERED')->count(),
                'failed_deliveries' => $this->deliveries()->where('status', 'FAILED')->count(),
            ]),
        ];
    }
}