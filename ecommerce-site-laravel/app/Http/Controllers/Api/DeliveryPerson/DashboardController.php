<?php

namespace App\Http\Controllers\Api\DeliveryPerson;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller du dashboard livreur
 * 
 * Ce controller fournit les statistiques personnelles pour les livreurs.
 * Contrairement au dashboard admin qui voit tout le système, ce dashboard
 * est centré sur les performances individuelles du livreur connecté.
 *
 * @OA\Tag(
 *     name="DeliveryPerson Dashboard",
 *     description="Delivery person dashboard"
 * )
 */
class DashboardController extends Controller
{
    protected $statisticsService;
    
    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }
    
    /**
     * Obtient les statistiques personnelles du livreur
     * 
     * GET /api/delivery-person/dashboard/overview
     * 
     * @OA\Get(
     *      path="/api/delivery-person/dashboard/overview",
     *      operationId="getDeliveryPersonDashboardOverview",
     *      tags={"DeliveryPerson Dashboard"},
     *      summary="Get personal stats",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="period", in="query", description="Period (today, week, month, year, all)", required=false, @OA\Schema(type="string", default="today")),
     *      @OA\Response(
     *          response=200,
     *          description="Dashboard stats",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="period", type="string"),
     *                  @OA\Property(property="statistics", type="object"),
     *                  @OA\Property(property="generated_at", type="string", format="date-time")
     *              )
     *          )
     *      )
     * )
     */
    public function overview(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        $period = $request->get('period', 'today');
        
        if (!in_array($period, ['today', 'week', 'month', 'year', 'all'])) {
            return generate_api_response(
                false,
                null,
                'Période invalide',
                400
            );
        }
        
        // Utilisation du StatisticsService pour calculer les stats personnelles
        $stats = $this->statisticsService->getDeliveryPersonStats($deliveryPerson, $period);
        
        return generate_api_response(true, [
            'period' => $period,
            'statistics' => $stats,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Obtient le résumé quotidien du livreur
     * 
     * GET /api/delivery-person/dashboard/daily-summary
     * 
     * @OA\Get(
     *      path="/api/delivery-person/dashboard/daily-summary",
     *      operationId="getDailySummary",
     *      tags={"DeliveryPerson Dashboard"},
     *      summary="Get daily summary",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Daily summary",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="date", type="string", format="date"),
     *                  @OA\Property(property="deliveries", type="object",
     *                      @OA\Property(property="total", type="integer"),
     *                      @OA\Property(property="completed", type="integer"),
     *                      @OA\Property(property="pending", type="integer"),
     *                      @OA\Property(property="failed", type="integer")
     *                  ),
     *                  @OA\Property(property="earnings", type="string"),
     *                  @OA\Property(property="earnings_raw", type="number"),
     *                  @OA\Property(property="is_available", type="boolean")
     *              )
     *          )
     *      )
     * )
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $deliveryPerson = $request->user('delivery-api');
        
        $today = $deliveryPerson->deliveries()
            ->whereDate('created_at', today())
            ->get();
        
        $completed = $today->where('status', 'DELIVERED')->count();
        $pending = $today->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])->count();
        $failed = $today->where('status', 'FAILED')->count();
        
        // Calculer les gains du jour
        $earnings = $today->where('status', 'DELIVERED')->sum(function($delivery) {
            // Vous pouvez avoir un système de commission pour les livreurs
            // Par exemple, 500 FCFA par livraison réussie
            return 500;
        });
        
        $data = [
            'date' => today()->toDateString(),
            'deliveries' => [
                'total' => $today->count(),
                'completed' => $completed,
                'pending' => $pending,
                'failed' => $failed,
            ],
            'earnings' => format_currency($earnings),
            'earnings_raw' => (float) $earnings,
            'is_available' => $deliveryPerson->is_available,
        ];
        
        return generate_api_response(true, $data);
    }
}