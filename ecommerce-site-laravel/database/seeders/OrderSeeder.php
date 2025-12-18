<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Client;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartLine;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Seed pour créer des commandes de test
     * 
     * Ce seeder crée des commandes complètes avec leurs lignes de commande,
     * en utilisant différents statuts et périodes pour simuler un vrai système.
     * 
     * Scénarios couverts :
     * - Commandes en attente (PENDING) : 15%
     * - Commandes confirmées (CONFIRMED) : 10%
     * - Commandes en traitement (PROCESSING) : 20%
     * - Commandes expédiées (SHIPPED) : 25%
     * - Commandes livrées (DELIVERED) : 25%
     * - Commandes annulées (CANCELLED) : 5%
     * 
     * Distribution temporelle :
     * - Aujourd'hui : 15%
     * - Cette semaine : 30%
     * - Ce mois : 35%
     * - Mois précédents : 20%
     */
    public function run(): void
    {
        // Vérifier que nous avons des clients et des produits
        $clients = Client::all();
        $products = Product::where('is_active', true)->where('quantity', '>', 0)->get();

        if ($clients->isEmpty()) {
            $this->command->error('Aucun client trouvé. Veuillez d\'abord exécuter le ClientSeeder.');
            return;
        }

        if ($products->isEmpty()) {
            $this->command->error('Aucun produit trouvé. Veuillez d\'abord exécuter le ProductSeeder.');
            return;
        }

        $this->command->info('Création de commandes de test...');

        // Définir les différentes périodes
        $periods = [
            'today' => [
                'count' => 10,
                'start' => Carbon::today(),
                'end' => Carbon::now(),
            ],
            'this_week' => [
                'count' => 20,
                'start' => Carbon::now()->startOfWeek(),
                'end' => Carbon::yesterday()->endOfDay(),
            ],
            'this_month' => [
                'count' => 25,
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->startOfWeek()->subDay(),
            ],
            'last_months' => [
                'count' => 15,
                'start' => Carbon::now()->subMonths(3),
                'end' => Carbon::now()->startOfMonth()->subDay(),
            ],
        ];

        $totalCreated = 0;

        foreach ($periods as $periodName => $period) {
            $this->command->info("Création de {$period['count']} commandes pour la période : {$periodName}");

            for ($i = 0; $i < $period['count']; $i++) {
                // Sélectionner un client aléatoire
                $client = $clients->random();

                // Générer une date aléatoire dans la période
                $createdAt = Carbon::createFromTimestamp(
                    rand($period['start']->timestamp, $period['end']->timestamp)
                );

                // Déterminer le statut selon une distribution réaliste
                $orderStatus = $this->getRandomOrderStatus();
                $paymentStatus = $this->getPaymentStatus($orderStatus);

                // Créer la commande
                $order = $this->createOrder($client, $createdAt, $orderStatus, $paymentStatus);

                // Ajouter des lignes de commande (1 à 5 produits)
                $this->createOrderLines($order, $products);

                // Calculer et mettre à jour le montant total
                $this->updateOrderTotal($order);

                $totalCreated++;
            }
        }

        $this->command->info("✓ {$totalCreated} commandes créées avec succès!");
        
        // Afficher les statistiques
        $this->displayStatistics();
    }

    /**
     * Crée une commande
     */
    private function createOrder(Client $client, Carbon $createdAt, string $orderStatus, string $paymentStatus): Order
    {
        return Order::create([
            'client_id' => $client->id,
            'total_amount' => 0, // Sera calculé après l'ajout des lignes
            'delivery_fee' => $this->getDeliveryFee(),
            'delivery_address' => $client->address,
            'status' => $orderStatus,
            'payment_status' => $paymentStatus,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * Crée les lignes de commande
     */
    private function createOrderLines(Order $order, $products): void
    {
        // Nombre aléatoire de produits (1 à 5)
        $numberOfProducts = rand(1, 5);
        
        // Sélectionner des produits aléatoires sans doublon
        $selectedProducts = $products->random(min($numberOfProducts, $products->count()));

        foreach ($selectedProducts as $product) {
            // Quantité aléatoire (1 à 3)
            $quantity = rand(1, min(3, $product->quantity));

            OrderLine::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'unit_price' => $product->price,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * Calcule et met à jour le montant total de la commande
     */
    private function updateOrderTotal(Order $order): void
    {
        $subtotal = $order->orderLines->sum(function($line) {
            return $line->unit_price * $line->quantity;
        });

        $order->update([
            'total_amount' => $subtotal + $order->delivery_fee,
        ]);
    }

    /**
     * Sélectionne un statut de commande aléatoire selon une distribution pondérée
     */
    private function getRandomOrderStatus(): string
    {
        $distribution = [
            'PENDING' => 15,
            'CONFIRMED' => 10,
            'PROCESSING' => 20,
            'SHIPPED' => 25,
            'DELIVERED' => 25,
            'CANCELLED' => 5,
        ];

        return $this->getWeightedRandom($distribution);
    }

    /**
     * Détermine le statut de paiement en fonction du statut de commande
     */
    private function getPaymentStatus(string $orderStatus): string
    {
        switch ($orderStatus) {
            case 'PENDING':
                // 70% pending, 30% completed (paiement à la livraison prévu)
                return rand(1, 100) <= 70 ? 'PENDING' : 'COMPLETED';
            
            case 'CONFIRMED':
            case 'PROCESSING':
            case 'SHIPPED':
                // 90% completed, 10% pending
                return rand(1, 100) <= 90 ? 'COMPLETED' : 'PENDING';
            
            case 'DELIVERED':
                // 100% completed
                return 'COMPLETED';
            
            case 'CANCELLED':
                // 50% pending, 30% failed, 20% refunded
                $rand = rand(1, 100);
                if ($rand <= 50) return 'PENDING';
                if ($rand <= 80) return 'FAILED';
                return 'REFUNDED';
            
            default:
                return 'PENDING';
        }
    }

    /**
     * Génère des frais de livraison aléatoires
     */
    private function getDeliveryFee(): int
    {
        $fees = [
            0,      // Livraison gratuite (30%)
            1000,   // 1000 FCFA (30%)
            2000,   // 2000 FCFA (25%)
            3000,   // 3000 FCFA (10%)
            5000,   // 5000 FCFA (5%)
        ];

        $distribution = [30, 30, 25, 10, 5];
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $index => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $fees[$index];
            }
        }

        return 0;
    }

    /**
     * Sélection pondérée aléatoire
     */
    private function getWeightedRandom(array $distribution): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $value => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return array_key_first($distribution);
    }

    /**
     * Affiche les statistiques des commandes créées
     */
    private function displayStatistics(): void
    {
        $total = Order::count();
        $pending = Order::where('status', 'PENDING')->count();
        $confirmed = Order::where('status', 'CONFIRMED')->count();
        $processing = Order::where('status', 'PROCESSING')->count();
        $shipped = Order::where('status', 'SHIPPED')->count();
        $delivered = Order::where('status', 'DELIVERED')->count();
        $cancelled = Order::where('status', 'CANCELLED')->count();

        $this->command->info("\n📊 Statistiques des commandes créées:\n");

        $this->command->table(
            ['Statut', 'Nombre', 'Pourcentage'],
            [
                ['PENDING', $pending, round(($pending / $total) * 100, 1) . '%'],
                ['CONFIRMED', $confirmed, round(($confirmed / $total) * 100, 1) . '%'],
                ['PROCESSING', $processing, round(($processing / $total) * 100, 1) . '%'],
                ['SHIPPED', $shipped, round(($shipped / $total) * 100, 1) . '%'],
                ['DELIVERED', $delivered, round(($delivered / $total) * 100, 1) . '%'],
                ['CANCELLED', $cancelled, round(($cancelled / $total) * 100, 1) . '%'],
                ['TOTAL', $total, '100%'],
            ]
        );

        // Statistiques de paiement
        $this->command->info("\n💳 Statuts de paiement:\n");
        
        $paymentPending = Order::where('payment_status', 'PENDING')->count();
        $paymentCompleted = Order::where('payment_status', 'COMPLETED')->count();
        $paymentFailed = Order::where('payment_status', 'FAILED')->count();
        $paymentRefunded = Order::where('payment_status', 'REFUNDED')->count();

        $this->command->table(
            ['Statut Paiement', 'Nombre', 'Pourcentage'],
            [
                ['PENDING', $paymentPending, round(($paymentPending / $total) * 100, 1) . '%'],
                ['COMPLETED', $paymentCompleted, round(($paymentCompleted / $total) * 100, 1) . '%'],
                ['FAILED', $paymentFailed, round(($paymentFailed / $total) * 100, 1) . '%'],
                ['REFUNDED', $paymentRefunded, round(($paymentRefunded / $total) * 100, 1) . '%'],
            ]
        );

        // Statistiques par période
        $this->command->info("\n📅 Distribution temporelle:\n");
        
        $today = Order::whereDate('created_at', Carbon::today())->count();
        $thisWeek = Order::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()])->count();
        $thisMonth = Order::whereMonth('created_at', Carbon::now()->month)->count();

        $this->command->table(
            ['Période', 'Nombre'],
            [
                ['Aujourd\'hui', $today],
                ['Cette semaine', $thisWeek],
                ['Ce mois', $thisMonth],
                ['Total', $total],
            ]
        );

        // Statistiques financières
        $this->command->info("\n💰 Statistiques financières:\n");
        
        $totalRevenue = Order::whereIn('status', ['DELIVERED'])->sum('total_amount');
        $pendingRevenue = Order::whereIn('status', ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED'])->sum('total_amount');
        $averageOrderValue = Order::avg('total_amount');

        $this->command->table(
            ['Métrique', 'Montant'],
            [
                ['Revenu total (livré)', number_format($totalRevenue, 0, ',', ' ') . ' FCFA'],
                ['Revenu en attente', number_format($pendingRevenue, 0, ',', ' ') . ' FCFA'],
                ['Valeur moyenne commande', number_format($averageOrderValue, 0, ',', ' ') . ' FCFA'],
            ]
        );
    }
}