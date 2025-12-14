<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Service de géolocalisation et calcul d'itinéraires
 * 
 * Ce service centralise toute la logique liée à :
 * - La conversion d'adresses en coordonnées GPS (geocoding)
 * - Le calcul d'itinéraires entre deux points via OSRM
 * - Le calcul de distances et temps de trajet
 * - La validation de coordonnées GPS
 * 
 * Il utilise des services externes gratuits :
 * - Nominatim pour le geocoding (API officielle d'OpenStreetMap)
 * - OSRM pour le calcul d'itinéraires
 * 
 * Les résultats sont mis en cache pour améliorer les performances
 * et réduire la charge sur les serveurs publics gratuits.
 */
class GeolocationService
{
    /**
     * URL de base de l'API Nominatim pour le geocoding
     * 
     * Nominatim est le service officiel d'OpenStreetMap pour convertir
     * des adresses en coordonnées GPS et vice versa. C'est gratuit et
     * ne nécessite pas de clé API, mais il faut respecter leur politique
     * d'utilisation équitable (max 1 requête par seconde).
     * 
     * @var string
     */
    protected $nominatimBaseUrl = 'https://nominatim.openstreetmap.org';
    
    /**
     * URL de base de l'API OSRM pour le calcul d'itinéraires
     * 
     * OSRM (Open Source Routing Machine) est un moteur de calcul
     * d'itinéraires ultra-rapide. Nous utilisons le serveur public
     * gratuit, mais pour une application en production avec beaucoup
     * de trafic, il serait recommandé d'héberger votre propre serveur OSRM.
     * 
     * @var string
     */
    protected $osrmBaseUrl = 'https://router.project-osrm.org';
    
    /**
     * Durée de mise en cache des résultats en minutes
     * 
     * Pour éviter de surcharger les APIs publiques gratuites et améliorer
     * les performances, nous mettons en cache les résultats. Un itinéraire
     * entre deux points fixes ne change pas fréquemment (sauf en cas de
     * fermeture de route), donc un cache de 60 minutes est raisonnable.
     * 
     * @var int
     */
    protected $cacheMinutes = 60;

    /**
     * Convertit une adresse textuelle en coordonnées GPS (latitude, longitude)
     * 
     * Cette méthode utilise l'API Nominatim d'OpenStreetMap pour transformer
     * une adresse comme "123 Main Street, Douala, Cameroun" en coordonnées
     * GPS précises comme latitude 4.0511, longitude 9.7679.
     * 
     * Le processus de geocoding n'est pas instantané et peut prendre quelques
     * centaines de millisecondes, c'est pourquoi nous mettons les résultats
     * en cache. Si la même adresse est recherchée plusieurs fois, nous
     * retournons le résultat en cache sans appeler l'API.
     * 
     * @param string $address L'adresse à géocoder (ex: "123 Main St, Douala")
     * @return array|null Tableau avec 'latitude' et 'longitude', ou null si non trouvée
     * @throws Exception Si l'API retourne une erreur
     */
    public function geocodeAddress(string $address): ?array
    {
        // Créer une clé de cache unique basée sur l'adresse
        // Nous normalisons l'adresse (minuscules, espaces trimés) pour que
        // "Main Street" et "main street" utilisent le même cache
        $cacheKey = 'geocode_' . md5(strtolower(trim($address)));
        
        // Vérifier si nous avons déjà cette adresse en cache
        // Si oui, retourner immédiatement le résultat sans appeler l'API
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // Appeler l'API Nominatim
            // Le paramètre 'format=json' demande une réponse JSON
            // Le paramètre 'limit=1' ne retourne que le meilleur résultat
            // Le paramètre 'addressdetails=1' inclut des détails sur l'adresse
            $response = Http::timeout(10)
                ->withHeaders([
                    // Nominatim exige un User-Agent personnalisé
                    // pour identifier votre application
                    'User-Agent' => config('app.name') . ' Delivery System',
                ])
                ->get($this->nominatimBaseUrl . '/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);
            
            // Vérifier si la requête a réussi
            if (!$response->successful()) {
                throw new Exception('Erreur lors du geocoding: ' . $response->status());
            }
            
            $data = $response->json();
            
            // Vérifier si au moins un résultat a été trouvé
            if (empty($data)) {
                // Aucun résultat trouvé pour cette adresse
                // Mettre null en cache pour éviter de rappeler l'API
                // pour une adresse qui n'existe pas
                Cache::put($cacheKey, null, now()->addMinutes($this->cacheMinutes));
                return null;
            }
            
            // Extraire les coordonnées du premier résultat
            $result = [
                'latitude' => (float) $data[0]['lat'],
                'longitude' => (float) $data[0]['lon'],
                // Informations supplémentaires utiles
                'display_name' => $data[0]['display_name'] ?? null,
                'address_details' => $data[0]['address'] ?? null,
            ];
            
            // Mettre en cache pour les prochaines requêtes
            Cache::put($cacheKey, $result, now()->addMinutes($this->cacheMinutes));
            
            return $result;
            
        } catch (Exception $e) {
            // Logger l'erreur pour debugging
            logger()->error('Geocoding error', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Calcule l'itinéraire optimal entre deux points
     * 
     * Cette méthode utilise l'API OSRM pour calculer le meilleur itinéraire
     * entre un point de départ et un point d'arrivée. OSRM prend en compte
     * la topologie des routes, les sens uniques, les limitations de vitesse,
     * et d'autres facteurs pour calculer l'itinéraire le plus rapide.
     * 
     * Le résultat inclut non seulement le tracé de l'itinéraire sous forme
     * de coordonnées GPS, mais aussi la distance totale, le temps estimé,
     * et des instructions de navigation étape par étape.
     * 
     * @param float $startLat Latitude du point de départ
     * @param float $startLon Longitude du point de départ
     * @param float $endLat Latitude du point d'arrivée
     * @param float $endLon Longitude du point d'arrivée
     * @param string $profile Profil de transport ('driving', 'walking', 'cycling')
     * @return array Informations sur l'itinéraire (distance, durée, géométrie)
     * @throws Exception Si l'API retourne une erreur
     */
    public function calculateRoute(
        float $startLat,
        float $startLon,
        float $endLat,
        float $endLon,
        string $profile = 'driving'
    ): array {
        // Valider le profil de transport
        $validProfiles = ['driving', 'walking', 'cycling'];
        if (!in_array($profile, $validProfiles)) {
            throw new Exception("Profil de transport invalide. Utilisez: " . implode(', ', $validProfiles));
        }
        
        // Créer une clé de cache unique pour cet itinéraire
        // Deux itinéraires identiques partageront le même cache
        $cacheKey = sprintf(
            'route_%s_%s_%s_%s_%s',
            $profile,
            round($startLat, 6),
            round($startLon, 6),
            round($endLat, 6),
            round($endLon, 6)
        );
        
        // Vérifier le cache
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // Construire l'URL de la requête OSRM
            // Format: /route/v1/{profile}/{lon1},{lat1};{lon2},{lat2}
            // IMPORTANT: OSRM attend longitude PUIS latitude (inverse de ce qu'on attend habituellement)
            $url = sprintf(
                '%s/route/v1/%s/%s,%s;%s,%s',
                $this->osrmBaseUrl,
                $profile,
                $startLon, $startLat,  // Point de départ (lon, lat)
                $endLon, $endLat       // Point d'arrivée (lon, lat)
            );
            
            // Paramètres de la requête
            // overview=full retourne la géométrie complète de l'itinéraire
            // geometries=geojson retourne les coordonnées au format GeoJSON standard
            // steps=true inclut les instructions de navigation étape par étape
            $response = Http::timeout(10)->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => true,
            ]);
            
            if (!$response->successful()) {
                throw new Exception('Erreur lors du calcul d\'itinéraire: ' . $response->status());
            }
            
            $data = $response->json();
            
            // Vérifier si OSRM a trouvé un itinéraire
            if ($data['code'] !== 'Ok' || empty($data['routes'])) {
                throw new Exception('Aucun itinéraire trouvé entre ces deux points');
            }
            
            // Extraire les informations du premier itinéraire (le meilleur)
            $route = $data['routes'][0];
            
            // Formater le résultat dans un format facile à utiliser
            $result = [
                // Distance totale en mètres
                'distance' => $route['distance'],
                
                // Distance formatée pour affichage (en km si > 1000m)
                'distance_text' => $this->formatDistance($route['distance']),
                
                // Durée estimée en secondes
                'duration' => $route['duration'],
                
                // Durée formatée pour affichage (ex: "25 minutes")
                'duration_text' => $this->formatDuration($route['duration']),
                
                // Géométrie de l'itinéraire (tableau de coordonnées [lon, lat])
                // Cette géométrie peut être directement utilisée par Leaflet pour tracer l'itinéraire
                'geometry' => $route['geometry']['coordinates'],
                
                // Instructions de navigation étape par étape
                // Utile pour afficher "Tournez à droite sur Avenue X"
                'steps' => $this->formatSteps($route['legs'][0]['steps'] ?? []),
                
                // Boîte englobante (bounding box) de l'itinéraire
                // Utile pour zoomer la carte au bon niveau pour voir tout l'itinéraire
                'bounds' => $this->calculateBounds($route['geometry']['coordinates']),
            ];
            
            // Mettre en cache
            Cache::put($cacheKey, $result, now()->addMinutes($this->cacheMinutes));
            
            return $result;
            
        } catch (Exception $e) {
            logger()->error('Route calculation error', [
                'start' => [$startLat, $startLon],
                'end' => [$endLat, $endLon],
                'profile' => $profile,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Calcule la distance à vol d'oiseau entre deux points GPS
     * 
     * Cette méthode utilise la formule de Haversine pour calculer la distance
     * la plus courte entre deux points à la surface d'une sphère (la Terre).
     * C'est la distance "à vol d'oiseau", pas la distance réelle par la route.
     * 
     * Cette fonction est utile pour des estimations rapides sans avoir besoin
     * d'appeler l'API OSRM. Par exemple, pour vérifier si un livreur est
     * proche de sa destination (moins de 100 mètres), on peut utiliser cette
     * formule simple plutôt que de calculer un itinéraire complet.
     * 
     * @param float $lat1 Latitude du premier point
     * @param float $lon1 Longitude du premier point
     * @param float $lat2 Latitude du deuxième point
     * @param float $lon2 Longitude du deuxième point
     * @return float Distance en mètres
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        // Rayon de la Terre en mètres
        $earthRadius = 6371000;
        
        // Convertir les degrés en radians
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);
        
        // Formule de Haversine
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        // Distance en mètres
        return $earthRadius * $c;
    }
    
    /**
     * Vérifie si des coordonnées GPS sont valides
     * 
     * @param float $latitude Doit être entre -90 et 90
     * @param float $longitude Doit être entre -180 et 180
     * @return bool
     */
    public function validateCoordinates(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90 &&
               $longitude >= -180 && $longitude <= 180;
    }
    
    /**
     * Formate une distance en mètres pour affichage convivial
     * 
     * @param float $meters Distance en mètres
     * @return string Distance formatée (ex: "2.5 km" ou "350 m")
     */
    protected function formatDistance(float $meters): string
    {
        if ($meters >= 1000) {
            return round($meters / 1000, 1) . ' km';
        }
        return round($meters) . ' m';
    }
    
    /**
     * Formate une durée en secondes pour affichage convivial
     * 
     * @param float $seconds Durée en secondes
     * @return string Durée formatée (ex: "1h 25min" ou "45 minutes")
     */
    protected function formatDuration(float $seconds): string
    {
        $minutes = round($seconds / 60);
        
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return $hours . 'h ' . $remainingMinutes . 'min';
        }
        
        return $minutes . ' minutes';
    }
    
    /**
     * Formate les étapes de navigation pour un affichage simplifié
     * 
     * @param array $steps Étapes brutes de OSRM
     * @return array Étapes formatées
     */
    protected function formatSteps(array $steps): array
    {
        return array_map(function($step) {
            return [
                'instruction' => $step['maneuver']['type'] ?? 'continue',
                'name' => $step['name'] ?? 'Route sans nom',
                'distance' => $this->formatDistance($step['distance'] ?? 0),
                'duration' => $this->formatDuration($step['duration'] ?? 0),
            ];
        }, $steps);
    }
    
    /**
     * Calcule la boîte englobante (bounding box) d'un ensemble de coordonnées
     * 
     * La bounding box est le rectangle minimal qui contient tous les points.
     * Elle est utilisée pour centrer et zoomer la carte correctement.
     * 
     * @param array $coordinates Tableau de coordonnées [[lon, lat], [lon, lat], ...]
     * @return array ['minLat', 'minLon', 'maxLat', 'maxLon']
     */
    protected function calculateBounds(array $coordinates): array
    {
        if (empty($coordinates)) {
            return ['minLat' => 0, 'minLon' => 0, 'maxLat' => 0, 'maxLon' => 0];
        }
        
        $lats = array_map(fn($coord) => $coord[1], $coordinates);
        $lons = array_map(fn($coord) => $coord[0], $coordinates);
        
        return [
            'minLat' => min($lats),
            'minLon' => min($lons),
            'maxLat' => max($lats),
            'maxLon' => max($lons),
        ];
    }
}