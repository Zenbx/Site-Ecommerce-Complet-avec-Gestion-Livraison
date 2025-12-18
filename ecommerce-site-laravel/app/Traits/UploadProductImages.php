<?php

namespace App\Traits;

use App\Services\SupabaseStorageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

trait UploadProductImages
{
    /**
     * Télécharge une image depuis une URL et l'upload vers Supabase
     */
    protected function downloadAndUploadImage(string $imageUrl, string $productName): ?string
    {
        try {
            $storageService = app(SupabaseStorageService::class);
            
            // Télécharger l'image
            $response = Http::timeout(30)->get($imageUrl);
            
            if (!$response->successful()) {
                return null;
            }

            $imageData = $response->body();
            
            // Créer un fichier temporaire
            $tempPath = tempnam(sys_get_temp_dir(), 'product_');
            file_put_contents($tempPath, $imageData);
            
            // Détecter le type MIME
            $mimeType = mime_content_type($tempPath);
            $extension = explode('/', $mimeType)[1] ?? 'jpg';
            
            // Créer un UploadedFile
            $file = new UploadedFile(
                $tempPath,
                $productName . '.' . $extension,
                $mimeType,
                null,
                true
            );

            // Upload vers Supabase
            $result = $storageService->upload($file);
            
            // Nettoyer le fichier temporaire
            @unlink($tempPath);
            
            return $result['success'] ? $result['url'] : null;
            
        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());
            return null;
        }
    }

      /**
     * Obtenir l'URL d'une image Unsplash par requête
     */
    protected function getUnsplashImageUrl(string $query, int $width = 800, int $height = 800): string
    {
        return "https://source.unsplash.com/{$width}x{$height}/?{$query}";
    }
}