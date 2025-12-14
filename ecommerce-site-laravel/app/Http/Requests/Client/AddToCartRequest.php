<?php

// ============================================
// 5. ADD TO CART REQUEST
// app/Http/Requests/AddToCartRequest.php
// ============================================

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Client;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:product,id',
            'quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Le produit est obligatoire',
            'product_id.exists' => 'Ce produit n\'existe pas',
            'quantity.required' => 'La quantité est obligatoire',
            'quantity.min' => 'La quantité minimale est 1',
            'quantity.max' => 'La quantité maximale est 100',
        ];
    }
}