<?php

// ============================================
// 3. EXPORT SERVICE - GÉNÉRATION DE DOCUMENTS
// app/Services/ExportService.php
// ============================================

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveriesExport;
use App\Exports\DeliveryPersonsExport;
use Carbon\Carbon;

/**
 * Service d'export de documents
 * 
 * Ce service encapsule toute la logique de génération de documents
 * PDF et Excel. Il utilise les packages Laravel les plus populaires :
 * - barryvdh/laravel-dompdf pour les PDF
 * - maatwebsite/laravel-excel pour les Excel
 * 
 * Avantages de centraliser dans un service :
 * - Réutilisabilité : peut être appelé depuis n'importe quel controller
 * - Testabilité : facile à mocker dans les tests
 * - Maintenabilité : toute la logique d'export au même endroit
 */
class ExportService
{
    /**
     * Exporte les livraisons en PDF
     * 
     * Génère un PDF professionnel avec :
     * - En-tête avec logo et période
     * - Statistiques résumées
     * - Tableau détaillé des livraisons
     * - Graphiques (optionnel)
     * - Pied de page avec pagination
     */
    public function exportDeliveriesToPDF($deliveries, Carbon $startDate, Carbon $endDate)
    {
        // Préparer les données pour la vue
        $data = [
            'title' => 'Rapport de Livraisons',
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
            
            // Statistiques résumées
            'summary' => [
                'total' => $deliveries->count(),
                'successful' => $deliveries->where('status', 'DELIVERED')->count(),
                'failed' => $deliveries->where('status', 'FAILED')->count(),
                'in_progress' => $deliveries->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])->count(),
                'success_rate' => $deliveries->count() > 0 
                    ? round(($deliveries->where('status', 'DELIVERED')->count() / $deliveries->count()) * 100, 2)
                    : 0,
            ],
            
            // Liste des livraisons
            'deliveries' => $deliveries->map(function($delivery) {
                return [
                    'tracking_code' => $delivery->tracking_code,
                    'order_number' => generate_order_number($delivery->order->id),
                    'client_name' => $delivery->order->client->name,
                    'delivery_person' => $delivery->deliveryPerson ? $delivery->deliveryPerson->name : 'Non assigné',
                    'status' => $this->translateStatus($delivery->status),
                    'created_at' => $delivery->created_at->format('d/m/Y H:i'),
                    'delivered_at' => $delivery->delivered_at ? $delivery->delivered_at->format('d/m/Y H:i') : '-',
                ];
            }),
        ];

        // Générer le PDF avec une vue Blade
        $pdf = PDF::loadView('exports.deliveries-pdf', $data);
        
        // Configuration du PDF
        $pdf->setPaper('a4', 'landscape') // Format paysage pour plus de colonnes
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Nom du fichier avec date
        $filename = 'rapport-livraisons-' . now()->format('Y-m-d-His') . '.pdf';

        // Retourner le PDF en download
        return $pdf->download($filename);
    }

    /**
     * Exporte les livraisons en Excel
     * 
     * Génère un fichier Excel avec plusieurs feuilles :
     * - Feuille 1 : Résumé des statistiques
     * - Feuille 2 : Liste détaillée des livraisons
     * - Feuille 3 : Graphiques (optionnel)
     */
    public function exportDeliveriesToExcel($deliveries, Carbon $startDate, Carbon $endDate)
    {
        $filename = 'rapport-livraisons-' . now()->format('Y-m-d-His') . '.xlsx';

        // Utilisation d'une classe Export personnalisée
        return Excel::download(
            new DeliveriesExport($deliveries, $startDate, $endDate),
            $filename
        );
    }

    /**
     * Exporte les performances des livreurs en PDF
     */
    public function exportDeliveryPersonsToPDF($deliveryPersons, Carbon $startDate, Carbon $endDate)
    {
        $data = [
            'title' => 'Rapport de Performance des Livreurs',
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
            
            'delivery_persons' => $deliveryPersons->map(function($person) use ($startDate, $endDate) {
                $deliveries = $person->deliveries()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $successful = $deliveries->where('status', 'DELIVERED')->count();
                $total = $deliveries->count();

                return [
                    'name' => $person->name,
                    'email' => $person->email,
                    'total_deliveries' => $total,
                    'successful' => $successful,
                    'failed' => $deliveries->where('status', 'FAILED')->count(),
                    'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                ];
            }),
        ];

        $pdf = PDF::loadView('exports.delivery-persons-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'rapport-livreurs-' . now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Traduit les statuts en français pour l'affichage
     */
    private function translateStatus(string $status): string
    {
        $translations = [
            'PENDING' => 'En attente',
            'ASSIGNED' => 'Assignée',
            'PICKED_UP' => 'Récupérée',
            'IN_TRANSIT' => 'En transit',
            'DELIVERED' => 'Livrée',
            'FAILED' => 'Échouée',
        ];

        return $translations[$status] ?? $status;
    }
}
