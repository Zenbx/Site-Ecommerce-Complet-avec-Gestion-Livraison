<?php

// ============================================
// app/Services/QRCodeService.php
// ============================================

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Génère un QR Code pour une commande
     */
    public function generateOrderQRCode($order)
    {
        $token = Str::random(32);
        $data = [
            'order_id' => $order->id,
            'token' => $token,
            'amount' => $order->total_amount,
            'customer' => $order->client->name
        ];
        
        // Sauvegarder le token dans la table delivery
        if ($order->delivery) {
            $order->delivery->update([
                'qr_token' => $token,
                'qr_expires_at' => now()->addDays(7)
            ]);
        }
        
        // Générer le QR Code
        $qrCode = QrCode::format('png')
            ->size(300)
            ->generate(json_encode($data));
        
        // Sauvegarder l'image
        $filename = 'qrcodes/order-' . $order->id . '.png';
        \Storage::disk('public')->put($filename, $qrCode);
        
        return \Storage::url($filename);
    }
    
    /**
     * Vérifie un QR Code
     */
    public function verifyQRCode($token, $orderId)
    {
        $delivery = \App\Models\Delivery::where('order_id', $orderId)
            ->where('qr_token', $token)
            ->where('qr_expires_at', '>', now())
            ->first();
        
        return $delivery !== null;
    }
}
