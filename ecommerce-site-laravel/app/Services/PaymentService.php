<?php

// ============================================
// app/Services/PaymentService.php
// ============================================

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Initier un paiement Mobile Money
     */
    public function initiateMobileMoneyPayment(Order $order, $phoneNumber, $method = 'MOMO')
    {
        // Logique d'intégration avec l'API de paiement
        // Exemple: MTN Mobile Money, Orange Money
        
        $transactionRef = 'TXN-' . strtoupper(Str::random(10));
        
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $method,
            'amount' => $order->total_amount,
            'transaction_reference' => $transactionRef
        ]);
        
        // Appel API du provider de paiement
        // $response = $this->callPaymentAPI($phoneNumber, $order->total_amount);
        
        return [
            'success' => true,
            'transaction_reference' => $transactionRef,
            'payment_url' => "https://payment.example.com/pay/{$transactionRef}"
        ];
    }
    
    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus($transactionReference)
    {
        $payment = Payment::where('transaction_reference', $transactionReference)->first();
        
        if (!$payment) {
            return ['status' => 'not_found'];
        }
        
        // Appel API pour vérifier le statut
        // $apiStatus = $this->callPaymentStatusAPI($transactionReference);
        
        return [
            'status' => 'pending', // ou 'completed', 'failed'
            'payment' => $payment
        ];
    }
    
    /**
     * Confirmer un paiement
     */
    public function confirmPayment($transactionReference)
    {
        $payment = Payment::where('transaction_reference', $transactionReference)->first();
        
        if ($payment) {
            $payment->update(['paid_at' => now()]);
            $payment->order->update(['payment_status' => 'COMPLETED']);
            
            return true;
        }
        
        return false;
    }
}