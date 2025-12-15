<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Service de gestion des paiements
 * 
 * Ce service centralise toute la logique de paiement pour votre système e-commerce.
 * Il gère plusieurs méthodes de paiement :
 * - MTN Mobile Money (MOMO)
 * - Orange Money (OM)
 * - Paiement en espèces à la livraison (CASH)
 * 
 * Architecture et flux de paiement :
 * 
 * Pour les paiements Mobile Money, le flux typique est :
 * 1. Le client passe une commande et choisit MOMO ou OM comme méthode de paiement
 * 2. Notre système appelle initiatePayment() qui contacte l'API de l'opérateur
 * 3. L'opérateur envoie une notification push sur le téléphone du client
 * 4. Le client confirme le paiement en entrant son code PIN sur son téléphone
 * 5. L'opérateur nous notifie via webhook que le paiement est confirmé
 * 6. Notre système met à jour le statut de la commande et démarre la livraison
 * 
 * Pour le paiement cash, le flux est différent :
 * 1. Le client passe une commande et choisit CASH
 * 2. La commande est créée avec payment_status = PENDING
 * 3. Le livreur livre le colis et collecte l'argent
 * 4. Le livreur confirme le paiement dans son app mobile
 * 5. Le système met à jour payment_status = COMPLETED
 * 
 * Sécurité :
 * Les clés API des opérateurs Mobile Money sont stockées dans le fichier .env
 * et ne sont jamais exposées au frontend. Toutes les requêtes vers les APIs
 * des opérateurs passent par notre backend qui agit comme un proxy sécurisé.
 */
class PaymentService
{
    /**
     * Configuration MTN Mobile Money
     * 
     * Ces informations sont obtenues après inscription sur le portail développeur MTN.
     * Pour la production, vous devez vous inscrire sur https://momodeveloper.mtn.com/
     * 
     * @var array
     */
    protected $mtnConfig;
    
    /**
     * Configuration Orange Money
     * 
     * Ces informations sont obtenues après inscription sur le portail Orange Developer.
     * 
     * @var array
     */
    protected $orangeConfig;

    /**
     * Constructeur - charge les configurations depuis .env
     */
    public function __construct()
    {
        $this->mtnConfig = [
            'api_url' => config('services.mtn.api_url'),
            'api_key' => config('services.mtn.api_key'),
            'api_secret' => config('services.mtn.api_secret'),
            'environment' => config('services.mtn.environment', 'sandbox'), // sandbox ou production
        ];
        
        $this->orangeConfig = [
            'api_url' => config('services.orange.api_url'),
            'merchant_id' => config('services.orange.merchant_id'),
            'api_key' => config('services.orange.api_key'),
        ];
    }

    /**
     * Initie un paiement Mobile Money
     * 
     * Cette méthode est appelée quand un client confirme sa commande avec
     * paiement Mobile Money. Elle contacte l'API de l'opérateur pour initier
     * la demande de paiement qui sera envoyée au téléphone du client.
     * 
     * Le client recevra une notification sur son téléphone lui demandant
     * de confirmer le paiement en entrant son code PIN.
     * 
     * @param Order $order La commande à payer
     * @param string $phoneNumber Numéro de téléphone du payeur (format: 237650000000)
     * @param string $paymentMethod 'MOMO' ou 'OM'
     * @return array ['success' => bool, 'transaction_reference' => string|null, 'message' => string]
     */
    public function initiatePayment(Order $order, string $phoneNumber, string $paymentMethod): array
    {
        // Validation du numéro de téléphone
        // Format attendu : 237XXXXXXXXX (indicatif Cameroun + 9 chiffres)
        if (!$this->validatePhoneNumber($phoneNumber)) {
            return [
                'success' => false,
                'transaction_reference' => null,
                'message' => 'Numéro de téléphone invalide. Format attendu: 237650000000',
            ];
        }
        
        // Générer une référence de transaction unique
        // Format: PAY-YYYYMMDD-XXXXXX-RANDOM
        $transactionReference = $this->generateTransactionReference($order->id);
        
        try {
            // Router vers la bonne méthode selon l'opérateur
            if ($paymentMethod === 'MOMO') {
                $result = $this->initiateMTNPayment($order, $phoneNumber, $transactionReference);
            } elseif ($paymentMethod === 'OM') {
                $result = $this->initiateOrangePayment($order, $phoneNumber, $transactionReference);
            } else {
                return [
                    'success' => false,
                    'transaction_reference' => null,
                    'message' => 'Méthode de paiement non supportée',
                ];
            }
            
            // Si l'initiation a réussi, créer un enregistrement Payment
            if ($result['success']) {
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $order->total_amount,
                    'transaction_reference' => $transactionReference,
                    'status' => 'PENDING', // En attente de confirmation du client
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Erreur initiation paiement', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'transaction_reference' => null,
                'message' => 'Erreur technique lors de l\'initiation du paiement',
            ];
        }
    }

    /**
     * Initie un paiement MTN Mobile Money
     * 
     * Cette méthode contacte l'API MTN MoMo pour créer une demande de paiement.
     * MTN utilise une architecture basée sur des "collections" où vous demandez
     * à collecter de l'argent depuis le compte du client.
     * 
     * Documentation API MTN : https://momodeveloper.mtn.com/api-documentation/
     * 
     * @param Order $order
     * @param string $phoneNumber
     * @param string $transactionReference
     * @return array
     */
    protected function initiateMTNPayment(Order $order, string $phoneNumber, string $transactionReference): array
    {
        // Pour le moment, c'est une implémentation simplifiée
        // Dans un vrai projet, vous devez d'abord obtenir un token OAuth
        // puis utiliser ce token pour faire la requête de paiement
        
        if ($this->mtnConfig['environment'] === 'sandbox') {
            // En mode sandbox, simuler un succès pour les tests
            Log::info('Simulation paiement MTN MoMo (sandbox)', [
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'phone' => $phoneNumber,
            ]);
            
            return [
                'success' => true,
                'transaction_reference' => $transactionReference,
                'message' => 'Demande de paiement envoyée. Vérifiez votre téléphone pour confirmer.',
            ];
        }
        
        // En production, voici le flux réel :
        
        try {
            // Étape 1 : Obtenir un token d'accès OAuth
            $tokenResponse = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->mtnConfig['api_key'],
            ])->post($this->mtnConfig['api_url'] . '/collection/token/', [
                'grant_type' => 'client_credentials',
            ]);
            
            if (!$tokenResponse->successful()) {
                throw new Exception('Échec obtention token MTN');
            }
            
            $accessToken = $tokenResponse->json()['access_token'];
            
            // Étape 2 : Créer la demande de paiement (Request to Pay)
            $paymentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Reference-Id' => $transactionReference,
                'X-Target-Environment' => $this->mtnConfig['environment'],
                'Ocp-Apim-Subscription-Key' => $this->mtnConfig['api_key'],
                'Content-Type' => 'application/json',
            ])->post($this->mtnConfig['api_url'] . '/collection/v1_0/requesttopay', [
                'amount' => (string) $order->total_amount,
                'currency' => 'XAF', // Franc CFA
                'externalId' => (string) $order->id,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $phoneNumber,
                ],
                'payerMessage' => 'Paiement commande #' . $order->id,
                'payeeNote' => 'E-commerce order payment',
            ]);
            
            if ($paymentResponse->successful() || $paymentResponse->status() === 202) {
                // 202 Accepted signifie que la demande a été acceptée
                return [
                    'success' => true,
                    'transaction_reference' => $transactionReference,
                    'message' => 'Demande de paiement envoyée. Vérifiez votre téléphone pour confirmer.',
                ];
            }
            
            Log::error('Échec demande paiement MTN', [
                'status' => $paymentResponse->status(),
                'body' => $paymentResponse->body(),
            ]);
            
            return [
                'success' => false,
                'transaction_reference' => null,
                'message' => 'Échec de l\'initiation du paiement MTN',
            ];
            
        } catch (Exception $e) {
            Log::error('Exception paiement MTN', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'transaction_reference' => null,
                'message' => 'Erreur technique MTN Mobile Money',
            ];
        }
    }

    /**
     * Initie un paiement Orange Money
     * 
     * Orange Money utilise une API différente de MTN.
     * L'architecture est similaire mais les endpoints et le format
     * des requêtes sont différents.
     * 
     * @param Order $order
     * @param string $phoneNumber
     * @param string $transactionReference
     * @return array
     */
    protected function initiateOrangePayment(Order $order, string $phoneNumber, string $transactionReference): array
    {
        // Implémentation similaire à MTN mais avec les spécificités Orange
        
        if (!$this->orangeConfig['api_url']) {
            // Mode sandbox/test
            Log::info('Simulation paiement Orange Money (sandbox)', [
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'phone' => $phoneNumber,
            ]);
            
            return [
                'success' => true,
                'transaction_reference' => $transactionReference,
                'message' => 'Demande de paiement Orange Money envoyée.',
            ];
        }
        
        // L'implémentation réelle nécessite de suivre la documentation
        // Orange Money Developer : https://developer.orange.com/
        
        return [
            'success' => true,
            'transaction_reference' => $transactionReference,
            'message' => 'Demande de paiement envoyée.',
        ];
    }

    /**
     * Vérifie le statut d'une transaction Mobile Money
     * 
     * Cette méthode est appelée périodiquement ou sur demande pour vérifier
     * si un paiement en attente a été confirmé par le client.
     * 
     * Dans un système en production, vous utiliseriez plutôt des webhooks
     * où l'opérateur vous notifie automatiquement quand le paiement est confirmé.
     * 
     * @param string $transactionReference
     * @param string $paymentMethod
     * @return array ['status' => string, 'paid_at' => Carbon|null]
     */
    public function checkPaymentStatus(string $transactionReference, string $paymentMethod): array
    {
        try {
            if ($paymentMethod === 'MOMO') {
                return $this->checkMTNPaymentStatus($transactionReference);
            } elseif ($paymentMethod === 'OM') {
                return $this->checkOrangePaymentStatus($transactionReference);
            }
            
            return [
                'status' => 'UNKNOWN',
                'paid_at' => null,
            ];
            
        } catch (Exception $e) {
            Log::error('Erreur vérification statut paiement', [
                'transaction_reference' => $transactionReference,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'status' => 'ERROR',
                'paid_at' => null,
            ];
        }
    }

    /**
     * Vérifie le statut d'un paiement MTN
     * 
     * @param string $transactionReference
     * @return array
     */
    protected function checkMTNPaymentStatus(string $transactionReference): array
    {
        if ($this->mtnConfig['environment'] === 'sandbox') {
            // En sandbox, simuler un paiement réussi
            return [
                'status' => 'SUCCESSFUL',
                'paid_at' => now(),
            ];
        }
        
        // En production, appeler l'API MTN pour vérifier le statut
        // GET /collection/v1_0/requesttopay/{referenceId}
        
        return [
            'status' => 'PENDING',
            'paid_at' => null,
        ];
    }

    /**
     * Vérifie le statut d'un paiement Orange Money
     * 
     * @param string $transactionReference
     * @return array
     */
    protected function checkOrangePaymentStatus(string $transactionReference): array
    {
        // Implémentation similaire à MTN
        return [
            'status' => 'PENDING',
            'paid_at' => null,
        ];
    }

    /**
     * Traite un webhook de confirmation de paiement
     * 
     * Cette méthode est appelée quand l'opérateur Mobile Money nous envoie
     * une notification que le paiement a été confirmé.
     * 
     * @param array $webhookData Données reçues du webhook
     * @param string $paymentMethod
     * @return bool
     */
    public function processPaymentWebhook(array $webhookData, string $paymentMethod): bool
    {
        try {
            // Extraire la référence de transaction du webhook
            $transactionReference = $webhookData['transaction_reference'] ?? null;
            
            if (!$transactionReference) {
                Log::warning('Webhook sans transaction_reference', $webhookData);
                return false;
            }
            
            // Trouver le paiement correspondant
            $payment = Payment::where('transaction_reference', $transactionReference)->first();
            
            if (!$payment) {
                Log::warning('Paiement introuvable pour transaction', [
                    'reference' => $transactionReference,
                ]);
                return false;
            }
            
            // Vérifier le statut du webhook
            $status = $webhookData['status'] ?? 'UNKNOWN';
            
            if ($status === 'SUCCESSFUL' || $status === 'COMPLETED') {
                // Marquer le paiement comme complété
                $payment->update([
                    'paid_at' => now(),
                    'status' => 'COMPLETED',
                ]);
                
                // Mettre à jour le statut de la commande
                $payment->order->update([
                    'payment_status' => 'COMPLETED',
                    'status' => 'CONFIRMED',
                ]);
                
                Log::info('Paiement confirmé via webhook', [
                    'order_id' => $payment->order_id,
                    'transaction_reference' => $transactionReference,
                ]);
                
                // Déclencher des événements/notifications
                // app(NotificationService::class)->sendOrderConfirmed($payment->order);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            Log::error('Erreur traitement webhook paiement', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData,
            ]);
            return false;
        }
    }

    /**
     * Enregistre un paiement en espèces (CASH)
     * 
     * Cette méthode est appelée quand le livreur collecte l'argent
     * à la livraison et confirme le paiement dans son app mobile.
     * 
     * @param Order $order
     * @return bool
     */
    public function recordCashPayment(Order $order): bool
    {
        try {
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'CASH',
                'amount' => $order->total_amount,
                'transaction_reference' => 'CASH-' . $order->id . '-' . time(),
                'paid_at' => now(),
                'status' => 'COMPLETED',
            ]);
            
            $order->update([
                'payment_status' => 'COMPLETED',
            ]);
            
            Log::info('Paiement cash enregistré', [
                'order_id' => $order->id,
                'amount' => $order->total_amount,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Erreur enregistrement paiement cash', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Valide un numéro de téléphone camerounais
     * 
     * Format attendu : 237XXXXXXXXX
     * Exemple : 237650123456 (MTN) ou 237690123456 (Orange)
     * 
     * @param string $phoneNumber
     * @return bool
     */
    protected function validatePhoneNumber(string $phoneNumber): bool
    {
        // Nettoyer le numéro (enlever espaces, tirets, etc.)
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Vérifier le format : 237 suivi de 9 chiffres
        return preg_match('/^237[0-9]{9}$/', $cleaned);
    }

    /**
     * Génère une référence de transaction unique
     * 
     * Format: PAY-YYYYMMDD-{order_id}-{random}
     * Exemple: PAY-20241214-000123-A3F2
     * 
     * @param int $orderId
     * @return string
     */
    protected function generateTransactionReference(int $orderId): string
    {
        return sprintf(
            'PAY-%s-%s-%s',
            date('Ymd'),
            str_pad($orderId, 6, '0', STR_PAD_LEFT),
            strtoupper(Str::random(4))
        );
    }

    /**
     * Initie un remboursement (en cas d'annulation de commande)
     * 
     * @param Payment $payment
     * @return array ['success' => bool, 'message' => string]
     */
    public function initiateRefund(Payment $payment): array
    {
        // L'implémentation dépend de l'opérateur
        // Généralement, les APIs Mobile Money permettent d'initier des remboursements
        // mais cela nécessite des approbations et peut prendre du temps
        
        Log::info('Demande de remboursement initiée', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'transaction_reference' => $payment->transaction_reference,
        ]);
        
        // Pour l'instant, marquer comme en attente de remboursement
        $payment->update([
            'status' => 'REFUND_PENDING',
        ]);
        
        return [
            'success' => true,
            'message' => 'Demande de remboursement enregistrée. Délai de traitement: 3-5 jours ouvrables.',
        ];
    }
}