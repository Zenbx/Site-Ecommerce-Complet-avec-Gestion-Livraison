<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel as ExcelWriter;

class ExportService
{
    // ================================
    // PDF
    // ================================
    public function exportDeliveriesToPDF($deliveries, Carbon $startDate, Carbon $endDate)
    {
        $data = [
            'title' => 'Rapport de Livraisons',
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
            'summary' => [
                'total' => $deliveries->count(),
                'successful' => $deliveries->where('status', 'DELIVERED')->count(),
                'failed' => $deliveries->where('status', 'FAILED')->count(),
                'in_progress' => $deliveries->whereIn('status', ['ASSIGNED','PICKED_UP','IN_TRANSIT'])->count(),
                'success_rate' => $deliveries->count() > 0 
                    ? round(($deliveries->where('status','DELIVERED')->count()/$deliveries->count())*100,2) 
                    : 0,
            ],
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

        $pdf = Pdf::loadView('exports.deliveries-pdf', $data)
            ->setPaper('a4','landscape')
            ->setOptions([
                'defaultFont'=>'Arial',
                'isHtml5ParserEnabled'=>true,
                'isRemoteEnabled'=>true,
            ]);

        $filename = 'rapport-livraisons-'.now()->format('Y-m-d-His').'.pdf';
        return $pdf->download($filename);
    }

    // ================================
    // Excel Livraisons
    // ================================
    public function exportDeliveriesToExcel($deliveries, Carbon $startDate, Carbon $endDate)
    {
        $exportCollection = new class($deliveries) implements FromCollection, WithHeadings, WithMapping {
            private $deliveries;

            public function __construct($deliveries)
            {
                $this->deliveries = $deliveries;
            }

            public function collection()
            {
                return $this->deliveries;
            }

            public function headings(): array
            {
                return ['Code suivi','Commande','Client','Livreur','Statut','Date création','Date livraison'];
            }

            public function map($delivery): array
            {
                return [
                    $delivery->tracking_code,
                    generate_order_number($delivery->order->id),
                    $delivery->order->client->name,
                    $delivery->deliveryPerson ? $delivery->deliveryPerson->name : 'Non assigné',
                    $delivery->status,
                    $delivery->created_at->format('d/m/Y H:i'),
                    $delivery->delivered_at ? $delivery->delivered_at->format('d/m/Y H:i') : '-',
                ];
            }
        };

        $filename = 'rapport-livraisons-'.now()->format('Y-m-d-His').'.xlsx';
        return Excel::download($exportCollection, $filename, ExcelWriter::XLSX);
    }

    // ================================
    // PDF Livreurs
    // ================================
    public function exportDeliveryPersonsToPDF($deliveryPersons, Carbon $startDate, Carbon $endDate)
    {
        $data = [
            'title' => 'Rapport de Performance des Livreurs',
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
            'delivery_persons' => $deliveryPersons->map(function($person) use ($startDate,$endDate) {
                $deliveries = $person->deliveries()->whereBetween('created_at', [$startDate, $endDate])->get();
                $successful = $deliveries->where('status','DELIVERED')->count();
                $total = $deliveries->count();
                return [
                    'name' => $person->name,
                    'email' => $person->email,
                    'total_deliveries' => $total,
                    'successful' => $successful,
                    'failed' => $deliveries->where('status','FAILED')->count(),
                    'success_rate' => $total > 0 ? round(($successful/$total)*100,2) : 0,
                ];
            }),
        ];

        $pdf = Pdf::loadView('exports.delivery-persons-pdf', $data)
            ->setPaper('a4','portrait');

        $filename = 'rapport-livreurs-'.now()->format('Y-m-d-His').'.pdf';
        return $pdf->download($filename);
    }

    // ================================
    // Excel Livreurs
    // ================================
    public function exportDeliveryPersonsToExcel($deliveryPersons, Carbon $startDate, Carbon $endDate)
    {
        $exportCollection = new class($deliveryPersons, $startDate, $endDate) implements FromCollection, WithHeadings, WithMapping {
            private $deliveryPersons;
            private $startDate;
            private $endDate;

            public function __construct($deliveryPersons, $startDate, $endDate)
            {
                $this->deliveryPersons = $deliveryPersons;
                $this->startDate = $startDate;
                $this->endDate = $endDate;
            }

            public function collection()
            {
                return $this->deliveryPersons;
            }

            public function headings(): array
            {
                return ['Nom','Email','Total livraisons','Réussies','Échouées','Taux (%)'];
            }

            public function map($person): array
            {
                $deliveries = $person->deliveries()->whereBetween('created_at', [$this->startDate, $this->endDate])->get();
                $successful = $deliveries->where('status','DELIVERED')->count();
                $total = $deliveries->count();
                $failed = $deliveries->where('status','FAILED')->count();
                $successRate = $total > 0 ? round(($successful/$total)*100,2) : 0;

                return [
                    $person->name,
                    $person->email,
                    $total,
                    $successful,
                    $failed,
                    $successRate,
                ];
            }
        };

        $filename = 'rapport-livreurs-'.now()->format('Y-m-d-His').'.xlsx';
        return Excel::download($exportCollection, $filename, ExcelWriter::XLSX);
    }

    // ================================
    // Traduction des statuts
    // ================================
    private function translateStatus(string $status): string
    {
        $translations = [
            'PENDING' => 'En attente',
            'ASSIGNED' => 'Assignée',
            'PICKED_UP' => 'Récupérée',
            'IN_TRANSIT' => 'En transit',
            'DELIVERED' => 'Livrée',
            'FAILED' => 'Échouée',
            'CANCELLED' => 'Annulée',
        ];

        return $translations[$status] ?? $status;
    }
}
