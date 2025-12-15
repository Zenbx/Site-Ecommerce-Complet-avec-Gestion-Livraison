<?php

// ============================================
// 2. REPORT CONTROLLER - GÉNÉRATION DE RAPPORTS
// app/Http/Controllers/Api/Admin/ReportController.php
// ============================================

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\DeliveryPerson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Controller dédié aux rapports et exports
 * 
 * Ce controller gère tout ce qui concerne la génération de rapports
 * statistiques et leur export dans différents formats (PDF, Excel).
 * 
 * Séparation des responsabilités :
 * - DeliveryController : gestion opérationnelle des livraisons
 * - ReportController : analyse et reporting
 * 
 * Cette séparation permet de garder chaque controller focalisé sur
 * une seule responsabilité (principe SOLID).
 */
class ReportController extends Controller
{
    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Génère un rapport de livraisons
     * 
     * GET /api/admin/reports/deliveries
     * 
     * Ce endpoint génère des statistiques détaillées sur les livraisons
     * pour une période donnée. Il peut être consommé directement par
     * Angular pour afficher les stats, ou utilisé pour générer un export.
     */
    public function deliveriesReport(Request $request): JsonResponse
    {
        // Validation de la période
        $validated = $request->validate([
            'period' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date|after_or_equal:start_date',
        ]);

        // Déterminer les dates selon la période
        [$startDate, $endDate] = $this->resolvePeriod(
            $validated['period'],
            $request->start_date,
            $request->end_date
        );

        // Récupérer les livraisons de la période
        $deliveries = Delivery::with(['order', 'deliveryPerson'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Calculer les statistiques
        $stats = [
            'period' => [
                'type' => $validated['period'],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days_count' => $startDate->diffInDays($endDate) + 1,
            ],
            
            'totals' => [
                'deliveries' => $deliveries->count(),
                'successful' => $deliveries->where('status', 'DELIVERED')->count(),
                'failed' => $deliveries->where('status', 'FAILED')->count(),
                'in_progress' => $deliveries->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])->count(),
                'pending' => $deliveries->where('status', 'PENDING')->count(),
            ],
            
            'rates' => [
                'success_rate' => $this->calculatePercentage(
                    $deliveries->where('status', 'DELIVERED')->count(),
                    $deliveries->count()
                ),
                'failure_rate' => $this->calculatePercentage(
                    $deliveries->where('status', 'FAILED')->count(),
                    $deliveries->count()
                ),
            ],
            
            'timing' => [
                'average_delivery_time' => $this->calculateAverageDeliveryTime($deliveries->where('status', 'DELIVERED')),
                'fastest_delivery' => $this->getFastestDelivery($deliveries->where('status', 'DELIVERED')),
                'slowest_delivery' => $this->getSlowestDelivery($deliveries->where('status', 'DELIVERED')),
            ],
            
            'by_delivery_person' => $this->groupByDeliveryPerson($deliveries),
            
            'by_day' => $this->groupByDay($deliveries, $startDate, $endDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }

    /**
     * Exporte un rapport de livraisons en PDF
     * 
     * GET /api/admin/reports/deliveries/export/pdf
     * 
     * Ce endpoint utilise le ExportService pour générer un PDF
     * bien formaté avec toutes les statistiques.
     */
    public function exportDeliveriesPDF(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date',
        ]);

        [$startDate, $endDate] = $this->resolvePeriod(
            $validated['period'],
            $request->start_date,
            $request->end_date
        );

        $deliveries = Delivery::with(['order.client', 'deliveryPerson'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Délégation au service d'export
        // Le service va créer un PDF professionnel avec mise en page
        return $this->exportService->exportDeliveriesToPDF($deliveries, $startDate, $endDate);
    }

    /**
     * Exporte un rapport de livraisons en Excel
     * 
     * GET /api/admin/reports/deliveries/export/excel
     */
    public function exportDeliveriesExcel(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date',
        ]);

        [$startDate, $endDate] = $this->resolvePeriod(
            $validated['period'],
            $request->start_date,
            $request->end_date
        );

        $deliveries = Delivery::with(['order.client', 'deliveryPerson'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return $this->exportService->exportDeliveriesToExcel($deliveries, $startDate, $endDate);
    }

    /**
     * Rapport de performance des livreurs
     * 
     * GET /api/admin/reports/delivery-persons
     */
    public function deliveryPersonsReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date',
        ]);

        [$startDate, $endDate] = $this->resolvePeriod(
            $validated['period'],
            $request->start_date,
            $request->end_date
        );

        $deliveryPersons = DeliveryPerson::withCount([
            'deliveries as total_deliveries' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            },
            'deliveries as successful_deliveries' => function($q) use ($startDate, $endDate) {
                $q->where('status', 'DELIVERED')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'deliveries as failed_deliveries' => function($q) use ($startDate, $endDate) {
                $q->where('status', 'FAILED')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            },
        ])->get();

        $stats = $deliveryPersons->map(function($person) {
            $successRate = $person->total_deliveries > 0
                ? round(($person->successful_deliveries / $person->total_deliveries) * 100, 2)
                : 0;

            return [
                'id' => $person->id,
                'name' => $person->name,
                'email' => $person->email,
                'statistics' => [
                    'total_deliveries' => $person->total_deliveries,
                    'successful' => $person->successful_deliveries,
                    'failed' => $person->failed_deliveries,
                    'success_rate' => $successRate,
                ],
            ];
        })->sortByDesc('statistics.total_deliveries')->values();

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }

    /**
     * Exporte le rapport des livreurs en PDF
     */
    public function exportDeliveryPersonsPDF(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date',
        ]);

        [$startDate, $endDate] = $this->resolvePeriod(
            $validated['period'],
            $request->start_date,
            $request->end_date
        );

        $deliveryPersons = DeliveryPerson::with(['deliveries' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();

        return $this->exportService->exportDeliveryPersonsToPDF($deliveryPersons, $startDate, $endDate);
    }

    // ============================================
    // MÉTHODES PRIVÉES UTILITAIRES
    // ============================================

    /**
     * Résout la période en dates concrètes
     */
    private function resolvePeriod(string $period, $startDate = null, $endDate = null): array
    {
        switch ($period) {
            case 'today':
                return [Carbon::today(), Carbon::today()->endOfDay()];
            
            case 'week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            
            case 'month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            
            case 'year':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            
            case 'custom':
                return [Carbon::parse($startDate), Carbon::parse($endDate)->endOfDay()];
            
            default:
                return [Carbon::today(), Carbon::today()->endOfDay()];
        }
    }

    private function calculatePercentage($part, $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0;
    }

    private function calculateAverageDeliveryTime($deliveries): ?string
    {
        if ($deliveries->isEmpty()) {
            return null;
        }

        $totalMinutes = $deliveries->sum(function($delivery) {
            return $delivery->created_at->diffInMinutes($delivery->delivered_at);
        });

        $averageMinutes = $totalMinutes / $deliveries->count();
        $hours = floor($averageMinutes / 60);
        $minutes = $averageMinutes % 60;

        return $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes}min";
    }

    private function getFastestDelivery($deliveries): ?array
    {
        if ($deliveries->isEmpty()) {
            return null;
        }

        $fastest = $deliveries->sortBy(function($delivery) {
            return $delivery->created_at->diffInMinutes($delivery->delivered_at);
        })->first();

        return [
            'tracking_code' => $fastest->tracking_code,
            'time' => $fastest->created_at->diffForHumans($fastest->delivered_at, true),
        ];
    }

    private function getSlowestDelivery($deliveries): ?array
    {
        if ($deliveries->isEmpty()) {
            return null;
        }

        $slowest = $deliveries->sortByDesc(function($delivery) {
            return $delivery->created_at->diffInMinutes($delivery->delivered_at);
        })->first();

        return [
            'tracking_code' => $slowest->tracking_code,
            'time' => $slowest->created_at->diffForHumans($slowest->delivered_at, true),
        ];
    }

    private function groupByDeliveryPerson($deliveries): array
    {
        return $deliveries->groupBy('delivery_person_id')->map(function($group) {
            $person = $group->first()->deliveryPerson;
            
            return [
                'name' => $person ? $person->name : 'Non assigné',
                'total' => $group->count(),
                'successful' => $group->where('status', 'DELIVERED')->count(),
                'failed' => $group->where('status', 'FAILED')->count(),
                'success_rate' => $this->calculatePercentage(
                    $group->where('status', 'DELIVERED')->count(),
                    $group->count()
                ),
            ];
        })->values()->toArray();
    }

    private function groupByDay($deliveries, $startDate, $endDate): array
    {
        $days = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayDeliveries = $deliveries->filter(function($delivery) use ($currentDate) {
                return $delivery->created_at->isSameDay($currentDate);
            });

            $days[] = [
                'date' => $currentDate->format('Y-m-d'),
                'total' => $dayDeliveries->count(),
                'successful' => $dayDeliveries->where('status', 'DELIVERED')->count(),
                'failed' => $dayDeliveries->where('status', 'FAILED')->count(),
            ];

            $currentDate->addDay();
        }

        return $days;
    }
}