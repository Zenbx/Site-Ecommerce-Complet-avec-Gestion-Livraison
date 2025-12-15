<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Delivery;
use App\Models\DeliveryPerson;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service de calcul de statistiques et métriques
 * 
 * Ce service centralise tous les calculs de statistiques pour les dashboards.
 * Il utilise intensivement le cache pour améliorer les performances car les
 * statistiques peuvent nécessiter des requêtes SQL complexes et coûteuses.
 * 
 * Organisation :
 * - Statistiques admin (vue d'ensemble globale du business)
 * - Statistiques livreur (performance individuelle)
 * - Statistiques produits (best sellers, ruptures de stock)
 * - Statistiques temporelles (évolution dans le temps)
 * 
 * Utilisation du cache :
 * Les statistiques sont mises en cache avec des durées appropriées :
 * - Stats en temps réel : cache de 1 minute
 * - Stats du jour : cache de 5 minutes
 * - Stats historiques : cache de 1 heure
 */
class StatisticsService
{
    /**
     * Obtient les statistiques globales pour le dashboard admin
     * 
     * Cette méthode retourne un ensemble complet de métriques qui donnent
     * une vue d'ensemble de la santé du business :
     * - Chiffre d'affaires (total, aujourd'hui, cette semaine, ce mois)
     * - Nombre de commandes par statut
     * - Taux de conversion panier → commande
     * - Performance des livraisons
     * - Nombre de clients actifs
     * 
     * @param string $period 'today', 'week', 'month', 'year', 'all'
     * @return array
     */
    public function getAdminDashboardStats(string $period = 'today'): array
    {
        $cacheKey = "admin_dashboard_stats_{$period}";
        $cacheDuration = $period === 'today' ? 5 : 60; // 5 min pour today, 1h pour le reste
        
        return Cache::remember($cacheKey, now()->addMinutes($cacheDuration), function() use ($period) {
            $dateRange = $this->getDateRange($period);
            
            return [
                'revenue' => $this->calculateRevenue($dateRange),
                'orders' => $this->getOrdersStatistics($dateRange),
                'products' => $this->getProductsStatistics(),
                'customers' => $this->getCustomersStatistics($dateRange),
                'deliveries' => $this->getDeliveriesStatistics($dateRange),
                'growth' => $this->calculateGrowthRate($period),
            ];
        });
    }

    /**
     * Calcule les statistiques de chiffre d'affaires
     * 
     * @param array $dateRange ['start' => Carbon, 'end' => Carbon]
     * @return array
     */
    protected function calculateRevenue(array $dateRange): array
    {
        $query = Order::where('payment_status', 'COMPLETED');
        
        if ($dateRange['start']) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        
        $total = $query->sum('total_amount');
        
        // Calculer également par période
        $today = Order::where('payment_status', 'COMPLETED')
            ->whereDate('created_at', today())
            ->sum('total_amount');
        
        $thisWeek = Order::where('payment_status', 'COMPLETED')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_amount');
        
        $thisMonth = Order::where('payment_status', 'COMPLETED')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        
        return [
            'total' => (float) $total,
            'today' => (float) $today,
            'this_week' => (float) $thisWeek,
            'this_month' => (float) $thisMonth,
            'average_order_value' => $query->count() > 0 ? $total / $query->count() : 0,
        ];
    }

    /**
     * Obtient les statistiques des commandes
     * 
     * @param array $dateRange
     * @return array
     */
    protected function getOrdersStatistics(array $dateRange): array
    {
        $query = Order::query();
        
        if ($dateRange['start']) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        
        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'PENDING')->count(),
            'confirmed' => (clone $query)->where('status', 'CONFIRMED')->count(),
            'processing' => (clone $query)->where('status', 'PROCESSING')->count(),
            'shipped' => (clone $query)->where('status', 'SHIPPED')->count(),
            'delivered' => (clone $query)->where('status', 'DELIVERED')->count(),
            'cancelled' => (clone $query)->where('status', 'CANCELLED')->count(),
        ];
    }

    /**
     * Obtient les statistiques des produits
     * 
     * @return array
     */
    protected function getProductsStatistics(): array
    {
        return [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'out_of_stock' => Product::where('quantity', 0)->count(),
            'low_stock' => Product::where('quantity', '>', 0)
                ->where('quantity', '<=', 10)
                ->count(),
        ];
    }

    /**
     * Obtient les statistiques des clients
     * 
     * @param array $dateRange
     * @return array
     */
    protected function getCustomersStatistics(array $dateRange): array
    {
        $totalClients = Client::count();
        
        $newQuery = Client::query();
        if ($dateRange['start']) {
            $newQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        $newClients = $newQuery->count();
        
        // Clients actifs = clients qui ont passé au moins une commande
        $activeClients = Client::whereHas('orders')->count();
        
        return [
            'total' => $totalClients,
            'new' => $newClients,
            'active' => $activeClients,
            'retention_rate' => $totalClients > 0 ? ($activeClients / $totalClients) * 100 : 0,
        ];
    }

    /**
     * Obtient les statistiques des livraisons
     * 
     * @param array $dateRange
     * @return array
     */
    protected function getDeliveriesStatistics(array $dateRange): array
    {
        $query = Delivery::query();
        
        if ($dateRange['start']) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        
        $total = $query->count();
        $delivered = (clone $query)->where('status', 'DELIVERED')->count();
        $failed = (clone $query)->where('status', 'FAILED')->count();
        
        return [
            'total' => $total,
            'pending' => (clone $query)->where('status', 'PENDING')->count(),
            'assigned' => (clone $query)->where('status', 'ASSIGNED')->count(),
            'in_transit' => (clone $query)->where('status', 'IN_TRANSIT')->count(),
            'delivered' => $delivered,
            'failed' => $failed,
            'success_rate' => $total > 0 ? ($delivered / $total) * 100 : 0,
        ];
    }

    /**
     * Calcule le taux de croissance par rapport à la période précédente
     * 
     * @param string $period
     * @return array
     */
    protected function calculateGrowthRate(string $period): array
    {
        $currentRange = $this->getDateRange($period);
        $previousRange = $this->getPreviousDateRange($period);
        
        $currentRevenue = Order::where('payment_status', 'COMPLETED')
            ->whereBetween('created_at', [$currentRange['start'], $currentRange['end']])
            ->sum('total_amount');
        
        $previousRevenue = Order::where('payment_status', 'COMPLETED')
            ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
            ->sum('total_amount');
        
        $growthRate = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;
        
        return [
            'revenue_growth' => round($growthRate, 2),
            'current_revenue' => (float) $currentRevenue,
            'previous_revenue' => (float) $previousRevenue,
        ];
    }

    /**
     * Obtient les produits les plus vendus
     * 
     * @param int $limit Nombre de produits à retourner
     * @param string $period Période à considérer
     * @return \Illuminate\Support\Collection
     */
    public function getTopSellingProducts(int $limit = 10, string $period = 'month'): \Illuminate\Support\Collection
    {
        $cacheKey = "top_selling_products_{$limit}_{$period}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function() use ($limit, $period) {
            $dateRange = $this->getDateRange($period);
            
            return DB::table('order_lines')
                ->join('products', 'order_lines.product_id', '=', 'products.id')
                ->join('orders', 'order_lines.order_id', '=', 'orders.id')
                ->where('orders.payment_status', 'COMPLETED')
                ->when($dateRange['start'], function($query) use ($dateRange) {
                    $query->whereBetween('orders.created_at', [$dateRange['start'], $dateRange['end']]);
                })
                ->select(
                    'products.id',
                    'products.name',
                    'products.image_url',
                    'products.price',
                    DB::raw('SUM(order_lines.quantity) as total_sold'),
                    DB::raw('SUM(order_lines.quantity * order_lines.unit_price) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.image_url', 'products.price')
                ->orderByDesc('total_sold')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Obtient les statistiques d'un livreur spécifique
     * 
     * @param DeliveryPerson $deliveryPerson
     * @param string $period
     * @return array
     */
    public function getDeliveryPersonStats(DeliveryPerson $deliveryPerson, string $period = 'today'): array
    {
        $cacheKey = "delivery_person_stats_{$deliveryPerson->id}_{$period}";
        $cacheDuration = $period === 'today' ? 5 : 30;
        
        return Cache::remember($cacheKey, now()->addMinutes($cacheDuration), function() use ($deliveryPerson, $period) {
            $dateRange = $this->getDateRange($period);
            
            $query = Delivery::where('delivery_person_id', $deliveryPerson->id);
            
            if ($dateRange['start']) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }
            
            $total = $query->count();
            $delivered = (clone $query)->where('status', 'DELIVERED')->count();
            $failed = (clone $query)->where('status', 'FAILED')->count();
            $inProgress = (clone $query)->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])->count();
            
            // Calculer le temps moyen de livraison
            $avgDeliveryTime = Delivery::where('delivery_person_id', $deliveryPerson->id)
                ->where('status', 'DELIVERED')
                ->whereNotNull('delivered_at')
                ->when($dateRange['start'], function($q) use ($dateRange) {
                    $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
                })
                ->get()
                ->average(function($delivery) {
                    return $delivery->created_at->diffInMinutes($delivery->delivered_at);
                });
            
            return [
                'period' => $period,
                'deliveries' => [
                    'total' => $total,
                    'delivered' => $delivered,
                    'failed' => $failed,
                    'in_progress' => $inProgress,
                    'success_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
                ],
                'performance' => [
                    'average_delivery_time' => $avgDeliveryTime ? round($avgDeliveryTime) . ' minutes' : 'N/A',
                    'on_time_rate' => $this->calculateOnTimeRate($deliveryPerson, $dateRange),
                ],
            ];
        });
    }

    /**
     * Calcule le taux de livraison à temps
     * 
     * @param DeliveryPerson $deliveryPerson
     * @param array $dateRange
     * @return float
     */
    protected function calculateOnTimeRate(DeliveryPerson $deliveryPerson, array $dateRange): float
    {
        // Une livraison est "à temps" si elle est complétée dans les 2 heures
        // après avoir été assignée (vous pouvez ajuster ce seuil)
        
        $deliveries = Delivery::where('delivery_person_id', $deliveryPerson->id)
            ->where('status', 'DELIVERED')
            ->whereNotNull('delivered_at')
            ->when($dateRange['start'], function($q) use ($dateRange) {
                $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            })
            ->get();
        
        if ($deliveries->isEmpty()) {
            return 0;
        }
        
        $onTime = $deliveries->filter(function($delivery) {
            $deliveryTime = $delivery->created_at->diffInMinutes($delivery->delivered_at);
            return $deliveryTime <= 120; // 2 heures
        })->count();
        
        return round(($onTime / $deliveries->count()) * 100, 2);
    }

    /**
     * Obtient les données pour un graphique d'évolution des ventes
     * 
     * @param string $period
     * @param string $groupBy 'day', 'week', 'month'
     * @return array
     */
    public function getSalesChartData(string $period = 'month', string $groupBy = 'day'): array
    {
        $cacheKey = "sales_chart_{$period}_{$groupBy}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function() use ($period, $groupBy) {
            $dateRange = $this->getDateRange($period);
            
            $dateFormat = match($groupBy) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-%W',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };
            
            $results = Order::where('payment_status', 'COMPLETED')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as date"),
                    DB::raw('COUNT(*) as orders_count'),
                    DB::raw('SUM(total_amount) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            return [
                'labels' => $results->pluck('date')->toArray(),
                'orders' => $results->pluck('orders_count')->toArray(),
                'revenue' => $results->pluck('revenue')->map(fn($v) => (float)$v)->toArray(),
            ];
        });
    }

    /**
     * Obtient une plage de dates selon la période demandée
     * 
     * @param string $period
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    protected function getDateRange(string $period): array
    {
        $now = now();
        
        return match($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'all' => [
                'start' => null,
                'end' => $now,
            ],
            default => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Obtient la plage de dates de la période précédente
     * 
     * @param string $period
     * @return array
     */
    protected function getPreviousDateRange(string $period): array
    {
        $current = $this->getDateRange($period);
        
        if (!$current['start']) {
            return $current;
        }
        
        $duration = $current['start']->diffInDays($current['end']);
        
        return [
            'start' => $current['start']->copy()->subDays($duration + 1),
            'end' => $current['start']->copy()->subDay(),
        ];
    }
}