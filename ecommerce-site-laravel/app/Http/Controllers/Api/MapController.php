<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeolocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller pour les fonctionnalités de cartographie
 * 
 * Ce controller expose des endpoints qui utilisent OpenStreetMap et OSRM
 * pour fournir des fonctionnalités de géolocalisation et de navigation.
 * 
 * Endpoints disponibles :
 * - POST /api/map/geocode : Convertir une adresse en coordonnées GPS
 * - POST /api/map/reverse-geocode : Convertir des coordonnées en adresse
 * - POST /api/map/route : Calculer un itinéraire entre deux points
 * - POST /api/map/distance : Calculer la distance à vol d'oiseau
 * 
 * Ces endpoints sont utilisés par :
 * - L'application mobile React Native des livreurs pour la navigation
 * - L'application Angular des admins pour afficher les positions sur carte
 * - Le système backend pour valider et normaliser les adresses
 */
class MapController extends Controller
{
    /**
     * Instance du service de géolocalisation
     * 
     * @var GeolocationService
     */
    protected $geolocationService;
    
    /**
     * Constructeur - injection du service de géolocalisation
     * 
     * Laravel injecte automatiquement le service grâce à son conteneur
     * de dépendances. Cela permet de tester facilement ce controller
     * en mockant le service dans les tests unitaires.
     */
    public function __construct(GeolocationService $geolocationService)
    {
        $this->geolocationService = $geolocationService;
    }
    
    /**
     * Convertit une adresse textuelle en coordonnées GPS (geocoding)
     * 
     * POST /api/map/geocode
     * 
     * @OA\Post(
     *      path="/api/map/geocode",
     *      operationId="geocodeAddress",
     *      tags={"Map"},
     *      summary="Geocode address",
     *      description="Convert text address to GPS coordinates",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"address"},
     *              @OA\Property(property="address", type="string", example="123 Main Street, Douala, Cameroun")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Geocoding successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="latitude", type="number", format="float"),
     *                  @OA\Property(property="longitude", type="number", format="float"),
     *                  @OA\Property(property="display_name", type="string"),
     *                  @OA\Property(property="address_details", type="object")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=404, description="Address not found")
     * )
     */
    public function geocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string|min:3|max:500',
        ]);
        
        try {
            $result = $this->geolocationService->geocodeAddress($validated['address']);
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de localiser cette adresse. Vérifiez qu\'elle est correcte et complète.',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du géocodage de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
    
    /**
     * Calcule un itinéraire optimal entre deux points
     * 
     * POST /api/map/route
     * 
     * @OA\Post(
     *      path="/api/map/route",
     *      operationId="calculateRoute",
     *      tags={"Map"},
     *      summary="Calculate route",
     *      description="Calculate optimal route between two points",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"start", "end"},
     *              @OA\Property(property="start", type="object",
     *                  @OA\Property(property="latitude", type="number", format="float"),
     *                  @OA\Property(property="longitude", type="number", format="float")
     *              ),
     *              @OA\Property(property="end", type="object",
     *                  @OA\Property(property="latitude", type="number", format="float"),
     *                  @OA\Property(property="longitude", type="number", format="float")
     *              ),
     *              @OA\Property(property="profile", type="string", enum={"driving", "walking", "cycling"}, default="driving")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Route calculated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="distance", type="number", format="float"),
     *                  @OA\Property(property="distance_text", type="string"),
     *                  @OA\Property(property="duration", type="integer"),
     *                  @OA\Property(property="duration_text", type="string"),
     *                  @OA\Property(property="geometry", type="array", @OA\Items(type="array", @OA\Items(type="number"))),
     *                  @OA\Property(property="steps", type="array", @OA\Items(type="object"))
     *              ),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      )
     * )
     */
    public function calculateRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start.latitude' => 'required|numeric|between:-90,90',
            'start.longitude' => 'required|numeric|between:-180,180',
            'end.latitude' => 'required|numeric|between:-90,90',
            'end.longitude' => 'required|numeric|between:-180,180',
            'profile' => 'sometimes|string|in:driving,walking,cycling',
        ]);
        
        $profile = $validated['profile'] ?? 'driving';
        
        try {
            $route = $this->geolocationService->calculateRoute(
                $validated['start']['latitude'],
                $validated['start']['longitude'],
                $validated['end']['latitude'],
                $validated['end']['longitude'],
                $profile
            );
            
            return response()->json([
                'success' => true,
                'data' => $route,
                'meta' => [
                    'profile' => $profile,
                    'generated_at' => now()->toIso8601String(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de l\'itinéraire',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
    
    /**
     * Calcule la distance à vol d'oiseau entre deux points
     * 
     * POST /api/map/distance
     * 
     * @OA\Post(
     *      path="/api/map/distance",
     *      operationId="calculateDistance",
     *      tags={"Map"},
     *      summary="Calculate distance",
     *      description="Calculate straight line distance between two points",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"point1", "point2"},
     *              @OA\Property(property="point1", type="object",
     *                  @OA\Property(property="latitude", type="number", format="float"),
     *                  @OA\Property(property="longitude", type="number", format="float")
     *              ),
     *              @OA\Property(property="point2", type="object",
     *                  @OA\Property(property="latitude", type="number", format="float"),
     *                  @OA\Property(property="longitude", type="number", format="float")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Distance calculated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="distance", type="number", format="float"),
     *                  @OA\Property(property="distance_text", type="string")
     *              )
     *          )
     *      )
     * )
     */
    public function calculateDistance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'point1.latitude' => 'required|numeric|between:-90,90',
            'point1.longitude' => 'required|numeric|between:-180,180',
            'point2.latitude' => 'required|numeric|between:-90,90',
            'point2.longitude' => 'required|numeric|between:-180,180',
        ]);
        
        try {
            $distance = $this->geolocationService->calculateDistance(
                $validated['point1']['latitude'],
                $validated['point1']['longitude'],
                $validated['point2']['latitude'],
                $validated['point2']['longitude']
            );
            
            $distanceText = $distance >= 1000 
                ? round($distance / 1000, 1) . ' km' 
                : round($distance) . ' m';
            
            return response()->json([
                'success' => true,
                'data' => [
                    'distance' => $distance,
                    'distance_text' => $distanceText,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de distance',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }
    
    /**
     * Obtient l'itinéraire pour une livraison spécifique
     * 
     * GET /api/delivery-person/deliveries/{delivery}/route
     * 
     * Cet endpoint est une version spécialisée de calculateRoute qui prend
     * directement une livraison en paramètre. Il récupère automatiquement
     * la position actuelle du livreur et l'adresse de destination depuis
     * la base de données, puis calcule l'itinéraire.
     * 
     * Query params:
     * - current_lat: Latitude actuelle du livreur
     * - current_lon: Longitude actuelle du livreur
     * 
     * Response: Même format que calculateRoute
     * 
     * Cas d'usage typique :
     * Dans l'app mobile React Native, quand le livreur ouvre une livraison,
     * l'app connaît l'ID de la livraison et la position GPS actuelle du téléphone.
     * Elle peut simplement appeler cet endpoint pour obtenir l'itinéraire sans
     * avoir besoin de géocoder l'adresse de destination elle-même.
     */
    public function getDeliveryRoute(Request $request, $deliveryId): JsonResponse
    {
        // Cette méthode sera implémentée dans le DeliveryController
        // car elle nécessite l'authentification du livreur et l'accès
        // à la table deliveries. Je la mentionne ici pour documentation.
        
        // Voir: DeliveryController::getRoute()
        
        return response()->json([
            'success' => false,
            'message' => 'Endpoint à implémenter dans DeliveryController',
        ], 501);
    }
}