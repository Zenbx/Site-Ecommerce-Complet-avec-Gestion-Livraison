<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\Client;
use App\Models\DeliveryPerson;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un admin
        Admin::create([
            'name' => 'Admin Principal',
            'email' => 'admin@ecommerce.cm',
            'password' => Hash::make('admin123'),
            'role' => 'ADMIN'
        ]);

        // Créer des clients de test
        Client::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'address' => '123 Main St, Douala, Cameroun'
        ]);

        Client::create([
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'password' => Hash::make('password123'),
            'address' => '456 Avenue de la Liberté, Douala, Cameroun'
        ]);

        // Créer des livreurs
        DeliveryPerson::create([
            'name' => 'Pierre Livreur',
            'email' => 'pierre@delivery.cm',
            'password' => Hash::make('delivery123'),
            'id_card_number' => 'CNI-123456',
            'address' => 'Zone Akwa, Douala',
            'is_available' => true
        ]);

        DeliveryPerson::create([
            'name' => 'Paul Rapide',
            'email' => 'paul@delivery.cm',
            'password' => Hash::make('delivery123'),
            'id_card_number' => 'CNI-789012',
            'address' => 'Bonanjo, Douala',
            'is_available' => true
        ]);

        // Créer des produits
        $products = [
            [
                'name' => 'MacBook Pro 14"',
                'quantity' => 15,
                'price' => 2500000,
                'serial_id' => 'MBP-14-001',
                'description' => 'Apple M2 Pro, 16GB RAM, 512GB SSD',
                'brand' => 'Apple',
                'category' => 'Électronique',
                'is_active' => true
            ],
            [
                'name' => 'iPhone 15 Pro',
                'quantity' => 30,
                'price' => 1200000,
                'serial_id' => 'IPH-15-PRO-001',
                'description' => 'Smartphone haut de gamme avec puce A17 Pro',
                'brand' => 'Apple',
                'category' => 'Électronique',
                'is_active' => true
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'quantity' => 25,
                'price' => 1100000,
                'serial_id' => 'SAM-S24U-001',
                'description' => 'Smartphone Android flagship avec S Pen',
                'brand' => 'Samsung',
                'category' => 'Électronique',
                'is_active' => true
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'quantity' => 50,
                'price' => 350000,
                'serial_id' => 'SONY-XM5-001',
                'description' => 'Casque audio sans fil avec réduction de bruit',
                'brand' => 'Sony',
                'category' => 'Audio',
                'is_active' => true
            ],
            [
                'name' => 'iPad Pro 12.9"',
                'quantity' => 20,
                'price' => 1800000,
                'serial_id' => 'IPAD-PRO-001',
                'description' => 'Tablette professionnelle avec puce M2',
                'brand' => 'Apple',
                'category' => 'Électronique',
                'is_active' => true
            ],
            [
                'name' => 'Dell XPS 15',
                'quantity' => 12,
                'price' => 2200000,
                'serial_id' => 'DELL-XPS15-001',
                'description' => 'Laptop professionnel Intel i9, 32GB RAM',
                'brand' => 'Dell',
                'category' => 'Électronique',
                'is_active' => true
            ],
            [
                'name' => 'Canon EOS R6',
                'quantity' => 8,
                'price' => 3500000,
                'serial_id' => 'CANON-R6-001',
                'description' => 'Appareil photo hybride professionnel',
                'brand' => 'Canon',
                'category' => 'Photographie',
                'is_active' => true
            ],
            [
                'name' => 'Apple Watch Series 9',
                'quantity' => 40,
                'price' => 450000,
                'serial_id' => 'AW-S9-001',
                'description' => 'Montre connectée avec GPS et capteurs santé',
                'brand' => 'Apple',
                'category' => 'Wearables',
                'is_active' => true
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'quantity' => 35,
                'price' => 350000,
                'serial_id' => 'NSW-OLED-001',
                'description' => 'Console de jeux portable et de salon',
                'brand' => 'Nintendo',
                'category' => 'Gaming',
                'is_active' => true
            ],
            [
                'name' => 'Samsung 55" QLED TV',
                'quantity' => 10,
                'price' => 1500000,
                'serial_id' => 'SAM-QLED55-001',
                'description' => 'Téléviseur 4K avec technologie QLED',
                'brand' => 'Samsung',
                'category' => 'TV & Home',
                'is_active' => true
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('📧 Admin: admin@ecommerce.cm / admin123');
        $this->command->info('👤 Client: john@example.com / password123');
        $this->command->info('🚚 Livreur: pierre@delivery.cm / delivery123');
    }
}