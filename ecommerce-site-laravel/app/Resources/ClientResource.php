<?php

// ============================================
// 7. CLIENT RESOURCE
// app/Http/Resources/ClientResource.php
// ============================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'address' => $this->address,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'statistics' => $this->when($request->user() instanceof \App\Models\Admin, [
                'total_orders' => $this->orders()->count(),
                'total_spent' => (float) $this->orders()->where('status', 'DELIVERED')->sum('total_amount'),
            ]),
        ];
    }
}