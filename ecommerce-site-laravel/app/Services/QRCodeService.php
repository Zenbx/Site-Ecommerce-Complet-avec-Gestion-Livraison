<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * Service de génération et gestion de codes QR
 * 
 * Ce service gère la création de codes QR pour différentes fonctionnalités :
 * - QR codes de confirmation de livraison
 * - QR codes pour les produits (optionnel)
 * - QR codes pour les commandes (optionnel)
 * 
 * Architecture :
 * Les QR codes sont générés en tant qu'images PNG et stockés dans le
 * système de fichiers Laravel (storage/app/public/qrcodes).
 * Une URL publique est retournée pour que les applications frontend
 * puissent afficher le QR code.
 * 
 * Sécurité :
 * Chaque QR code contient un token unique et a une date d'expiration.
 * Le système vérifie que le QR code n'a pas expiré avant de l'accepter.
 */
class QRCodeService
{
    /**
     * Génère un QR code pour une livraison
     * 
     * Le QR code contient un token unique qui permet au livreur de confirmer
     * qu'il livre bien le bon colis au bon client. Le client affiche ce QR code
     * dans son application, et le livreur le scanne avec son application mobile.
     * 
     * Format du token:
     * {delivery_id}|{timestamp}|{random_hash}
     * 
     * Exemple: "123|1702568400|a3f2c9d8e1b4"
     * 
     * @param \App\Models\Delivery $delivery Instance de la livraison
     * @param int $expirationDays Nombre de jours avant expiration (défaut: 7)
     * @return array ['token' => string, 'qr_url' => string, 'expires_at' => Carbon]
     * @throws Exception
     */
    public function generateDeliveryQRCode($delivery, int $expirationDays = 7): array
    {
        try {
            // Générer un token unique et sécurisé
            // Le token combine l'ID de la livraison, un timestamp, et un hash aléatoire
            $token = $this->generateSecureToken($delivery->id);
            
            // Calculer la date d'expiration
            $expiresAt = now()->addDays($expirationDays);
            
            // Construire les données à encoder dans le QR code
            // Format JSON pour faciliter le parsing côté mobile
            $qrData = json_encode([
                'type' => 'delivery',
                'delivery_id' => $delivery->id,
                'tracking_code' => $delivery->tracking_code,
                'token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);
            
            // Générer l'image du QR code
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($qrData)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->build();
            
            // Nom du fichier unique
            $filename = 'qrcodes/delivery-' . $delivery->id . '-' . time() . '.png';
            
            // Sauvegarder dans le storage public
            Storage::disk('public')->put($filename, $result->getString());
            
            // Générer l'URL publique
            $qrUrl = Storage::url($filename);
            
            return [
                'token' => $token,
                'qr_url' => $qrUrl,
                'qr_path' => $filename,
                'expires_at' => $expiresAt,
            ];
            
        } catch (Exception $e) {
            logger()->error('Erreur génération QR code', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Vérifie et valide un token de QR code
     * 
     * Cette méthode est appelée quand un livreur scanne un QR code.
     * Elle vérifie que :
     * 1. Le token existe et correspond à une livraison
     * 2. Le token n'a pas expiré
     * 3. Le token n'a pas déjà été utilisé
     * 
     * @param string $token Le token scanné
     * @param int $deliveryId L'ID de la livraison (pour double vérification)
     * @return array ['valid' => bool, 'message' => string, 'delivery_id' => int|null]
     */
    public function validateDeliveryQRToken(string $token, int $deliveryId): array
    {
        try {
            // Décoder le token pour extraire les informations
            $tokenParts = explode('|', $token);
            
            if (count($tokenParts) !== 3) {
                return [
                    'valid' => false,
                    'message' => 'Format de token invalide',
                    'delivery_id' => null,
                ];
            }
            
            [$tokenDeliveryId, $timestamp, $hash] = $tokenParts;
            
            // Vérifier que l'ID de livraison correspond
            if ((int)$tokenDeliveryId !== $deliveryId) {
                return [
                    'valid' => false,
                    'message' => 'Le QR code ne correspond pas à cette livraison',
                    'delivery_id' => (int)$tokenDeliveryId,
                ];
            }
            
            // Récupérer la livraison pour vérifier l'expiration
            $delivery = \App\Models\Delivery::find($deliveryId);
            
            if (!$delivery) {
                return [
                    'valid' => false,
                    'message' => 'Livraison introuvable',
                    'delivery_id' => $deliveryId,
                ];
            }
            
            // Vérifier l'expiration
            if ($delivery->qr_expires_at && $delivery->qr_expires_at < now()) {
                return [
                    'valid' => false,
                    'message' => 'QR code expiré. Demandez au client de régénérer le code.',
                    'delivery_id' => $deliveryId,
                ];
            }
            
            // Vérifier que le token n'a pas déjà été utilisé
            if ($delivery->qr_scanned_at) {
                return [
                    'valid' => false,
                    'message' => 'QR code déjà scanné le ' . $delivery->qr_scanned_at->format('d/m/Y à H:i'),
                    'delivery_id' => $deliveryId,
                ];
            }
            
            // Vérifier que le token stocké correspond
            if ($delivery->qr_token !== $token) {
                return [
                    'valid' => false,
                    'message' => 'Token invalide ou révoqué',
                    'delivery_id' => $deliveryId,
                ];
            }
            
            // Tout est bon !
            return [
                'valid' => true,
                'message' => 'QR code valide',
                'delivery_id' => $deliveryId,
            ];
            
        } catch (Exception $e) {
            logger()->error('Erreur validation QR token', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'valid' => false,
                'message' => 'Erreur lors de la validation',
                'delivery_id' => null,
            ];
        }
    }

    /**
     * Génère un token sécurisé unique
     * 
     * Format: {id}|{timestamp}|{random_hash}
     * 
     * @param int $id ID de l'entité (livraison, commande, etc.)
     * @return string
     */
    protected function generateSecureToken(int $id): string
    {
        return sprintf(
            '%d|%d|%s',
            $id,
            time(),
            Str::random(12)
        );
    }

    /**
     * Régénère un QR code (si le client l'a perdu ou s'il a expiré)
     * 
     * @param \App\Models\Delivery $delivery
     * @return array
     */
    public function regenerateDeliveryQRCode($delivery): array
    {
        // Supprimer l'ancien QR code du storage si il existe
        if ($delivery->qr_path) {
            Storage::disk('public')->delete($delivery->qr_path);
        }
        
        // Générer un nouveau QR code
        return $this->generateDeliveryQRCode($delivery);
    }

    /**
     * Supprime un QR code du storage
     * 
     * @param string $qrPath Chemin du fichier QR code
     * @return bool
     */
    public function deleteQRCode(string $qrPath): bool
    {
        try {
            if (Storage::disk('public')->exists($qrPath)) {
                return Storage::disk('public')->delete($qrPath);
            }
            return true;
        } catch (Exception $e) {
            logger()->error('Erreur suppression QR code', [
                'path' => $qrPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}