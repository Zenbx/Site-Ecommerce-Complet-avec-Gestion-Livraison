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
        // 1. Ajouter un prix pour pouvoir vérifier les totaux
        $productPrice = 100.00;
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 50,
            'price' => $productPrice, // Définir un prix
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
        
        // 2. Correction de la structure JSON pour correspondre au contrôleur
        $response->assertJsonStructure([
            'success',
            'data' => [
                'cart' => [ // Correction: la ressource Cart est imbriquée sous la clé 'cart'
                    'id',
                    'status',
                    // Les autres champs de la ressource (items, summary) 
                    // ne sont pas chargés ici car ils sont au niveau parent dans la réponse du contrôleur (L. 101-125)
                ],
                'items' => [ // Correct car retourné directement sous 'data'
                    '*' => [
                        'id',
                        'product',
                        'quantity',
                        'unit_price',
                        'subtotal',
                    ]
                ],
                'summary' => [ // Correct car retourné directement sous 'data'
                    'items_count',
                    'subtotal',
                    'total'
                ]
            ]
        ]);
        
        // 3. Assertion de données pour valider les calculs
        $response->assertJsonPath('data.summary.items_count', 1);
        $response->assertJsonPath('data.summary.subtotal', number_format($productPrice * 1, 2, '.', ''));
        $response->assertJsonPath('data.items.0.quantity', 1);
    }


 public function test_client_can_update_cart_item_quantity()
    {
        $productPrice = 100.00;
        $initialQuantity = 2;
        $newQuantity = 5;

        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 50,
            'price' => $productPrice,
        ]);

        // 1. Ajouter l'article
        $addResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product->id,
                'quantity' => $initialQuantity
            ]);

        // CORRECTION CRUCIALE : Récupérer l'ID de la ligne de panier (CartLine ID)
        // La réponse de addItem retourne une CartResource qui contient une liste 'items'
        $cartLineId = $addResponse->json('data.items.0.id');

        $this->assertNotNull($cartLineId, "L'ID de la ligne de panier n'a pas été trouvé dans la réponse.");

        // 2. Mettre à jour la quantité (PUT sur l'ID de la LIGNE, pas du produit)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/client/cart/items/{$cartLineId}", [ // Note: Vérifiez si votre route est PUT ou PATCH
                'quantity' => $newQuantity 
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        // Vérifier les données mises à jour
        $response->assertJsonPath('data.summary.items_count', $newQuantity);
        
        // Vérifier en BDD avec l'ID de la ligne
        $this->assertDatabaseHas('cart_lines', [
            'id' => $cartLineId,
            'quantity' => $newQuantity
        ]);
    }

    public function test_client_can_remove_item_from_cart()
    {
        $productPrice = 100.00;
        
        $client = Client::factory()->create();
        $token = $client->createToken('test-token')->plainTextToken;
        
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'quantity' => 50,
            'price' => $productPrice,
        ]);

        // 1. Ajouter l'article
        $addResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/client/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

        // CORRECTION CRUCIALE : Récupérer l'ID de la ligne de panier
        $cartLineId = $addResponse->json('data.items.0.id');
        
        $this->assertNotNull($cartLineId, "Impossible de récupérer l'ID de la ligne de panier pour la suppression.");

        // 2. Supprimer l'article (DELETE sur l'ID de la LIGNE)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/client/cart/items/{$cartLineId}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Article retiré du panier');

        // 3. Vérifier que la ligne a disparu de la BDD
        $this->assertDatabaseMissing('cart_lines', [
            'id' => $cartLineId
        ]);
        
        // 4. Vérifier que le panier est vide dans le JSON
        $response->assertJsonPath('data.summary.items_count', 0);
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
