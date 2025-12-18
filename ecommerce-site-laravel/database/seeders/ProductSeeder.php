<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Traits\UploadProductImages;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use UploadProductImages;

    // URLs Supabase des images seedées
    protected $supabaseImages = [];

    public function run(): void
    {
        // Charger les URLs Supabase si disponibles
        $this->loadSupabaseImageUrls();

        // Récupérer les catégories existantes ou les créer
        $categories = Category::pluck('id', 'name')->toArray();
        if (empty($categories)) {
            $this->createCategories();
            $categories = Category::pluck('id', 'name')->toArray();
        }

        // Créer les produits avec images Supabase ou fallback Picsum
        $this->createProductsWithImages($categories);
    }

    private function loadSupabaseImageUrls(): void
    {
        $jsonPath = storage_path('app/supabase-image-urls.json');
        if (file_exists($jsonPath)) {
            $this->supabaseImages = json_decode(file_get_contents($jsonPath), true);
            $this->command->info('✅ ' . count($this->supabaseImages) . ' URLs Supabase chargées');
        } else {
            $this->command->warn('⚠️ Aucun fichier supabase-image-urls.json trouvé');
            $this->command->info('💡 Exécutez: php artisan products:seed-images --download --upload');
        }
    }

    private function getImageUrl(string $imageKey, string $fallbackQuery): string
    {
        // Supabase si disponible
        if (isset($this->supabaseImages[$imageKey])) {
            return $this->supabaseImages[$imageKey];
        }

        // Fallback Picsum
        $safeQuery = str_replace(' ', '-', strtolower($fallbackQuery));
        return "https://picsum.photos/seed/{$safeQuery}/800/800";
    }

    private function createCategories(): void
    {
        $categoryNames = [
            'Ordinateurs' => 'Laptops, PC portables et stations de travail',
            'Téléphones' => 'Smartphones et téléphones mobiles',
            'Tablettes' => 'Tablettes tactiles et iPad',
            'Caméras' => 'Appareils photo et caméras vidéo',
            'Audio' => 'Casques, écouteurs et enceintes',
            'Wearables' => 'Montres connectées et trackers',
            'Gaming' => 'Consoles de jeux et accessoires',
            'TV & Home' => 'Téléviseurs et électronique maison',
            'Accessoires' => 'Chargeurs, câbles et accessoires divers',
        ];

        foreach ($categoryNames as $name => $description) {
            Category::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }
    }

    private function createProductsWithImages(array $categories): void
    {
        $products = [
            [
                'name' => 'MacBook Pro 14"',
                'quantity' => 15,
                'price' => 2500000,
                'serial_id' => 'MBP-14-001',
                'description' => 'Apple M2 Pro, 16GB RAM, 512GB SSD, écran Liquid Retina XDR',
                'brand' => 'Apple',
                'category_id' => $categories['Ordinateurs'] ?? null,
                'image_key' => 'macbook-pro.jpg',
                'image_query' => 'macbook pro laptop',
            ],
            [
                'name' => 'Dell XPS 15',
                'quantity' => 12,
                'price' => 2200000,
                'serial_id' => 'DELL-XPS15-001',
                'description' => 'Intel Core i9-13900H, 32GB RAM, NVIDIA RTX 4050, écran 4K OLED tactile',
                'brand' => 'Dell',
                'category_id' => $categories['Ordinateurs'] ?? null,
                'image_key' => 'dell-xps.jpg',
                'image_query' => 'dell laptop computer',
            ],
            [
                'name' => 'HP Pavilion Gaming',
                'quantity' => 20,
                'price' => 950000,
                'serial_id' => 'HP-PAV-001',
                'description' => 'AMD Ryzen 7, 16GB RAM, NVIDIA GTX 1650, SSD 512GB',
                'brand' => 'HP',
                'category_id' => $categories['Ordinateurs'] ?? null,
                'image_key' => 'hp-gaming.jpg',
                'image_query' => 'gaming laptop hp',
            ],
            [
                'name' => 'Lenovo ThinkPad X1 Carbon',
                'quantity' => 8,
                'price' => 1800000,
                'serial_id' => 'LEN-X1C-001',
                'description' => 'Intel i7, 16GB RAM, 1TB SSD, écran 14" FHD, ultra-léger',
                'brand' => 'Lenovo',
                'category_id' => $categories['Ordinateurs'] ?? null,
                'image_key' => 'lenovo-thinkpad.jpg',
                'image_query' => 'thinkpad laptop',
            ],

            // TÉLÉPHONES
            [
                'name' => 'iPhone 15 Pro Max',
                'quantity' => 30,
                'price' => 1500000,
                'serial_id' => 'IPH-15PM-001',
                'description' => 'A17 Pro, Titanium, caméra 48MP, zoom optique 5x, USB-C',
                'brand' => 'Apple',
                'category_id' => $categories['Téléphones'] ?? null,
                'image_key' => 'iphone-15.jpg',
                'image_query' => 'iphone smartphone',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'quantity' => 25,
                'price' => 1100000,
                'serial_id' => 'SAM-S24U-001',
                'description' => 'Snapdragon 8 Gen 3, S-Pen intégré, zoom 100x, écran Dynamic AMOLED 2X',
                'brand' => 'Samsung',
                'category_id' => $categories['Téléphones'] ?? null,
                'image_key' => 'samsung-s24.jpg',
                'image_query' => 'samsung galaxy smartphone',
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'quantity' => 18,
                'price' => 950000,
                'serial_id' => 'GOO-P8P-001',
                'description' => 'Tensor G3, caméra exceptionnelle, IA avancée, Android pur',
                'brand' => 'Google',
                'category_id' => $categories['Téléphones'] ?? null,
                'image_key' => 'google-pixel.jpg',
                'image_query' => 'google pixel phone',
            ],
            [
                'name' => 'Xiaomi 14 Pro',
                'quantity' => 35,
                'price' => 650000,
                'serial_id' => 'XIA-14P-001',
                'description' => 'Snapdragon 8 Gen 3, caméra Leica, charge 120W',
                'brand' => 'Xiaomi',
                'category_id' => $categories['Téléphones'] ?? null,
                'image_key' => 'xiaomi-14.jpg',
                'image_query' => 'xiaomi smartphone',
            ],

            // TABLETTES
            [
                'name' => 'iPad Pro 12.9" M2',
                'quantity' => 20,
                'price' => 1800000,
                'serial_id' => 'IPAD-PRO-001',
                'description' => 'Puce M2, écran Liquid Retina XDR, 256GB, compatible Apple Pencil',
                'brand' => 'Apple',
                'category_id' => $categories['Tablettes'] ?? null,
                'image_key' => 'ipad-pro.jpg',
                'image_query' => 'ipad pro tablet',
            ],
            [
                'name' => 'Samsung Galaxy Tab S9 Ultra',
                'quantity' => 15,
                'price' => 1200000,
                'serial_id' => 'SAM-TABS9U-001',
                'description' => 'Écran AMOLED 14.6", Snapdragon 8 Gen 2, S-Pen inclus',
                'brand' => 'Samsung',
                'category_id' => $categories['Tablettes'] ?? null,
                'image_key' => 'samsung-tab.jpg',
                'image_query' => 'samsung tablet',
            ],

            // CAMÉRAS
            [
                'name' => 'Canon EOS R6 Mark II',
                'quantity' => 8,
                'price' => 3500000,
                'serial_id' => 'CANON-R6M2-001',
                'description' => 'Hybride plein format 24MP, rafale 40 fps, vidéo 6K',
                'brand' => 'Canon',
                'category_id' => $categories['Caméras'] ?? null,
                'image_key' => 'canon-camera.jpg',
                'image_query' => 'canon camera professional',
            ],
            [
                'name' => 'Sony A7 IV',
                'quantity' => 10,
                'price' => 3200000,
                'serial_id' => 'SONY-A7IV-001',
                'description' => 'Hybride 33MP, stabilisation 5 axes, vidéo 4K 60fps',
                'brand' => 'Sony',
                'category_id' => $categories['Caméras'] ?? null,
                'image_key' => 'sony-camera.jpg',
                'image_query' => 'sony mirrorless camera',
            ],
            [
                'name' => 'GoPro Hero 12 Black',
                'quantity' => 25,
                'price' => 450000,
                'serial_id' => 'GOP-H12-001',
                'description' => 'Action cam 5.3K, stabilisation HyperSmooth 6.0, étanche',
                'brand' => 'GoPro',
                'category_id' => $categories['Caméras'] ?? null,
                'image_key' => 'gopro.jpg',
                'image_query' => 'gopro action camera',
            ],

            // AUDIO
            [
                'name' => 'Sony WH-1000XM5',
                'quantity' => 50,
                'price' => 350000,
                'serial_id' => 'SONY-XM5-001',
                'description' => 'Casque ANC premium, autonomie 30h, audio Hi-Res',
                'brand' => 'Sony',
                'category_id' => $categories['Audio'] ?? null,
                'image_key' => 'sony-headphones.jpg',
                'image_query' => 'sony headphones wireless',
            ],
            [
                'name' => 'AirPods Pro 2',
                'quantity' => 60,
                'price' => 280000,
                'serial_id' => 'APP-PRO2-001',
                'description' => 'ANC adaptatif, audio spatial, puce H2, USB-C',
                'brand' => 'Apple',
                'category_id' => $categories['Audio'] ?? null,
                'image_key' => 'airpods.jpg',
                'image_query' => 'apple airpods',
            ],
            [
                'name' => 'Bose QuietComfort Ultra',
                'quantity' => 30,
                'price' => 380000,
                'serial_id' => 'BOS-QCU-001',
                'description' => 'ANC de référence, audio spatial immersif',
                'brand' => 'Bose',
                'category_id' => $categories['Audio'] ?? null,
                'image_key' => 'bose-headphones.jpg',
                'image_query' => 'bose headphones',
            ],

            // WEARABLES
            [
                'name' => 'Apple Watch Series 9',
                'quantity' => 40,
                'price' => 450000,
                'serial_id' => 'AW-S9-001',
                'description' => 'GPS, capteurs santé avancés, écran Always-On, puce S9',
                'brand' => 'Apple',
                'category_id' => $categories['Wearables'] ?? null,
                'image_key' => 'apple-watch.jpg',
                'image_query' => 'apple watch',
            ],
            [
                'name' => 'Samsung Galaxy Watch 6 Classic',
                'quantity' => 28,
                'price' => 380000,
                'serial_id' => 'SAM-GW6C-001',
                'description' => 'Lunette rotative, Wear OS, suivi santé complet',
                'brand' => 'Samsung',
                'category_id' => $categories['Wearables'] ?? null,
                'image_key' => 'samsung-watch.jpg',
                'image_query' => 'samsung galaxy watch',
            ],

            // GAMING
            [
                'name' => 'PlayStation 5 Slim',
                'quantity' => 22,
                'price' => 550000,
                'serial_id' => 'PS5-SLIM-001',
                'description' => 'Console nouvelle génération, SSD 1TB, 4K 120fps',
                'brand' => 'Sony',
                'category_id' => $categories['Gaming'] ?? null,
                'image_key' => 'playstation-5.jpg',
                'image_query' => 'playstation 5 console',
            ],
            [
                'name' => 'Xbox Series X',
                'quantity' => 18,
                'price' => 550000,
                'serial_id' => 'XBX-SX-001',
                'description' => 'Console 4K, SSD 1TB, Game Pass compatible',
                'brand' => 'Microsoft',
                'category_id' => $categories['Gaming'] ?? null,
                'image_key' => 'xbox-series-x.jpg',
                'image_query' => 'xbox series x',
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'quantity' => 35,
                'price' => 350000,
                'serial_id' => 'NSW-OLED-001',
                'description' => 'Console hybride, écran OLED 7", dock ethernet intégré',
                'brand' => 'Nintendo',
                'category_id' => $categories['Gaming'] ?? null,
                'image_key' => 'nintendo-switch.jpg',
                'image_query' => 'nintendo switch oled',
            ],
            [
                'name' => 'Steam Deck OLED',
                'quantity' => 12,
                'price' => 650000,
                'serial_id' => 'STM-DCK-001',
                'description' => 'PC gaming portable, écran OLED, SteamOS',
                'brand' => 'Valve',
                'category_id' => $categories['Gaming'] ?? null,
                'image_key' => 'steam-deck.jpg',
                'image_query' => 'steam deck handheld',
            ],

            // TV & HOME
            [
                'name' => 'Samsung 65" Neo QLED 4K',
                'quantity' => 10,
                'price' => 2200000,
                'serial_id' => 'SAM-NEO65-001',
                'description' => 'Téléviseur Neo QLED, Quantum Matrix, HDR10+, 120Hz',
                'brand' => 'Samsung',
                'category_id' => $categories['TV & Home'] ?? null,
                'image_key' => 'samsung-tv.jpg',
                'image_query' => 'samsung qled tv',
            ],
            [
                'name' => 'LG 55" OLED C3',
                'quantity' => 8,
                'price' => 1800000,
                'serial_id' => 'LG-C3-55-001',
                'description' => 'TV OLED evo, α9 Gen6, Dolby Vision IQ, 120Hz',
                'brand' => 'LG',
                'category_id' => $categories['TV & Home'] ?? null,
                'image_key' => 'lg-oled.jpg',
                'image_query' => 'lg oled tv',
            ],

            // ACCESSOIRES
            [
                'name' => 'Anker PowerCore 20000mAh',
                'quantity' => 100,
                'price' => 45000,
                'serial_id' => 'ANK-PC20-001',
                'description' => 'Batterie externe, charge rapide 18W, 2 ports USB',
                'brand' => 'Anker',
                'category_id' => $categories['Accessoires'] ?? null,
                'image_key' => 'anker-powerbank.jpg',
                'image_query' => 'anker power bank',
            ],
            [
                'name' => 'Logitech MX Master 3S',
                'quantity' => 45,
                'price' => 95000,
                'serial_id' => 'LOG-MX3S-001',
                'description' => 'Souris sans fil, 8000 DPI, silencieuse, multi-device',
                'brand' => 'Logitech',
                'category_id' => $categories['Accessoires'] ?? null,
                'image_key' => 'logitech-mouse.jpg',
                'image_query' => 'logitech mx master mouse',
            ],
            [
                'name' => 'Apple Magic Keyboard',
                'quantity' => 30,
                'price' => 120000,
                'serial_id' => 'APP-MKB-001',
                'description' => 'Clavier sans fil, batterie rechargeable, design compact',
                'brand' => 'Apple',
                'category_id' => $categories['Accessoires'] ?? null,
                'image_key' => 'magic-keyboard.jpg',
                'image_query' => 'apple magic keyboard',
            ],
        ];

        $bar = $this->command->getOutput()->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $productData) {
            $imageKey = $productData['image_key'];
            $imageQuery = $productData['image_query'];
            unset($productData['image_key'], $productData['image_query']);

            $productData['image_url'] = $this->getImageUrl($imageKey, $imageQuery);
            $productData['is_active'] = true;

            Product::updateOrCreate(
                ['serial_id' => $productData['serial_id']],
                $productData
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->info("\n✅ " . count($products) . " produits créés avec succès");
    }
}
