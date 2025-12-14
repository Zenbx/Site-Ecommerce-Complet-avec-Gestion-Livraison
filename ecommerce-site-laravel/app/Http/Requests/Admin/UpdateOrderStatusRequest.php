
<?php
// ============================================
// 9. UPDATE ORDER STATUS REQUEST
// app/Http/Requests/UpdateOrderStatusRequest.php
// ============================================

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Admin;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:PENDING,CONFIRMED,PROCESSING,SHIPPED,DELIVERED,CANCELLED',
            'note' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire',
            'status.in' => 'Statut invalide',
        ];
    }
}
