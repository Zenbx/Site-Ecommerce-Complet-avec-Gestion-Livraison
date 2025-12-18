<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Admin;
use App\Models\Client;
use App\Models\DeliveryPerson;
use App\Models\Product;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un admin
        Admin::create([
            'name' => 'Admin Principal',
            'email' => 'admin@ecommerce.cm',
            'password' => 'admin123',
            'role' => 'ADMIN'
        ]);

        // Créer des clients de test
        Client::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'address' => '123 Main St, Douala, Cameroun'
        ]);

        Client::create([
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'password' => 'password123',
            'address' => '456 Avenue de la Liberté, Douala, Cameroun'
        ]);

        // Créer des livreurs
        DeliveryPerson::create([
            'name' => 'Pierre Livreur',
            'email' => 'pierre@delivery.cm',
            'password' => 'delivery123',
            'id_card_number' => 'CNI-123456',
            'address' => 'Zone Akwa, Douala',
            'is_available' => true
        ]);

        DeliveryPerson::create([
            'name' => 'Paul Rapide',
            'email' => 'paul@delivery.cm',
            'password' => 'delivery123',
            'id_card_number' => 'CNI-789012',
            'address' => 'Bonanjo, Douala',
            'is_available' => true
        ]);

        //Créer Catégories
        $categoriesNames = [
            'Ordinateurs',
            'Téléphones',
            'Tablettes',
            'Caméras',
            'Audio',
            'Wearables',
            'Gaming',
            'TV & Home',
            'Accessoires'
        ];

        foreach ($categoriesNames as $name) {
            Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Produits de la catégorie {$name}",
                'is_active' => true
            ]);
        }

        $categories=Category::pluck('id','name');

        // Créer des produits
        $products = [
            [
                'name' => 'MacBook Pro 14"',
                'quantity' => 15,
                'price' => 2500000,
                'serial_id' => 'MBP-14-001',
                'description' => 'Apple M2 Pro, 16GB RAM, 512GB SSD',
                'brand' => 'Apple',
                'category_id' => $categories['Ordinateurs']
            ],
            [
                'name' => 'Dell XPS 15',
                'quantity' => 12,
                'price' => 2200000,
                'serial_id' => 'DELL-XPS15-001',
                'description' => 'Intel i9, 32GB RAM, écran 4K',
                'brand' => 'Dell',
                'category_id' => $categories['Ordinateurs']
            ],
            [
                'name' => 'iPhone 15 Pro',
                'quantity' => 30,
                'price' => 1200000,
                'serial_id' => 'IPH-15-PRO-001',
                'description' => 'A17 Pro, Titanium, caméra pro',
                'brand' => 'Apple',
                'category_id' => $categories['Téléphones']
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'quantity' => 25,
                'price' => 1100000,
                'serial_id' => 'SAM-S24U-001',
                'description' => 'Snapdragon 8 Gen 3, S-Pen intégré',
                'brand' => 'Samsung',
                'category_id' => $categories['Téléphones']
            ],
            [
                'name' => 'iPad Pro 12.9"',
                'quantity' => 20,
                'price' => 1800000,
                'serial_id' => 'IPAD-PRO-001',
                'description' => 'Puce M2, écran Liquid Retina XDR',
                'brand' => 'Apple',
                'category_id' => $categories['Tablettes']
            ],
            [
                'name' => 'Canon EOS R6',
                'quantity' => 8,
                'price' => 3500000,
                'serial_id' => 'CANON-R6-001',
                'description' => 'Appareil photo hybride professionnel',
                'brand' => 'Canon',
                'category_id' => $categories['Caméras']
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'quantity' => 50,
                'price' => 350000,
                'serial_id' => 'SONY-XM5-001',
                'description' => 'Casque ANC premium',
                'brand' => 'Sony',
                'category_id' => $categories['Audio']
            ],
            [
                'name' => 'Apple Watch Series 9',
                'quantity' => 40,
                'price' => 450000,
                'serial_id' => 'AW-S9-001',
                'description' => 'GPS, capteurs santé avancés',
                'brand' => 'Apple',
                'category_id' => $categories['Wearables']
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'quantity' => 35,
                'price' => 350000,
                'serial_id' => 'NSW-OLED-001',
                'description' => 'Console hybride OLED',
                'brand' => 'Nintendo',
                'category_id' => $categories['Gaming']
            ],
            [
                'name' => 'Samsung 55" QLED 4K',
                'quantity' => 10,
                'price' => 1500000,
                'serial_id' => 'SAM-QLED55-001',
                'description' => 'Téléviseur QLED HDR10+',
                'brand' => 'Samsung',
                'category_id' => $categories['TV & Home']
            ],
        ];

        foreach ($products as $product) {
            Product::create($product + [
                'is_active' => true
            ]);
        }

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('📧 Admin: admin@ecommerce.cm / admin123');
        $this->command->info('👤 Client: john@example.com / password123');
        $this->command->info('🚚 Livreur: pierre@delivery.cm / delivery123');
    }
}