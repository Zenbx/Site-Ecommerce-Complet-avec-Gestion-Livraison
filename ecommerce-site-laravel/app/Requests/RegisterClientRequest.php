<?php

// ============================================
// PARTIE 1 : REQUESTS (VALIDATION)
// ============================================

// ============================================
// 1. REGISTER CLIENT REQUEST
// app/Http/Requests/RegisterClientRequest.php
// ============================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:client,email',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'address.required' => 'L\'adresse est obligatoire',
        ];
    }
}

