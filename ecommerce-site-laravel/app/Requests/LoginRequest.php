<?php

// ============================================
// 2. LOGIN REQUEST
// app/Http/Requests/LoginRequest.php
// ============================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'user_type' => 'required|in:client,admin,delivery_person',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'password.required' => 'Le mot de passe est obligatoire',
            'user_type.required' => 'Le type d\'utilisateur est obligatoire',
            'user_type.in' => 'Type d\'utilisateur invalide',
        ];
    }
}

