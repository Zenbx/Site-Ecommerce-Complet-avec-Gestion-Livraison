<?php
// ============================================
// 6. DELIVERY RESOURCE
// app/Http/Resources/DeliveryResource.php
// ============================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'delivery_address' => $this->delivery_address,
            'tracking_code' => $this->tracking_code,
            'delivery_person' => $this->when($this->deliveryPerson, [
                'id' => $this->deliveryPerson?->id,
                'name' => $this->deliveryPerson?->name,
                'phone' => $this->when($this->status !== 'PENDING', 'Visible lors de la livraison'),
            ]),
            'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
            'qr_token' => $this->when($request->user() instanceof \App\Models\DeliveryPerson, $this->qr_token),
            'confirmation_img_url' => $this->confirmation_img_url,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
