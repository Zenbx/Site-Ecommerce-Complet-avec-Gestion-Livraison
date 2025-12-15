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
     * Query params:
     * - period: today, week, month, year, all (défaut: today)
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
     * Cette méthode retourne un résumé des livraisons du jour,
     * optimisé pour être affiché sur l'écran d'accueil de l'app mobile.
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