<?php

// ============================================
// app/Console/Commands/SeedProductImagesCommand.php
// ============================================

namespace App\Console\Commands;

use App\Services\SupabaseStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SeedProductImagesCommand extends Command
{
    protected $signature = 'products:seed-images 
                            {--download : Télécharger des images depuis Picsum}
                            {--upload : Uploader les images vers Supabase}';
    
    protected $description = 'Télécharge et upload des images de produits vers Supabase Storage';

    protected $storageService;

    public function __construct(SupabaseStorageService $storageService)
    {
        parent::__construct();
        $this->storageService = $storageService;
    }

    public function handle()
    {
        if ($this->option('download')) {
            $this->downloadImages();
        }

        if ($this->option('upload')) {
            $this->uploadToSupabase();
        }

        if (!$this->option('download') && !$this->option('upload')) {
            $this->info('Utilisez --download pour télécharger les images ou --upload pour les uploader');
        }
    }

    /**
     * Télécharge des images depuis Picsum Photos
     */
    private function downloadImages()
    {
        $this->info('📥 Téléchargement des images depuis Picsum Photos...');

        // Images à télécharger avec un "seed" basé sur le produit
        $imageSeeds = [
            'macbook-pro.jpg' => 'macbook-pro',
            'dell-xps.jpg' => 'dell-xps',
            'hp-gaming.jpg' => 'hp-gaming',
            'lenovo-thinkpad.jpg' => 'thinkpad',
            'iphone-15.jpg' => 'iphone-15',
            'samsung-s24.jpg' => 'samsung-s24',
            'google-pixel.jpg' => 'google-pixel',
            'xiaomi-14.jpg' => 'xiaomi-14',
            'ipad-pro.jpg' => 'ipad-pro',
            'samsung-tab.jpg' => 'samsung-tab',
            'canon-camera.jpg' => 'canon-camera',
            'sony-camera.jpg' => 'sony-camera',
            'gopro.jpg' => 'gopro',
            'sony-headphones.jpg' => 'sony-headphones',
            'airpods.jpg' => 'airpods',
            'bose-headphones.jpg' => 'bose-headphones',
            'apple-watch.jpg' => 'apple-watch',
            'samsung-watch.jpg' => 'samsung-watch',
            'playstation-5.jpg' => 'playstation-5',
            'xbox-series-x.jpg' => 'xbox-series-x',
            'nintendo-switch.jpg' => 'nintendo-switch',
            'steam-deck.jpg' => 'steam-deck',
            'samsung-tv.jpg' => 'samsung-tv',
            'lg-oled.jpg' => 'lg-oled',
            'anker-powerbank.jpg' => 'anker-powerbank',
            'logitech-mouse.jpg' => 'logitech-mouse',
            'magic-keyboard.jpg' => 'magic-keyboard',
        ];

        $bar = $this->output->createProgressBar(count($imageSeeds));
        $bar->start();

        foreach ($imageSeeds as $filename => $seed) {
            try {
                // Télécharger l'image directement depuis Picsum avec un seed unique
                $url = "https://picsum.photos/seed/{$seed}/800/800";
                $imageData = Http::timeout(30)->get($url)->body();

                // Sauvegarder localement dans storage/app/product-images
                Storage::disk('local')->put("product-images/{$filename}", $imageData);
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\n❌ Erreur pour {$filename}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->info("\n✅ Images téléchargées dans storage/app/product-images/");
    }

    /**
     * Upload les images vers Supabase
     */
    private function uploadToSupabase()
    {
        $this->info('☁️  Upload des images vers Supabase Storage...');

        $localPath = storage_path('app/private/product-images');
        
        if (!is_dir($localPath)) {
            $this->error('❌ Le dossier product-images n\'existe pas. Exécutez d\'abord --download');
            return;
        }

        $files = glob($localPath . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        
        if (empty($files)) {
            $this->error('❌ Aucune image trouvée dans ' . $localPath);
            return;
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        $uploadedUrls = [];

        foreach ($files as $filePath) {
            try {
                $filename = basename($filePath);
                
                $file = new UploadedFile(
                    $filePath,
                    $filename,
                    mime_content_type($filePath),
                    null,
                    true
                );

                $result = $this->storageService->upload($file, "seeds/{$filename}");
                
                if ($result['success']) {
                    $uploadedUrls[$filename] = $result['url'];
                    $bar->advance();
                } else {
                    $this->error("\n❌ Échec upload {$filename}: " . ($result['error'] ?? 'Unknown'));
                }
            } catch (\Exception $e) {
                $this->error("\n❌ Erreur {$filename}: {$e->getMessage()}");
            }
        }

        $bar->finish();

        // Sauvegarder la liste des URLs dans un fichier JSON
        $jsonPath = storage_path('app/supabase-image-urls.json');
        file_put_contents($jsonPath, json_encode($uploadedUrls, JSON_PRETTY_PRINT));

        $this->info("\n✅ " . count($uploadedUrls) . " images uploadées vers Supabase");
        $this->info("📄 URLs sauvegardées dans: {$jsonPath}");
    }
}
