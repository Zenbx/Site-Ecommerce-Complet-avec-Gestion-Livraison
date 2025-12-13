
<?php

// ============================================
// 7. CREATE ORDER REQUEST
// app/Http/Requests/CreateOrderRequest.php
// ============================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Client;
    }

    public function rules(): array
    {
        return [
            'shipping_address' => 'required|string|max:500',
            'payment_method' => 'required|in:MOMO,OM,CASH',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_address.required' => 'L\'adresse de livraison est obligatoire',
            'payment_method.required' => 'La méthode de paiement est obligatoire',
            'payment_method.in' => 'Méthode de paiement invalide',
        ];
    }
}
