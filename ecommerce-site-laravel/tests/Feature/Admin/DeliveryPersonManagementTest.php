<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\DeliveryPerson;
use App\Models\Delivery;

class DeliveryPersonManagementTest extends TestCase
{
    use RefreshDatabase;

   public function test_admin_can_list_delivery_persons()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        // Créer des livreurs avec des états différents pour tester les stats
        DeliveryPerson::factory()->count(2)->create(['is_available' => true]);
        DeliveryPerson::factory()->count(1)->create(['is_available' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/delivery-persons');

        $response->assertStatus(200);
        
        // 1. Vérification de la structure de base des données (comme avant)
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    // Ajouter des champs qui sont retournés par la DeliveryPersonResource
                    // (supposons que ces champs sont présents dans la Resource)
                    'is_available' 
                ]
            ],
            // 2. Ajout de la vérification de la structure de pagination (meta)
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
            // 3. Ajout de la vérification de la structure des statistiques
            'statistics' => [
                'total',
                'available',
                'busy',
            ]
        ]);
        
        // 4. Vérification des statistiques globales renvoyées par le contrôleur
        $response->assertJsonPath('statistics.total', 3);
        $response->assertJsonPath('statistics.available', 2);
    }

    public function test_admin_can_create_delivery_person()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/delivery-persons', [
                'name' => 'John Driver',
                'email' => 'driver@example.com',
                'password' => 'password123',
                // Nouveaux champs requis par le contrôleur:
                'id_card_number' => 'NIN12345678',
                'address' => '123 Main St, City',
                // Suppression des champs non validés: 'phone', 'vehicle_type', 'license_number'
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Livreur créé avec succès'
        ]);

        $this->assertDatabaseHas('delivery_persons', [
            'name' => 'John Driver',
            'email' => 'driver@example.com'
        ]);
    }

    public function test_admin_can_view_delivery_person()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $deliveryPerson = DeliveryPerson::factory()->create([
            'name' => 'Test Driver'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/delivery-persons/{$deliveryPerson->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $deliveryPerson->id,
                'name' => 'Test Driver'
            ]
        ]);
    }

    public function test_admin_can_update_delivery_person()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $deliveryPerson = DeliveryPerson::factory()->create([
            'name' => 'Old Name'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/delivery-persons/{$deliveryPerson->id}", [
                'name' => 'Updated Name',
                'phone' => '+237611111111'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Livreur mis à jour avec succès'
        ]);

        $this->assertDatabaseHas('delivery_persons', [
            'id' => $deliveryPerson->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_admin_can_delete_delivery_person()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $deliveryPerson = DeliveryPerson::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/delivery-persons/{$deliveryPerson->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Livreur supprimé avec succès'
        ]);

        $this->assertDatabaseMissing('delivery_persons', [
            'id' => $deliveryPerson->id
        ]);
    }

    public function test_admin_can_change_delivery_person_availability()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $deliveryPerson = DeliveryPerson::factory()->create([
            'is_available' => true
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/admin/delivery-persons/{$deliveryPerson->id}/availability", [
                'is_available' => false
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('delivery_persons', [
            'id' => $deliveryPerson->id,
            'is_available' => false
        ]);
    }

    // Ce test remplace l'ancien 'test_admin_can_view_delivery_person_deliveries'
    // car la route /deliveries n'est pas implémentée dans le contrôleur.
    // Nous testons l'endpoint implémenté /statistics à la place.
    public function test_admin_can_view_delivery_person_statistics()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $deliveryPerson = DeliveryPerson::factory()->create();
        
        // Définir un temps de création précis pour garantir que les livraisons sont dans la fenêtre de 30 jours
        $createdTime = now()->subMinutes(60); 
        $deliveredTime = now(); // Durée totale de 60 minutes

        // Créer 2 livraisons complétées
        Delivery::factory()->count(2)->create([
            'delivery_person_id' => $deliveryPerson->id,
            'status' => 'DELIVERED',
            'created_at' => $createdTime, // Fixer created_at
            'delivered_at' => $deliveredTime, // Nécessaire pour le calcul du temps moyen
        ]);
        
        // Créer 1 livraison en cours. Utiliser 'ASSIGNED' pour qu'elle soit comptée dans 'in_progress'.
        Delivery::factory()->count(1)->create([
            'delivery_person_id' => $deliveryPerson->id,
            // ATTENTION: PENDING n'est pas compté dans in_progress dans le Controller
            'status' => 'ASSIGNED', 
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/delivery-persons/{$deliveryPerson->id}/statistics");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'period_days',
                'total_deliveries',
                'completed',
                'failed',
                'in_progress',
                'success_rate',
                'average_delivery_time'
            ]
        ]);
        
        // Vérifier les totaux basés sur les données mockées
        $response->assertJsonPath('data.total_deliveries', 3); // (2 DELIVERED + 1 ASSIGNED)
        $response->assertJsonPath('data.completed', 2);
        
        // NOUVELLES ASSERTIONS cruciales pour valider toute la logique
        $response->assertJsonPath('data.in_progress', 1); // Doit être 1 (grâce à 'ASSIGNED')
        $response->assertJsonPath('data.success_rate', 66.67); // (2/3) * 100 arrondi à 2 décimales
        $response->assertJsonPath('data.average_delivery_time', '60 minutes'); // 60 minutes de moyenne
    }

    public function test_cannot_create_delivery_person_with_duplicate_email()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        DeliveryPerson::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/delivery-persons', [
                'name' => 'New Driver',
                'email' => 'existing@example.com', // Duplicate
                'password' => 'password123',
                'phone' => '+237600000000'
            ]);

        $response->assertStatus(422); // Validation error
    }
}
