<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Envoyer une notification de nouvelle commande
     */
    public function notifyNewOrder($order)
    {
        try {
            // Email au client
            // Mail::to($order->client->email)->send(new \App\Mail\OrderCreated($order));
            
            // Notification push si vous avez Firebase/OneSignal
            // $this->sendPushNotification($order->client, 'Commande créée', 'Votre commande a été créée avec succès');
            
            Log::info('Order notification sent', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send order notification', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Notifier le livreur d'une nouvelle livraison
     */
    public function notifyDeliveryAssignment($delivery)
    {
        try {
            // Email au livreur
            // Mail::to($delivery->deliveryPerson->email)->send(new \App\Mail\DeliveryAssigned($delivery));
            
            // Notification push
            // $this->sendPushNotification($delivery->deliveryPerson, 'Nouvelle livraison', 'Une livraison vous a été assignée');
            
            Log::info('Delivery assignment notification sent', ['delivery_id' => $delivery->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send delivery notification', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Notifier le client du statut de livraison
     */
    public function notifyDeliveryStatus($order, $status)
    {
        $messages = [
            'ASSIGNED' => 'Un livreur a été assigné à votre commande',
            'PICKED_UP' => 'Votre colis a été récupéré',
            'IN_TRANSIT' => 'Votre colis est en route',
            'DELIVERED' => 'Votre colis a été livré'
        ];
        
        $message = $messages[$status] ?? 'Mise à jour de votre livraison';
        
        try {
            // Email
            // Mail::to($order->client->email)->send(new \App\Mail\DeliveryStatusUpdate($order, $status));
            
            // Notification push
            // $this->sendPushNotification($order->client, 'Mise à jour livraison', $message);
            
            Log::info('Delivery status notification sent', [
                'order_id' => $order->id,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send delivery status notification', ['error' => $e->getMessage()]);
        }
    }
}