<?php

namespace Tests\Feature\Client;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Product;
use App\Models\Category;
use App\Models\Cart;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_add_product_to_cart()
    {
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 50,
            'price' => 100.00
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'status',
                'items',
                'summary'
            ]
        ]);

        $this->assertDatabaseHas('cart_lines', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    public function test_client_can_view_cart()
    {
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 50
        ]);

        // Add product to cart first
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1
            ]);

        // View cart
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/client/cart');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'status',
                'items',
                'summary' => [
                    'items_count',
                    'subtotal',
                    'total'
                ]
            ]
        ]);
    }

    public function test_client_can_update_cart_item_quantity()
    {
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 50
        ]);

        // Add product to cart
        $addResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

        $cartLineId = $addResponse->json('data.cart.cart_lines.0.id');

        // Update quantity
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/client/cart/items/{$cartLineId}", [
                'quantity' => 5
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('cart_lines', [
            'id' => $cartLineId,
            'quantity' => 5
        ]);
    }

    public function test_client_can_remove_item_from_cart()
    {
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 50
        ]);

        // Add product to cart
        $addResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

        $cartLineId = $addResponse->json('data.cart.cart_lines.0.id');

        // Remove item
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/client/cart/items/{$cartLineId}");

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('cart_lines', [
            'id' => $cartLineId
        ]);
    }

    public function test_client_can_clear_entire_cart()
    {
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id, 'quantity' => 50]);
        $product2 = Product::factory()->create(['category_id' => $category->id, 'quantity' => 50]);

        // Add products to cart
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product1->id,
                'quantity' => 2
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product2->id,
                'quantity' => 1
            ]);

        // Clear cart
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/client/cart');

        $response->assertStatus(200);
        
        // Verify cart is empty
        $cart = Cart::where('client_id', $client->id)->first();
        $this->assertEquals(0, $cart->cartLines()->count());
    }

    public function test_cannot_add_product_with_insufficient_stock()
    {
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 5 // Only 5 in stock
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product->id,
                'quantity' => 10 // Trying to add 10
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);
    }
}
