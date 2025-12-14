<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle Payment
 * 
 * Représente un paiement effectué pour une commande
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Méthode de paiement utilisée
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->getPaymentMethodLabel(),
            
            // Montant payé
            'amount' => number_format($this->amount, 2, '.', ''),
            
            // Référence de transaction
            // Fournie par le prestataire de paiement (Mobile Money, etc.)
            'transaction_reference' => $this->transaction_reference,
            
            // Date de paiement
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Retourne le libellé de la méthode de paiement
     */
    protected function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'MOMO' => 'MTN Mobile Money',
            'OM' => 'Orange Money',
            'CASH' => 'Espèces à la livraison',
            default => $this->payment_method,
        };
    }
}