<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\DeliveryPerson;
use App\Models\Admin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service de gestion des notifications multi-canaux
 * 
 * Ce service centralise l'envoi de notifications via différents canaux :
 * - Push notifications mobiles (Firebase Cloud Messaging)
 * - Emails (via le système de mailing Laravel)
 * - SMS (via un provider comme Twilio - optionnel)
 * 
 * Architecture :
 * Plutôt que d'avoir du code de notification éparpillé dans tous les controllers,
 * nous centralisons tout ici. Cela permet de :
 * 1. Changer facilement de provider (passer de FCM à OneSignal par exemple)
 * 2. Ajouter de nouveaux canaux sans modifier les controllers
 * 3. Gérer les préférences de notification des utilisateurs en un seul endroit
 * 4. Logger et monitorer toutes les notifications envoyées
 * 5. Gérer les erreurs et les retry de manière centralisée
 * 
 * Utilisation typique :
 * app(NotificationService::class)->sendDeliveryAssigned($delivery);
 * app(NotificationService::class)->sendOrderConfirmed($order);
 */
class NotificationService
{
    /**
     * Clé serveur Firebase Cloud Messaging
     * 
     * Cette clé est obtenue depuis la console Firebase de votre projet.
     * Elle permet à votre serveur d'envoyer des notifications push.
     * 
     * IMPORTANT : Ne jamais commiter cette clé dans Git !
     * Elle doit être dans .env et jamais dans le code.
     * 
     * @var string
     */
    protected $fcmServerKey;
    
    /**
     * URL de l'API FCM v1
     * 
     * @var string
     */
    protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Constructeur - récupère la configuration depuis .env
     */
    public function __construct()
    {
        $this->fcmServerKey = config('services.fcm.server_key');
    }

    /**
     * Envoie une notification de nouvelle livraison assignée au livreur
     * 
     * Cette notification est envoyée quand un admin assigne une livraison.
     * Elle doit alerter immédiatement le livreur pour qu'il accepte ou refuse.
     * 
     * Canaux utilisés :
     * - Push notification sur le téléphone (priorité haute)
     * - Email (en backup si le push échoue)
     * 
     * @param \App\Models\Delivery $delivery
     * @return bool Succès de l'envoi
     */
    public function sendDeliveryAssigned($delivery): bool
    {
        $deliveryPerson = $delivery->deliveryPerson;
        
        if (!$deliveryPerson) {
            Log::warning('Tentative d\'envoi de notification à un livreur inexistant', [
                'delivery_id' => $delivery->id,
            ]);
            return false;
        }
        
        // Charger les informations nécessaires
        $delivery->load('order.client');
        
        $title = '🚚 Nouvelle livraison assignée';
        $body = sprintf(
            'Commande #%s - %s à livrer. Montant: %s FCFA',
            str_pad($delivery->order_id, 6, '0', STR_PAD_LEFT),
            $delivery->order->client->name,
            number_format($delivery->order->total_amount, 0, ',', ' ')
        );
        
        $data = [
            'type' => 'delivery_assigned',
            'delivery_id' => $delivery->id,
            'tracking_code' => $delivery->tracking_code,
            'order_number' => 'ORD-' . str_pad($delivery->order_id, 6, '0', STR_PAD_LEFT),
            'customer_name' => $delivery->order->client->name,
            'delivery_address' => $delivery->delivery_address,
        ];
        
        // Envoyer notification push
        $pushSuccess = $this->sendPushNotification(
            $deliveryPerson->fcm_token,
            $title,
            $body,
            $data
        );
        
        // Envoyer email en backup
        if (!$pushSuccess) {
            $this->sendEmailNotification(
                $deliveryPerson->email,
                'Nouvelle livraison assignée',
                'emails.delivery-assigned',
                ['delivery' => $delivery]
            );
        }
        
        return $pushSuccess;
    }

    /**
     * Envoie une notification de confirmation de commande au client
     * 
     * @param \App\Models\Order $order
     * @return bool
     */
    public function sendOrderConfirmed($order): bool
    {
        $client = $order->client;
        
        $title = '✅ Commande confirmée';
        $body = sprintf(
            'Votre commande #%s a été confirmée. Montant total: %s FCFA',
            str_pad($order->id, 6, '0', STR_PAD_LEFT),
            number_format($order->total_amount, 0, ',', ' ')
        );
        
        $data = [
            'type' => 'order_confirmed',
            'order_id' => $order->id,
            'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'total_amount' => $order->total_amount,
        ];
        
        // Push notification
        if ($client->fcm_token) {
            return $this->sendPushNotification(
                $client->fcm_token,
                $title,
                $body,
                $data
            );
        }
        
        // Fallback email
        return $this->sendEmailNotification(
            $client->email,
            'Commande confirmée',
            'emails.order-confirmed',
            ['order' => $order]
        );
    }

    /**
     * Envoie une notification de livraison en cours au client
     * 
     * @param \App\Models\Delivery $delivery
     * @return bool
     */
    public function sendDeliveryInTransit($delivery): bool
    {
        $client = $delivery->order->client;
        $deliveryPerson = $delivery->deliveryPerson;
        
        $title = '🚚 Votre commande est en route';
        $body = sprintf(
            '%s est en route avec votre commande. Livraison estimée: %s',
            $deliveryPerson->name,
            $delivery->created_at->addHours(2)->format('H:i')
        );
        
        $data = [
            'type' => 'delivery_in_transit',
            'delivery_id' => $delivery->id,
            'tracking_code' => $delivery->tracking_code,
            'delivery_person_name' => $deliveryPerson->name,
            'delivery_person_phone' => $deliveryPerson->email, // Devrait être un vrai numéro
        ];
        
        if ($client->fcm_token) {
            return $this->sendPushNotification(
                $client->fcm_token,
                $title,
                $body,
                $data
            );
        }
        
        return false;
    }

    /**
     * Envoie une notification de livraison complétée au client
     * 
     * @param \App\Models\Delivery $delivery
     * @return bool
     */
    public function sendDeliveryCompleted($delivery): bool
    {
        $client = $delivery->order->client;
        
        $title = '✅ Livraison complétée';
        $body = 'Votre commande a été livrée avec succès. Merci pour votre confiance !';
        
        $data = [
            'type' => 'delivery_completed',
            'delivery_id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'delivered_at' => $delivery->delivered_at->toIso8601String(),
        ];
        
        if ($client->fcm_token) {
            $this->sendPushNotification(
                $client->fcm_token,
                $title,
                $body,
                $data
            );
        }
        
        // Toujours envoyer un email de confirmation
        return $this->sendEmailNotification(
            $client->email,
            'Livraison complétée',
            'emails.delivery-completed',
            ['delivery' => $delivery]
        );
    }

    /**
     * Envoie une notification push via Firebase Cloud Messaging
     * 
     * @param string|null $fcmToken Token FCM du destinataire
     * @param string $title Titre de la notification
     * @param string $body Corps de la notification
     * @param array $data Données supplémentaires (payload)
     * @return bool Succès de l'envoi
     */
    protected function sendPushNotification(
        ?string $fcmToken,
        string $title,
        string $body,
        array $data = []
    ): bool {
        // Si pas de token FCM, impossible d'envoyer
        if (!$fcmToken) {
            Log::info('Pas de FCM token disponible', ['title' => $title]);
            return false;
        }
        
        // Si la clé serveur FCM n'est pas configurée
        if (!$this->fcmServerKey) {
            Log::warning('FCM server key non configurée dans .env');
            return false;
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => $data,
                'priority' => 'high',
            ]);
            
            if ($response->successful()) {
                Log::info('Push notification envoyée avec succès', [
                    'title' => $title,
                    'fcm_token' => substr($fcmToken, 0, 20) . '...',
                ]);
                return true;
            }
            
            Log::error('Échec envoi push notification', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
            
        } catch (Exception $e) {
            Log::error('Exception lors de l\'envoi push notification', [
                'error' => $e->getMessage(),
                'title' => $title,
            ]);
            return false;
        }
    }

    /**
     * Envoie une notification par email
     * 
     * @param string $email Email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $view Vue Blade à utiliser
     * @param array $data Données pour la vue
     * @return bool
     */
    protected function sendEmailNotification(
        string $email,
        string $subject,
        string $view,
        array $data = []
    ): bool {
        try {
            Mail::send($view, $data, function($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject);
            });
            
            Log::info('Email de notification envoyé', [
                'email' => $email,
                'subject' => $subject,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Échec envoi email notification', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);
            return false;
        }
    }

    /**
     * Enregistre ou met à jour le token FCM d'un utilisateur
     * 
     * Cette méthode est appelée depuis les controllers d'authentification
     * quand un utilisateur se connecte depuis une app mobile.
     * 
     * @param mixed $user Instance de User, Client, DeliveryPerson, ou Admin
     * @param string $fcmToken Le token FCM fourni par le client
     * @return bool
     */
    public function registerFcmToken($user, string $fcmToken): bool
    {
        try {
            $user->update(['fcm_token' => $fcmToken]);
            
            Log::info('FCM token enregistré', [
                'user_type' => get_class($user),
                'user_id' => $user->id,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Erreur enregistrement FCM token', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Supprime le token FCM d'un utilisateur (lors de la déconnexion)
     * 
     * @param mixed $user
     * @return bool
     */
    public function removeFcmToken($user): bool
    {
        try {
            $user->update(['fcm_token' => null]);
            return true;
        } catch (Exception $e) {
            Log::error('Erreur suppression FCM token', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}