<?php

/**
 * Helpers personnalisés pour l'application e-commerce
 * 
 * Ces fonctions sont chargées automatiquement et disponibles globalement
 * dans toute l'application. Elles fournissent des raccourcis pour des
 * opérations courantes spécifiques à votre domaine métier.
 */

if (!function_exists('format_currency')) {
    /**
     * Formate un montant en FCFA avec séparateurs de milliers
     * 
     * @param float|int $amount Montant à formater
     * @param bool $showSymbol Afficher le symbole FCFA ou pas
     * @return string
     * 
     * Exemples:
     * format_currency(1500) => "1 500 FCFA"
     * format_currency(1500, false) => "1 500"
     * format_currency(1234567.89) => "1 234 568 FCFA"
     */
    function format_currency($amount, bool $showSymbol = true): string
    {
        $formatted = number_format((float)$amount, 0, ',', ' ');
        return $showSymbol ? $formatted . ' FCFA' : $formatted;
    }
}

if (!function_exists('generate_order_number')) {
    /**
     * Génère un numéro de commande formaté
     * 
     * Format: ORD-YYYYMMDD-XXXXXX
     * Exemple: ORD-20241214-000123
     * 
     * @param int $orderId ID de la commande
     * @return string
     */
    function generate_order_number(int $orderId): string
    {
        return sprintf(
            'ORD-%s-%s',
            date('Ymd'),
            str_pad($orderId, 6, '0', STR_PAD_LEFT)
        );
    }
}

if (!function_exists('generate_tracking_code')) {
    /**
     * Génère un code de suivi unique pour une livraison
     * 
     * Format: TRACK-YYYYMMDD-XXXXX
     * Exemple: TRACK-20241214-A3F2C
     * 
     * @param int $deliveryId ID de la livraison
     * @return string
     */
    function generate_tracking_code(int $deliveryId): string
    {
        return sprintf(
            'TRACK-%s-%s',
            date('Ymd'),
            strtoupper(substr(md5($deliveryId . time()), 0, 5))
        );
    }
}

if (!function_exists('format_phone_number')) {
    /**
     * Formate un numéro de téléphone camerounais
     * 
     * Convertit différents formats en format international standard
     * 
     * @param string $phone Numéro à formater
     * @return string|null
     * 
     * Exemples:
     * format_phone_number('650000000') => '237650000000'
     * format_phone_number('+237 6 50 00 00 00') => '237650000000'
     * format_phone_number('06 50 00 00 00') => '237650000000'
     */
    function format_phone_number(string $phone): ?string
    {
        // Enlever tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Si commence par 237, c'est déjà au bon format
        if (str_starts_with($cleaned, '237') && strlen($cleaned) === 12) {
            return $cleaned;
        }
        
        // Si commence par 0, enlever le 0 et ajouter 237
        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
            return '237' . substr($cleaned, 1);
        }
        
        // Si 9 chiffres, ajouter 237
        if (strlen($cleaned) === 9) {
            return '237' . $cleaned;
        }
        
        // Format invalide
        return null;
    }
}

if (!function_exists('time_ago')) {
    /**
     * Retourne une chaîne "il y a X temps" en français
     * 
     * @param \Carbon\Carbon|string $date
     * @return string
     * 
     * Exemples:
     * time_ago($date) => "il y a 5 minutes"
     * time_ago($date) => "il y a 2 heures"
     * time_ago($date) => "il y a 3 jours"
     */
    function time_ago($date): string
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        return $date->locale('fr')->diffForHumans();
    }
}

if (!function_exists('calculate_delivery_fee')) {
    /**
     * Calcule les frais de livraison selon la distance
     * 
     * Règles de tarification :
     * - 0-5 km : 500 FCFA
     * - 5-10 km : 1000 FCFA
     * - 10-20 km : 2000 FCFA
     * - 20+ km : 100 FCFA par km
     * 
     * @param float $distanceKm Distance en kilomètres
     * @return float Frais de livraison en FCFA
     */
    function calculate_delivery_fee(float $distanceKm): float
    {
        if ($distanceKm <= 5) {
            return 500;
        } elseif ($distanceKm <= 10) {
            return 1000;
        } elseif ($distanceKm <= 20) {
            return 2000;
        } else {
            return $distanceKm * 100;
        }
    }
}

if (!function_exists('is_business_hours')) {
    /**
     * Vérifie si nous sommes pendant les heures d'ouverture
     * 
     * @param string $startTime Heure d'ouverture (format H:i)
     * @param string $endTime Heure de fermeture (format H:i)
     * @return bool
     */
    function is_business_hours(string $startTime = '08:00', string $endTime = '20:00'): bool
    {
        $now = now();
        $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
        $end = \Carbon\Carbon::createFromFormat('H:i', $endTime);
        
        return $now->between($start, $end);
    }
}

if (!function_exists('get_order_status_badge')) {
    /**
     * Retourne la classe CSS pour le badge de statut de commande
     * 
     * Utile pour afficher des badges colorés dans les interfaces
     * 
     * @param string $status
     * @return string Nom de classe CSS
     */
    function get_order_status_badge(string $status): string
    {
        return match($status) {
            'PENDING' => 'badge-warning',
            'CONFIRMED' => 'badge-info',
            'PROCESSING' => 'badge-primary',
            'SHIPPED' => 'badge-purple',
            'DELIVERED' => 'badge-success',
            'CANCELLED' => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}

if (!function_exists('get_delivery_status_icon')) {
    /**
     * Retourne l'icône appropriée pour un statut de livraison
     * 
     * @param string $status
     * @return string Nom d'icône (compatible avec Font Awesome)
     */
    function get_delivery_status_icon(string $status): string
    {
        return match($status) {
            'PENDING' => 'clock',
            'ASSIGNED' => 'user-check',
            'PICKED_UP' => 'box',
            'IN_TRANSIT' => 'truck',
            'DELIVERED' => 'check-circle',
            'FAILED' => 'times-circle',
            default => 'question-circle',
        };
    }
}

if (!function_exists('sanitize_search_query')) {
    /**
     * Nettoie et sécurise une chaîne de recherche
     * 
     * @param string $query
     * @return string
     */
    function sanitize_search_query(string $query): string
    {
        // Enlever les caractères spéciaux SQL
        $cleaned = preg_replace('/[^a-zA-Z0-9\sÀ-ÿ\-]/', '', $query);
        
        // Limiter la longueur
        return substr(trim($cleaned), 0, 100);
    }
}

if (!function_exists('generate_api_response')) {
    /**
     * Génère une réponse JSON standardisée pour l'API
     * 
     * @param bool $success
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    function generate_api_response(
        bool $success,
        $data = null,
        ?string $message = null,
        int $statusCode = 200
    ): \Illuminate\Http\JsonResponse {
        $response = ['success' => $success];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $statusCode);
    }
}

if (!function_exists('get_cameroon_cities')) {
    /**
     * Retourne la liste des principales villes du Cameroun
     * 
     * @return array
     */
    function get_cameroon_cities(): array
    {
        return [
            'Douala',
            'Yaoundé',
            'Bafoussam',
            'Garoua',
            'Bamenda',
            'Maroua',
            'Nkongsamba',
            'Ngaoundéré',
            'Bertoua',
            'Loum',
            'Kumba',
            'Edéa',
            'Kribi',
            'Limbé',
            'Buéa',
        ];
    }
}

if (!function_exists('calculate_tax')) {
    /**
     * Calcule la TVA sur un montant
     * 
     * @param float $amount Montant HT
     * @param float $taxRate Taux de TVA (défaut 19.25% au Cameroun)
     * @return array ['tax' => float, 'total' => float]
     */
    function calculate_tax(float $amount, float $taxRate = 19.25): array
    {
        $tax = ($amount * $taxRate) / 100;
        
        return [
            'tax' => round($tax, 2),
            'total' => round($amount + $tax, 2),
        ];
    }
}

if (!function_exists('mask_email')) {
    /**
     * Masque partiellement une adresse email pour la confidentialité
     * 
     * @param string $email
     * @return string
     * 
     * Exemple:
     * mask_email('john.doe@example.com') => 'j***e@example.com'
     */
    function mask_email(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        
        [$name, $domain] = explode('@', $email);
        
        if (strlen($name) <= 2) {
            $masked = $name[0] . '*';
        } else {
            $masked = $name[0] . str_repeat('*', strlen($name) - 2) . substr($name, -1);
        }
        
        return $masked . '@' . $domain;
    }
}

if (!function_exists('mask_phone')) {
    /**
     * Masque partiellement un numéro de téléphone
     * 
     * @param string $phone
     * @return string
     * 
     * Exemple:
     * mask_phone('237650000000') => '237650***000'
     */
    function mask_phone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($cleaned);
        
        if ($length < 6) {
            return $cleaned;
        }
        
        $start = substr($cleaned, 0, 6);
        $end = substr($cleaned, -3);
        
        return $start . '***' . $end;
    }
}