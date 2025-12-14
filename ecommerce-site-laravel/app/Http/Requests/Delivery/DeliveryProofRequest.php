<?php
// ============================================
// 10. DELIVERY PROOF REQUEST
// app/Http/Requests/DeliveryProofRequest.php
// ============================================

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\DeliveryPerson;
    }

    public function rules(): array
    {
        return [
            'proof_file' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'recipient_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'proof_file.required' => 'La preuve de livraison est obligatoire',
            'proof_file.image' => 'Le fichier doit être une image',
            'proof_file.mimes' => 'L\'image doit être au format jpeg, png ou jpg',
            'proof_file.max' => 'L\'image ne doit pas dépasser 5MB',
        ];
    }
}