<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_categories()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        Category::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/categories');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description'
                ]
            ]
        ]);
    }

    public function test_admin_can_create_category()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/categories', [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic products'
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Catégorie créée avec succès'
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);
    }

    public function test_admin_can_view_single_category()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $category = Category::factory()->create([
            'name' => 'Test Category'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'name' => 'Test Category'
            ]
        ]);
    }

   public function test_admin_can_update_category()
{
    $admin = Admin::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $category = Category::factory()->create([
        'name' => 'Old Name',
        'description' => 'Old description'
    ]);
    
    $newName = 'Updated Name Final';
    $newDescription = 'Updated description final';

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson("/api/admin/categories/{$category->id}", [
            'name' => $newName,
            'description' => $newDescription
        ]);

    $response->assertStatus(200);
    
    // CORRECTION MAJEURE: Alignement du message et vérification de la clé 'data'
    $response->assertJson([
        'success' => true,
        'message' => 'Catégorie mise à jour', // <-- Message aligné sur le contrôleur
        'data' => [ // <-- Vérification de l'objet Category retourné
            'id' => $category->id,
            'name' => $newName,
            'description' => $newDescription,
            // Les autres champs (slug, is_active, parent_id, etc.) sont aussi dans 'data'
        ]
    ]);

    // Vérification de la base de données
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => $newName,
        'description' => $newDescription // On vérifie aussi la description
    ]);
}

    public function test_admin_can_delete_category()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès'
        ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id
        ]);
    }

    public function test_cannot_create_category_with_duplicate_slug()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/categories', [
                'name' => 'Electronics', // Duplicate name (which creates duplicate slug)
                'description' => 'Test'
            ]);

        $response->assertStatus(422); // Validation error
    }

    public function test_category_name_is_required()
    {
        $admin = Admin::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/categories', [
                'slug' => 'test-slug',
                'description' => 'Test'
                // Missing name
            ]);

        $response->assertStatus(422); // Validation error
    }
}
