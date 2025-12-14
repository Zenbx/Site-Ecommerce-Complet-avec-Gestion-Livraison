<?php

// ============================================
// TESTS POUR L'AUTHENTIFICATION
// tests/Feature/AuthTest.php
// ============================================

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Models\Admin;
use App\Models\DeliveryPerson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** 
    *  @test 
    */
    public function client_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => '123 Main St, Douala',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ]);

        $this->assertDatabaseHas('client', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /** @test */
    public function client_cannot_register_with_existing_email()
    {
        Client::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
            'address' => '123 Main St',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function client_cannot_register_with_invalid_password()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123', // Trop court
            'password_confirmation' => '123',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function client_can_login_with_valid_credentials()
    {
        $client = Client::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'address' => '123 Main St',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
            'user_type' => 'client',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Problème signalé'
            ]);

        $this->assertDatabaseHas('delivery', [
            'id' => $this->delivery->id,
            'status' => 'FAILED',
        ]);
    }

    /** @test */
    public function delivery_person_cannot_view_other_delivery_person_deliveries()
    {
        $otherDeliveryPerson = DeliveryPerson::create([
            'name' => 'Jacques Momo',
            'email' => 'jacques@delivery.cm',
            'password' => Hash::make('password123'),
            'id_card_number' => 'DL-002',
            'address' => 'Douala',
            'is_available' => true,
        ]);

        $otherDelivery = Delivery::create([
            'order_id' => $this->order->id,
            'delivery_person_id' => $otherDeliveryPerson->id,
            'delivery_address' => $this->client->address,
            'status' => 'ASSIGNED',
            'tracking_code' => 'TRK-OTHER-001',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/delivery-person/deliveries/' . $otherDelivery->id);

        $response->assertStatus(404);
    }

    /** @test */
    public function delivery_person_can_update_availability()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson('/api/delivery-person/availability', [
                'is_available' => false,
                'reason' => 'Pause déjeuner'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Disponibilité mise à jour'
            ]);

        $this->assertDatabaseHas('delivery_person', [
            'id' => $this->deliveryPerson->id,
            'is_available' => false,
        ]);
    }
}