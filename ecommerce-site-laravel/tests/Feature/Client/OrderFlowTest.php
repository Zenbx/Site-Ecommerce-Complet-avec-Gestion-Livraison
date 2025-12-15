<?php

namespace Tests\Feature\Client;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Product;
use App\Models\Category;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_place_order()
    {
        $client = Client::factory()->create();
            $token = $client->createToken('test-token')->plainTextToken;
            
            $category = Category::factory()->create();
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'price' => 100,
                'quantity' => 10
            ]);

            // 1. Add item to cart
            $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/client/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => 2
                ])
                ->assertStatus(200);

            // 2. Place Order
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/client/orders', [
                    'delivery_address' => '123 Order St',
                    'delivery_fee' => 15.00,
                    'payment_method' => 'CASH'
                ]);

            $response->assertStatus(201)
                ->assertJsonPath('success', true);
                
            $orderId = $response->json('data.id');
            
            // 3. Verify Order Details
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'client_id' => $client->id,
            'total_amount' => '215.00', 
        ]);
    }
}
