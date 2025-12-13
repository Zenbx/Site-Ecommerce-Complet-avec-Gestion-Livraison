<?php

// ============================================
// 11. REPORT ISSUE REQUEST
// app/Http/Requests/ReportIssueRequest.php
// ============================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\DeliveryPerson;
    }

    public function rules(): array
    {
        return [
            'issue_type' => 'required|in:customer_unavailable,address_not_found,refused_package,damaged_package',
            'description' => 'required|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'issue_type.required' => 'Le type de problème est obligatoire',
            'issue_type.in' => 'Type de problème invalide',
            'description.required' => 'La description est obligatoire',
            'photo.image' => 'Le fichier doit être une image',
            'photo.max' => 'L\'image ne doit pas dépasser 5MB',
        ];
    }
}