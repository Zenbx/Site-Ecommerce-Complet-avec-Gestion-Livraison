<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\DeliveryPerson;

class ManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_products()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();

        // 1. Create Product
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/products', [
                'name' => 'New Product',
                'description' => 'Description',
                'price' => 50.00,
                'quantity' => 100,
                'serial_id' => 'PROD-123456',
                'category_id' => $category->id,
                'is_active' => true
            ]);

        $response->assertStatus(201);
        $productId = $response->json('data.id');

        // 2. Update Product Stock
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/admin/products/{$productId}/stock", [
                'quantity' => 50,
                'operation' => 'add'
            ])
            ->assertStatus(200);
            
        $this->assertDatabaseHas('products', ['id' => $productId, 'quantity' => 150]);
    }

    public function test_admin_can_assign_delivery()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;
        
        $order = Order::factory()->create(['status' => 'CONFIRMED']);
        $deliveryPerson = DeliveryPerson::factory()->create(['is_available' => true]);

        // Assign Delivery
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/orders/{$order->id}/assign-delivery", [
                'delivery_person_id' => $deliveryPerson->id,
                'delivery_address' => $order->delivery_address
            ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('deliveries', [
            'order_id' => $order->id,
            'delivery_person_id' => $deliveryPerson->id,
            'status' => 'ASSIGNED'
        ]);
    }
}
