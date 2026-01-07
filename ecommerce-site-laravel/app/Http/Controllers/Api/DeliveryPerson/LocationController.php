<?php

namespace App\Http\Controllers\Api\DeliveryPerson;

use App\Events\DeliveryPersonLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Delivery Person Location",
 *     description="Endpoints for real-time location tracking of delivery persons"
 * )
 */
class LocationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/delivery-person/location/update",
     *     summary="Update delivery person location (called by mobile app)",
     *     tags={"Delivery Person Location"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example=3.8480),
     *             @OA\Property(property="longitude", type="number", format="float", example=11.5021),
     *             @OA\Property(property="address", type="string", example="Yaoundé, Cameroun")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Location updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get authenticated delivery person
        $deliveryPerson = auth('delivery-api')->user();
        
        if (!$deliveryPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Update location
        $deliveryPerson->updateLocation(
            $request->latitude,
            $request->longitude,
            $request->address
        );

        // Broadcast the location update to all listening clients (admin dashboard)
        broadcast(new DeliveryPersonLocationUpdated($deliveryPerson, [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address
        ]))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'delivery_person_id' => $deliveryPerson->id,
                'location' => $deliveryPerson->location,
                'is_online' => $deliveryPerson->is_online,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/delivery-person/location/status",
     *     summary="Set delivery person online/offline status",
     *     tags={"Delivery Person Location"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_online"},
     *             @OA\Property(property="is_online", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function setOnlineStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_online' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryPerson = auth('delivery-api')->user();
        
        if (!$deliveryPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $deliveryPerson->update(['is_online' => $request->is_online]);

        // Broadcast status change
        broadcast(new DeliveryPersonLocationUpdated($deliveryPerson));

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => [
                'is_online' => $deliveryPerson->is_online,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/delivery-person/location/current",
     *     summary="Get current authenticated delivery person location",
     *     tags={"Delivery Person Location"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Current location"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getCurrentLocation(Request $request): JsonResponse
    {
        $deliveryPerson = auth('delivery-api')->user();
        
        if (!$deliveryPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $deliveryPerson->id,
                'name' => $deliveryPerson->name,
                'location' => $deliveryPerson->location,
                'is_online' => $deliveryPerson->is_online,
                'is_available' => $deliveryPerson->is_available,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/delivery-persons/tracking/active",
     *     summary="Get all active delivery persons with their locations (Admin only)",
     *     tags={"Admin - Delivery Person Tracking"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of active delivery persons with locations",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/DeliveryPerson")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getActiveDeliveryPersons(): JsonResponse
    {
        $deliveryPersons = DeliveryPerson::where('is_online', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->where('last_location_update', '>=', now()->subMinutes(5))
            ->get()
            ->map(function ($deliveryPerson) {
                return [
                    'id' => $deliveryPerson->id,
                    'name' => $deliveryPerson->name,
                    'email' => $deliveryPerson->email,
                    'photo_url' => $deliveryPerson->photo_url,
                    'is_available' => $deliveryPerson->is_available,
                    'location' => $deliveryPerson->location,
                    'is_recently_active' => $deliveryPerson->isRecentlyActive(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $deliveryPersons,
            'count' => $deliveryPersons->count(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/delivery-persons/{id}/location",
     *     summary="Get specific delivery person location (Admin only)",
     *     tags={"Admin - Delivery Person Tracking"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Delivery person location"),
     *     @OA\Response(response=404, description="Delivery person not found")
     * )
     */
    /**
 * @OA\Get(
 *     path="/api/admin/delivery-persons/{deliveryPerson}/location",
 *     summary="Get specific delivery person location (Admin only)",
 *     tags={"Admin - Delivery Person Tracking"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="deliveryPerson",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Delivery person location"),
 *     @OA\Response(response=404, description="Delivery person not found")
 * )
 */
public function getLocation(DeliveryPerson $deliveryPerson): JsonResponse
{
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $deliveryPerson->id,
            'name' => $deliveryPerson->name,
            'email' => $deliveryPerson->email,
            'photo_url' => $deliveryPerson->photo_url,
            'location' => $deliveryPerson->location,
            'is_online' => $deliveryPerson->is_online,
            'is_available' => $deliveryPerson->is_available,
            'last_update' => $deliveryPerson->last_location_update?->diffForHumans(),
        ]
    ]);
}

    public function getAllLocations(): JsonResponse
    {
        $deliveryPersons = DeliveryPerson::all();

        return response()->json([
            'success' => true,
            'data' => $deliveryPersons,
        ]);
    }
}