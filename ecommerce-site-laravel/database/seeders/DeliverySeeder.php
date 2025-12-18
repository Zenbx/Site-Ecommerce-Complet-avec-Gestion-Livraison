<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\DeliveryPerson;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DeliverySeeder extends Seeder
{
    /**
     * Seed pour créer des livraisons de test
     * 
     * Ce seeder crée des livraisons avec différents statuts et dates
     * pour permettre de tester les rapports et statistiques.
     * 
     * Scénarios couverts :
     * - Livraisons réussies (70%)
     * - Livraisons échouées (10%)
     * - Livraisons en cours (15%)
     * - Livraisons en attente (5%)
     * 
     * Distribution temporelle :
     * - Aujourd'hui : 20%
     * - Cette semaine : 30%
     * - Ce mois : 30%
     * - Mois précédents : 20%
     */
    public function run(): void
    {
        // Vérifier que nous avons des commandes et des livreurs
        $orders = Order::all();
        $deliveryPersons = DeliveryPerson::all();

        if ($orders->isEmpty()) {
            $this->command->error('Aucune commande trouvée. Veuillez d\'abord exécuter le OrderSeeder.');
            return;
        }

        if ($deliveryPersons->isEmpty()) {
            $this->command->error('Aucun livreur trouvé. Veuillez d\'abord exécuter le DeliveryPersonSeeder.');
            return;
        }

        $this->command->info('Création de livraisons de test...');

        // Définir les différentes périodes
        $periods = [
            'today' => [
                'count' => 15,
                'start' => Carbon::today(),
                'end' => Carbon::now(),
            ],
            'this_week' => [
                'count' => 25,
                'start' => Carbon::now()->startOfWeek(),
                'end' => Carbon::yesterday()->endOfDay(),
            ],
            'this_month' => [
                'count' => 30,
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->startOfWeek()->subDay(),
            ],
            'last_months' => [
                'count' => 20,
                'start' => Carbon::now()->subMonths(3),
                'end' => Carbon::now()->startOfMonth()->subDay(),
            ],
        ];

        $totalCreated = 0;

        foreach ($periods as $periodName => $period) {
            $this->command->info("Création de {$period['count']} livraisons pour la période : {$periodName}");

            for ($i = 0; $i < $period['count']; $i++) {
                // Sélectionner une commande aléatoire qui n'a pas encore de livraison
                $order = $orders->random();
                
                // Vérifier si cette commande a déjà une livraison
                if (Delivery::where('order_id', $order->id)->exists()) {
                    continue;
                }

                // Sélectionner un livreur aléatoire
                $deliveryPerson = $deliveryPersons->random();

                // Déterminer le statut selon une distribution réaliste
                $statusDistribution = [
                    'DELIVERED' => 70,  // 70% de livraisons réussies
                    'FAILED' => 10,     // 10% d'échecs
                    'IN_TRANSIT' => 10, // 10% en transit
                    'PICKED_UP' => 5,   // 5% récupérées
                    'ASSIGNED' => 3,    // 3% assignées
                    'PENDING' => 2,     // 2% en attente
                ];

                $status = $this->getRandomStatus($statusDistribution);

                // Générer une date aléatoire dans la période
                $createdAt = Carbon::createFromTimestamp(
                    rand($period['start']->timestamp, $period['end']->timestamp)
                );

                // Créer la livraison
                $delivery = Delivery::create([
                    'order_id' => $order->id,
                    'delivery_person_id' => $status === 'PENDING' ? null : $deliveryPerson->id,
                    'delivery_address' => $order->client->address,
                    'status' => $status,
                    'tracking_code' => $this->generateTrackingCode(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    
                    // QR Code data
                    'qr_token' => Str::random(32),
                    'qr_expires_at' => Carbon::now()->addDays(7),
                    'qr_status' => $status === 'DELIVERED' ? 'scanned' : 'active',
                ]);

                // Définir les timestamps selon le statut
                $this->setDeliveryTimestamps($delivery, $createdAt, $status);

                $totalCreated++;
            }
        }

        $this->command->info("✓ {$totalCreated} livraisons créées avec succès!");
        
        // Afficher les statistiques
        $this->displayStatistics();
    }

    /**
     * Génère un code de suivi unique
     */
    private function generateTrackingCode(): string
    {
        do {
            $code = 'TRK-' . strtoupper(Str::random(8));
        } while (Delivery::where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Sélectionne un statut aléatoire selon une distribution pondérée
     */
    private function getRandomStatus(array $distribution): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'PENDING';
    }

    /**
     * Définit les timestamps de livraison selon le statut
     */
    private function setDeliveryTimestamps(Delivery $delivery, Carbon $createdAt, string $status): void
    {
        switch ($status) {
            case 'DELIVERED':
                // Livraison complète : tous les timestamps sont définis
                $delivery->update([
                    'delivered_at' => $createdAt->copy()->addHours(rand(1, 8)),
                    'qr_scanned_at' => $createdAt->copy()->addHours(rand(1, 8)),
                ]);
                break;

            case 'IN_TRANSIT':
                // En transit : pas encore livré
                $delivery->update([
                    'delivered_at' => null,
                ]);
                break;

            case 'PICKED_UP':
            case 'ASSIGNED':
            case 'PENDING':
            case 'FAILED':
                // Autres statuts : pas de livraison
                $delivery->update([
                    'delivered_at' => null,
                ]);
                break;
        }
    }

    /**
     * Affiche les statistiques des livraisons créées
     */
    private function displayStatistics(): void
    {
        $total = Delivery::count();
        $delivered = Delivery::where('status', 'DELIVERED')->count();
        $failed = Delivery::where('status', 'FAILED')->count();
        $inProgress = Delivery::whereIn('status', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])->count();
        $pending = Delivery::where('status', 'PENDING')->count();

        $this->command->table(
            ['Statut', 'Nombre', 'Pourcentage'],
            [
                ['DELIVERED', $delivered, round(($delivered / $total) * 100, 1) . '%'],
                ['FAILED', $failed, round(($failed / $total) * 100, 1) . '%'],
                ['IN PROGRESS', $inProgress, round(($inProgress / $total) * 100, 1) . '%'],
                ['PENDING', $pending, round(($pending / $total) * 100, 1) . '%'],
                ['TOTAL', $total, '100%'],
            ]
        );

        // Statistiques par période
        $this->command->info("\nDistribution temporelle:");
        $this->command->table(
            ['Période', 'Nombre'],
            [
                ['Aujourd\'hui', Delivery::whereDate('created_at', Carbon::today())->count()],
                ['Cette semaine', Delivery::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()])->count()],
                ['Ce mois', Delivery::whereMonth('created_at', Carbon::now()->month)->count()],
                ['Total', $total],
            ]
        );
    }
}