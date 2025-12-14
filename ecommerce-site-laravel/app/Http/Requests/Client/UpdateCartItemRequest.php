<?php

// ============================================
// 6. UPDATE CART ITEM REQUEST
// app/Http/Requests/UpdateCartItemRequest.php
// ============================================

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Client;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'La quantité est obligatoire',
            'quantity.min' => 'La quantité minimale est 1',
            'quantity.max' => 'La quantité maximale est 100',
        ];
    }
}