<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller du dashboard administrateur
 * 
 * Ce controller fournit tous les endpoints nécessaires pour afficher
 * un dashboard complet avec des métriques en temps réel, des graphiques,
 * et des tableaux de performance.
 * 
 * Architecture :
 * Ce controller est un excellent exemple de "thin controller".
 * Il ne contient presque aucune logique métier. Toute la complexité
 * du calcul des statistiques est déléguée au StatisticsService.
 * Le controller se contente d'appeler les méthodes appropriées du service
 * et de formater les réponses.
 * 
 * Cette approche rend le code très testable car on peut tester le
 * StatisticsService indépendamment du controller.
 */
class DashboardController extends Controller
{
    protected $statisticsService;
    
    /**
     * Injection du StatisticsService
     * 
     * Ce service encapsule toute la logique complexe de calcul de métriques.
     */
    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }
    
    /**
     * Obtient les statistiques globales du dashboard
     * 
     * GET /api/admin/dashboard/overview
     * 
     * Query params:
     * - period: today, week, month, year, all (défaut: today)
     * 
     * Cette méthode retourne une vue d'ensemble complète avec toutes
     * les métriques principales : chiffre d'affaires, commandes, clients,
     * livraisons, taux de conversion, etc.
     */
    public function overview(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        
        // Validation de la période
        if (!in_array($period, ['today', 'week', 'month', 'year', 'all'])) {
            return generate_api_response(
                false,
                null,
                'Période invalide. Valeurs acceptées : today, week, month, year, all',
                400
            );
        }
        
        // Délégation complète au service
        // Le controller ne fait aucun calcul, il orchestre simplement
        $stats = $this->statisticsService->getAdminDashboardStats($period);
        
        // Le service retourne déjà les données dans le bon format,
        // le controller n'a qu'à les retourner
        return generate_api_response(true, [
            'period' => $period,
            'statistics' => $stats,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Obtient les données pour le graphique d'évolution des ventes
     * 
     * GET /api/admin/dashboard/sales-chart
     * 
     * Query params:
     * - period: week, month, year (défaut: month)
     * - group_by: day, week, month (défaut: day)
     * 
     * Cette méthode retourne des données formatées spécifiquement
     * pour être affichées dans un graphique Chart.js ou similaire
     * dans l'application Angular.
     */
    public function salesChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $groupBy = $request->get('group_by', 'day');
        
        // Validation
        $validPeriods = ['week', 'month', 'year'];
        $validGroupBy = ['day', 'week', 'month'];
        
        if (!in_array($period, $validPeriods)) {
            return generate_api_response(
                false,
                null,
                'Période invalide',
                400
            );
        }
        
        if (!in_array($groupBy, $validGroupBy)) {
            return generate_api_response(
                false,
                null,
                'Groupement invalide',
                400
            );
        }
        
        // Délégation au service
        $chartData = $this->statisticsService->getSalesChartData($period, $groupBy);
        
        // Le service retourne déjà les données au format attendu par Chart.js
        // avec les tableaux labels, orders, et revenue
        return generate_api_response(true, $chartData);
    }
    
    /**
     * Obtient la liste des produits les plus vendus
     * 
     * GET /api/admin/dashboard/top-products
     * 
     * Query params:
     * - limit: nombre de produits (défaut: 10)
     * - period: today, week, month, year (défaut: month)
     */
    public function topProducts(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', 'month');
        
        // Validation
        if ($limit < 1 || $limit > 50) {
            $limit = 10;
        }
        
        // Délégation au service
        $topProducts = $this->statisticsService->getTopSellingProducts($limit, $period);
        
        // Transformation des données avec les helpers pour formater les montants
        $formatted = $topProducts->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url,
                'total_sold' => (int) $product->total_sold,
                'total_revenue' => format_currency($product->total_revenue),
                'total_revenue_raw' => (float) $product->total_revenue,
                'unit_price' => format_currency($product->price),
            ];
        });
        
        return generate_api_response(true, $formatted);
    }
    
    /**
     * Obtient les statistiques de performance des livreurs
     * 
     * GET /api/admin/dashboard/delivery-performance
     * 
     * Cette méthode retourne un classement des livreurs par performance.
     */
    public function deliveryPerformance(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        
        // Pour cette fonctionnalité, on pourrait ajouter une méthode
        // getDeliveryPerformanceRanking dans le StatisticsService
        // Pour l'instant, implémentation simplifiée
        
        $deliveryPersons = \App\Models\DeliveryPerson::all();
        
        $performance = $deliveryPersons->map(function($deliveryPerson) use ($period) {
            $stats = $this->statisticsService->getDeliveryPersonStats($deliveryPerson, $period);
            
            return [
                'id' => $deliveryPerson->id,
                'name' => $deliveryPerson->name,
                'statistics' => $stats,
            ];
        })
        ->sortByDesc(function($item) {
            return $item['statistics']['deliveries']['success_rate'];
        })
        ->values();
        
        return generate_api_response(true, $performance);
    }
    
    /**
     * Obtient l'activité récente du système
     * 
     * GET /api/admin/dashboard/recent-activity
     * 
     * Cette méthode retourne les derniers événements importants :
     * nouvelles commandes, livraisons complétées, alertes de stock faible, etc.
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        
        // Récupérer les dernières commandes
        $recentOrders = \App\Models\Order::with('client')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($order) {
                return [
                    'type' => 'new_order',
                    'title' => 'Nouvelle commande',
                    'message' => sprintf(
                        'Commande %s de %s - %s',
                        generate_order_number($order->id),
                        $order->client->name,
                        format_currency($order->total_amount)
                    ),
                    'timestamp' => $order->created_at->toIso8601String(),
                    'time_ago' => time_ago($order->created_at),
                    'link' => '/admin/orders/' . $order->id,
                ];
            });
        
        // Récupérer les dernières livraisons complétées
        $recentDeliveries = \App\Models\Delivery::with(['order', 'deliveryPerson'])
            ->where('status', 'DELIVERED')
            ->latest('delivered_at')
            ->limit($limit)
            ->get()
            ->map(function($delivery) {
                return [
                    'type' => 'delivery_completed',
                    'title' => 'Livraison complétée',
                    'message' => sprintf(
                        'Livraison %s complétée par %s',
                        $delivery->tracking_code,
                        $delivery->deliveryPerson->name
                    ),
                    'timestamp' => $delivery->delivered_at->toIso8601String(),
                    'time_ago' => time_ago($delivery->delivered_at),
                    'link' => '/admin/deliveries/' . $delivery->id,
                ];
            });
        
        // Vérifier les produits en stock faible
        $lowStockProducts = \App\Models\Product::where('quantity', '>', 0)
            ->where('quantity', '<=', 10)
            ->where('is_active', true)
            ->get()
            ->map(function($product) {
                return [
                    'type' => 'low_stock',
                    'title' => 'Stock faible',
                    'message' => sprintf(
                        '%s - Plus que %d unités disponibles',
                        $product->name,
                        $product->quantity
                    ),
                    'timestamp' => now()->toIso8601String(),
                    'time_ago' => 'maintenant',
                    'link' => '/admin/products/' . $product->id,
                ];
            });
        
        // Fusionner toutes les activités et trier par date
        $allActivities = $recentOrders
            ->concat($recentDeliveries)
            ->concat($lowStockProducts)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
        
        return generate_api_response(true, $allActivities);
    }
}