<?php

// ============================================
// 8. ASSIGN DELIVERY REQUEST
// app/Http/Requests/AssignDeliveryRequest.php
// ============================================

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Admin;
    }

    public function rules(): array
    {
        return [
            'delivery_person_id' => 'required|integer|exists:delivery_person,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_person_id.required' => 'Le livreur est obligatoire',
            'delivery_person_id.exists' => 'Ce livreur n\'existe pas',
        ];
    }
}