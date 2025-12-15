<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\Client;
use App\Models\DeliveryPerson;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ADMIN AUTH TESTS
    public function test_admin_can_login()
    {
        $this->withoutExceptionHandling();
        $admin = Admin::factory()->create([
            'email' => 'admin@test.com',
            'password' => 'password',
            'role' => 'ADMIN'
        ]);

        $response = $this->postJson('/api/auth/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_admin_can_logout()
    {
        $admin = Admin::factory()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/admin/logout');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'tokenable_type' => Admin::class,
        ]);
    }

    // CLIENT AUTH TESTS
    public function test_client_can_register()
    {
        $response = $this->postJson('/api/auth/client/register', [
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => '123 Test St',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('clients', ['email' => 'client@test.com']);
    }

    public function test_client_can_login()
    {
        $client = Client::factory()->create([
            'email' => 'client@test.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/client/login', [
            'email' => 'client@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    // DELIVERY PERSON AUTH TESTS
    public function test_delivery_person_can_login()
    {
        $deliveryPerson = DeliveryPerson::factory()->create([
            'email' => 'driver@test.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/delivery-person/login', [
            'email' => 'driver@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_delivery_person_can_update_availability()
    {
        $this->withoutExceptionHandling();
        $deliveryPerson = DeliveryPerson::factory()->create(['is_available' => false]);
        $token = $deliveryPerson->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/delivery-person/profile/availability', [
                'is_available' => true,
            ]);

        $response->assertStatus(200);
        $this->assertTrue($deliveryPerson->fresh()->is_available);
    }
}
