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

        DeliveryPerson::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/delivery-persons');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'is_available'
                ]
            ]
        ]);
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
                'phone' => '+237600000000',
                'vehicle_type' => 'MOTO',
                'license_number' => 'ABC123'
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

    public function test_admin_can_view_delivery_person_deliveries()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $deliveryPerson = DeliveryPerson::factory()->create();
        
        // Create deliveries for this person
        Delivery::factory()->count(3)->create([
            'delivery_person_id' => $deliveryPerson->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/delivery-persons/{$deliveryPerson->id}/deliveries");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'order_id',
                    'status',
                    'tracking_code'
                ]
            ]
        ]);
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
