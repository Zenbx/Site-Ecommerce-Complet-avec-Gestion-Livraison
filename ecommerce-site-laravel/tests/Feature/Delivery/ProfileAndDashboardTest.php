<?php

namespace Tests\Feature\Delivery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\DeliveryPerson;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

class ProfileAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ========== Profile Tests ==========

    public function test_delivery_person_can_view_profile()
    {
        $deliveryPerson = DeliveryPerson::factory()->create([
            'name' => 'John Driver',
            'email' => 'driver@example.com'
        ]);
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/delivery-person/profile');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'John Driver',
                'email' => 'driver@example.com'
            ]
        ]);
    }

    public function test_delivery_person_can_update_profile()
    {
        $deliveryPerson = DeliveryPerson::factory()->create();
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/delivery-person/profile', [
                'name' => 'Updated Name',
                'phone' => '+237600000000'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profil mis à jour avec succès'
        ]);

        $this->assertDatabaseHas('delivery_persons', [
            'id' => $deliveryPerson->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_delivery_person_can_change_password()
    {
        $deliveryPerson = DeliveryPerson::factory()->create([
            'password' => 'OldPass123!'
        ]);
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/delivery-person/profile/password', [
                'current_password' => 'OldPass123!',
                'new_password' => 'NewPass123!',
                'new_password_confirmation' => 'NewPass123!'
            ]);

        $response->assertStatus(200);

        $deliveryPerson->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $deliveryPerson->password));
    }

    public function test_delivery_person_can_toggle_availability()
    {
        $deliveryPerson = DeliveryPerson::factory()->create([
            'is_available' => true
        ]);
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/delivery-person/profile/availability', [
                'is_available' => false
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('delivery_persons', [
            'id' => $deliveryPerson->id,
            'is_available' => false
        ]);
    }

    /**
     * Teste l'historique des livraisons basé sur ProfileController::deliveryHistory.
     * Logique : 
     * - Route : /api/delivery-person/profile/deliveries
     * - Structure de pagination spécifique : 'data' => ['deliveries' => [...], 'pagination' => [...]]
     */
    public function test_delivery_person_can_view_delivery_history()
    {
        // 1. Setup
        $deliveryPerson = DeliveryPerson::factory()->create();
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        // Créer les dépendances Order et Client pour que la transformation fonctionne
        \App\Models\Client::factory()->create(['name' => 'Customer A']);
        \App\Models\Order::factory()->create([
            'client_id' => \App\Models\Client::first()->id,
            'total_amount' => 50000,
        ]);

        // 2. Data : Créer 20 livraisons (pour tester la pagination par défaut de 15)
        Delivery::factory()->count(20)->create([
            'delivery_person_id' => $deliveryPerson->id,
            'status' => 'DELIVERED',
            'order_id' => \App\Models\Order::first()->id, // Lier à la commande
        ]);
        
        // 3. Action
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/delivery-person/profile/deliveries'); // Route corrigée

        // 4. Vérifications
        $response->assertStatus(200);
        
        // CORRECTION MAJEURE: Assurer la structure JSON exacte retournée par ProfileController::deliveryHistory
        $response->assertJsonStructure([
            'success',
            'data' => [
                'deliveries' => [
                    '*' => [ 
                        // Champs définis dans la transformation manuelle du controller
                        'id',
                        'tracking_code',
                        'order_number', // Nécessite la fonction helper generate_order_number
                        'customer_name', // Nécessite la relation order.client
                        'delivery_address',
                        'status',
                        'created_at',
                        'delivered_at',
                        'delivery_time',
                        'amount', // Nécessite la fonction helper format_currency
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'total',
                    'per_page',
                    'last_page',
                ],
            ]
        ]);
        
        // Assertions des données paginées
        $response->assertJsonPath('data.pagination.total', 20);
        $response->assertJsonCount(15, 'data.deliveries'); // 15 est la taille par défaut
        
        // Vérification de quelques champs de la première livraison
        $response->assertJsonPath('data.deliveries.0.status', 'DELIVERED');
        $response->assertJsonPath('data.deliveries.0.amount', '50,000.00 FCFA'); // Hypothèse de format_currency
    }
    // ========== Dashboard Tests ==========

   /**
     * Teste le résumé quotidien basé sur DashboardController::dailySummary
     * Logique : 
     * - Filtre 'created_at' = today()
     * - Pending = ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT']
     * - Earnings = 500 par livraison 'DELIVERED'
     */
    public function test_delivery_person_can_view_daily_summary()
    {
        // 1. Figer le temps pour garantir que "today()" est cohérent
        Carbon::setTestNow('2024-05-20 12:00:00');

        $deliveryPerson = DeliveryPerson::factory()->create();
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        // --- DONNÉES DE TEST (SCÉNARIO) ---

        // Cas 1: Livraison terminée AUJOURD'HUI
        // Doit compter dans: total, completed, earnings (+500)
        Delivery::factory()->create([
            'delivery_person_id' => $deliveryPerson->id,
            'status' => 'DELIVERED',
            'created_at' => now(), 
        ]);

        // Cas 2: Livraison en cours (PICKED_UP) AUJOURD'HUI
        // Doit compter dans: total, pending
        // Note: Le controller inclut 'PICKED_UP' dans les pending
        Delivery::factory()->create([
            'delivery_person_id' => $deliveryPerson->id,
            'status' => 'PICKED_UP',
            'created_at' => now(),
        ]);

        // Cas 3: Livraison échouée AUJOURD'HUI
        // Doit compter dans: total, failed
        Delivery::factory()->create([
            'delivery_person_id' => $deliveryPerson->id,
            'status' => 'FAILED',
            'created_at' => now(),
        ]);

        // Cas 4: Livraison terminée HIER
        // NE DOIT PAS être comptée (filtre whereDate)
        Delivery::factory()->create([
            'delivery_person_id' => $deliveryPerson->id,
            'status' => 'DELIVERED',
            'created_at' => now()->subDay(),
        ]);

        // --- ACTION ---
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/delivery-person/dashboard/daily-summary');

        // --- VÉRIFICATIONS ---

        $response->assertStatus(200);

        // Vérification de la structure exacte retournée par generate_api_response
        // On suppose que generate_api_response met les données dans 'data' et 'success' => true
        $response->assertJson([
            'success' => true,
            'data' => [
                'date' => '2024-05-20', // Doit correspondre à notre date figée
                'deliveries' => [
                    'total' => 3,     // 1 DELIVERED + 1 PICKED_UP + 1 FAILED (Celle d'hier est ignorée)
                    'completed' => 1, // 1 DELIVERED aujourd'hui
                    'pending' => 1,   // 1 PICKED_UP aujourd'hui
                    'failed' => 1,    // 1 FAILED aujourd'hui
                ],
                'is_available' => $deliveryPerson->is_available,
            ]
        ]);

        // Vérification des gains
        // Le controller calcule: $today->where('status', 'DELIVERED') * 500
        // Donc 1 * 500 = 500
        // On teste earnings_raw pour éviter les problèmes de formatage de devise (string)
        $response->assertJsonPath('data.earnings_raw', 500);

        // Réinitialiser le temps
        Carbon::setTestNow();
    }

    /**
     * Teste l'aperçu général basé sur DashboardController::overview
     * Ce test vérifie surtout que le endpoint répond et délègue au StatisticsService
     */
    public function test_delivery_person_can_view_overview_stats()
    {
        $deliveryPerson = DeliveryPerson::factory()->create();
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        // On appelle l'endpoint avec une période valide
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/delivery-person/dashboard/overview?period=week');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.period', 'week');
        
        // On vérifie simplement que la clé statistics est présente
        // car le contenu exact dépend de StatisticsService
        $response->assertJsonStructure([
            'success',
            'data' => [
                'period',
                'statistics',
                'generated_at'
            ]
        ]);
    }

    public function test_overview_rejects_invalid_period()
    {
        $deliveryPerson = DeliveryPerson::factory()->create();
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        // Période invalide
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/delivery-person/dashboard/overview?period=invalid_period');

        // Le controller retourne un code 400 pour période invalide
        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Période invalide');
    }
    // Commented out - statistics endpoint may not exist
     public function test_delivery_person_can_view_statistics()
     {
         $deliveryPerson = DeliveryPerson::factory()->create();
         $token = $deliveryPerson->createToken('test-token')->plainTextToken;

         // Create various deliveries
        Delivery::factory()->count(5)->create([
             'delivery_person_id' => $deliveryPerson->id,
             'status' => 'DELIVERED'
         ]);

         $response = $this->withHeader('Authorization', 'Bearer ' . $token)
             ->getJson('/api/delivery-person/profile/statistics');

         $response->assertStatus(200);
         $response->assertJsonStructure([
             'success',
             'data' => [
                 'total_deliveries',
                 'completed_deliveries',
                 'success_rate'
             ]
         ]);
     }
}
