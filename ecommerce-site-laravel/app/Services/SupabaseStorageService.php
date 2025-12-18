<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    protected $url;
    protected $key;
    protected $bucket;

    public function __construct()
    {
        $this->url = config('supabase.url');
        $this->key = config('supabase.key');
        $this->bucket = config('supabase.bucket');
    }

    /**
     * Upload un fichier vers Supabase Storage
     * 
     * @param UploadedFile $file Le fichier à uploader
     * @param string|null $path Le chemin personnalisé (optionnel)
     * @return array ['success' => bool, 'path' => string, 'url' => string] ou ['success' => false, 'error' => string]
     */
    public function upload(UploadedFile $file, string $path = null): array
    {
        try {
            // Générer un nom de fichier unique si non fourni
            $filename = $path ?? $this->generateFilename($file);
            
            // Lire le contenu du fichier
            $fileContent = file_get_contents($file->getRealPath());
            
            // Déterminer le Content-Type
            $contentType = $file->getMimeType();
            
            // Upload vers Supabase avec le bon Content-Type
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'apikey' => $this->key,
                'Content-Type' => $contentType,
            ])
            ->withBody($fileContent, $contentType)
            ->post("{$this->url}/storage/v1/object/{$this->bucket}/{$filename}");

            if ($response->successful()) {
                Log::info('Supabase upload successful', [
                    'filename' => $filename,
                    'size' => $file->getSize(),
                ]);

                return [
                    'success' => true,
                    'path' => $filename,
                    'url' => $this->getPublicUrl($filename),
                ];
            }

            Log::error('Supabase upload failed', [
                'filename' => $filename,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Upload failed',
                'details' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('Supabase upload exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Supprimer un fichier de Supabase Storage
     * 
     * @param string $path Le chemin du fichier à supprimer
     * @return bool True si la suppression a réussi
     */
    public function delete(string $path): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'apikey' => $this->key,
            ])
            ->delete("{$this->url}/storage/v1/object/{$this->bucket}/{$path}");

            if ($response->successful()) {
                Log::info('Supabase delete successful', ['path' => $path]);
                return true;
            }

            Log::warning('Supabase delete failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Supabase delete exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Obtenir l'URL publique d'un fichier
     * 
     * @param string $path Le chemin du fichier
     * @return string L'URL publique complète
     */
    public function getPublicUrl(string $path): string
    {
        return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$path}";
    }

    /**
     * Extraire le path depuis une URL Supabase complète
     * Utile pour supprimer un fichier à partir de son URL
     * 
     * @param string $url L'URL complète du fichier
     * @return string|null Le path extrait ou null si l'URL est invalide
     */
    public function extractPathFromUrl(string $url): ?string
    {
        // Format attendu: https://xxx.supabase.co/storage/v1/object/public/bucket/path
        $pattern = '/\/storage\/v1\/object\/public\/' . preg_quote($this->bucket, '/') . '\/(.+)$/';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        Log::warning('Unable to extract path from URL', ['url' => $url]);
        return null;
    }

    /**
     * Générer un nom de fichier unique
     * Format: products/{uuid}.{extension}
     * 
     * @param UploadedFile $file Le fichier uploadé
     * @return string Le nom de fichier généré
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $uuid = Str::uuid();
        return "products/{$uuid}.{$extension}";
    }

    /**
     * Lister les fichiers dans un dossier du bucket
     * 
     * @param string $folder Le préfixe/dossier à lister (par défaut: racine)
     * @param int $limit Nombre maximum de fichiers à retourner (par défaut: 100)
     * @return array Liste des fichiers ou tableau vide en cas d'erreur
     */
    public function listFiles(string $folder = '', int $limit = 100): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'apikey' => $this->key,
            ])
            ->post("{$this->url}/storage/v1/object/list/{$this->bucket}", [
                'prefix' => $folder,
                'limit' => $limit,
                'offset' => 0,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Supabase list files failed', [
                'folder' => $folder,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('Supabase list files exception', [
                'folder' => $folder,
                'message' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Vérifier si un fichier existe dans le bucket
     * 
     * @param string $path Le chemin du fichier
     * @return bool True si le fichier existe
     */
    public function exists(string $path): bool
    {
        try {
            // Extraire le dossier et le nom du fichier
            $pathParts = explode('/', $path);
            $filename = array_pop($pathParts);
            $folder = implode('/', $pathParts);

            $files = $this->listFiles($folder);

            foreach ($files as $file) {
                if (isset($file['name']) && $file['name'] === $filename) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Supabase exists check exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Déplacer/Renommer un fichier
     * 
     * @param string $fromPath Le chemin actuel du fichier
     * @param string $toPath Le nouveau chemin du fichier
     * @return bool True si l'opération a réussi
     */
    public function move(string $fromPath, string $toPath): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'apikey' => $this->key,
            ])
            ->post("{$this->url}/storage/v1/object/move", [
                'bucketId' => $this->bucket,
                'sourceKey' => $fromPath,
                'destinationKey' => $toPath,
            ]);

            if ($response->successful()) {
                Log::info('Supabase move successful', [
                    'from' => $fromPath,
                    'to' => $toPath,
                ]);
                return true;
            }

            Log::warning('Supabase move failed', [
                'from' => $fromPath,
                'to' => $toPath,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Supabase move exception', [
                'from' => $fromPath,
                'to' => $toPath,
                'message' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Copier un fichier
     * 
     * @param string $fromPath Le chemin du fichier source
     * @param string $toPath Le chemin de destination
     * @return bool True si l'opération a réussi
     */
    public function copy(string $fromPath, string $toPath): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'apikey' => $this->key,
            ])
            ->post("{$this->url}/storage/v1/object/copy", [
                'bucketId' => $this->bucket,
                'sourceKey' => $fromPath,
                'destinationKey' => $toPath,
            ]);

            if ($response->successful()) {
                Log::info('Supabase copy successful', [
                    'from' => $fromPath,
                    'to' => $toPath,
                ]);
                return true;
            }

            Log::warning('Supabase copy failed', [
                'from' => $fromPath,
                'to' => $toPath,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Supabase copy exception', [
                'from' => $fromPath,
                'to' => $toPath,
                'message' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Obtenir les informations d'un fichier (métadonnées)
     * 
     * @param string $path Le chemin du fichier
     * @return array|null Les métadonnées du fichier ou null
     */
    public function getFileInfo(string $path): ?array
    {
        try {
            // Extraire le dossier et le nom du fichier
            $pathParts = explode('/', $path);
            $filename = array_pop($pathParts);
            $folder = implode('/', $pathParts);

            $files = $this->listFiles($folder);

            foreach ($files as $file) {
                if (isset($file['name']) && $file['name'] === $filename) {
                    return $file;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Supabase get file info exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Créer une URL signée (privée) avec expiration
     * Utile pour partager temporairement des fichiers privés
     * 
     * @param string $path Le chemin du fichier
     * @param int $expiresIn Durée de validité en secondes (par défaut: 1 heure)
     * @return string|null L'URL signée ou null en cas d'erreur
     */
    public function createSignedUrl(string $path, int $expiresIn = 3600): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'apikey' => $this->key,
            ])
            ->post("{$this->url}/storage/v1/object/sign/{$this->bucket}/{$path}", [
                'expiresIn' => $expiresIn,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['signedURL'])) {
                    return $this->url . $data['signedURL'];
                }
            }

            Log::warning('Supabase create signed URL failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Supabase create signed URL exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Uploader un fichier depuis une URL
     * 
     * @param string $url L'URL du fichier à télécharger
     * @param string|null $path Le chemin de destination (optionnel)
     * @return array ['success' => bool, 'path' => string, 'url' => string]
     */
    public function uploadFromUrl(string $url, string $path = null): array
    {
        try {
            // Télécharger le fichier
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to download file from URL',
                ];
            }

            $fileContent = $response->body();
            
            // Détecter le type MIME
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($fileContent);
            
            // Déterminer l'extension
            $extension = explode('/', $mimeType)[1] ?? 'jpg';
            
            // Générer le nom de fichier si non fourni
            if (!$path) {
                $path = 'products/' . Str::uuid() . '.' . $extension;
            }
            
            // Créer un fichier temporaire
            $tempPath = tempnam(sys_get_temp_dir(), 'supabase_');
            file_put_contents($tempPath, $fileContent);
            
            // Créer un UploadedFile
            $uploadedFile = new UploadedFile(
                $tempPath,
                basename($path),
                $mimeType,
                null,
                true
            );

            // Upload vers Supabase
            $result = $this->upload($uploadedFile, $path);
            
            // Nettoyer le fichier temporaire
            @unlink($tempPath);
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Supabase upload from URL exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Supprimer plusieurs fichiers en une seule requête
     * 
     * @param array $paths Liste des chemins de fichiers à supprimer
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function deleteMultiple(array $paths): array
    {
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($paths as $path) {
            if ($this->delete($path)) {
                $success++;
            } else {
                $failed++;
                $errors[] = $path;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Obtenir la taille totale utilisée dans le bucket
     * 
     * @param string $folder Dossier spécifique (optionnel)
     * @return int Taille en bytes
     */
    public function getTotalSize(string $folder = ''): int
    {
        try {
            $files = $this->listFiles($folder, 1000);
            $totalSize = 0;

            foreach ($files as $file) {
                if (isset($file['metadata']['size'])) {
                    $totalSize += (int) $file['metadata']['size'];
                }
            }

            return $totalSize;

        } catch (\Exception $e) {
            Log::error('Supabase get total size exception', [
                'folder' => $folder,
                'message' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }

    /**
     * Nettoyer les fichiers plus anciens que X jours
     * 
     * @param int $days Nombre de jours
     * @param string $folder Dossier spécifique (optionnel)
     * @return int Nombre de fichiers supprimés
     */
    public function cleanOldFiles(int $days, string $folder = ''): int
    {
        try {
            $files = $this->listFiles($folder, 1000);
            $deleted = 0;
            $cutoffDate = now()->subDays($days);

            foreach ($files as $file) {
                if (isset($file['created_at'])) {
                    $createdAt = \Carbon\Carbon::parse($file['created_at']);
                    
                    if ($createdAt->lt($cutoffDate)) {
                        $path = $folder ? "{$folder}/{$file['name']}" : $file['name'];
                        
                        if ($this->delete($path)) {
                            $deleted++;
                        }
                    }
                }
            }

            Log::info('Supabase clean old files completed', [
                'deleted' => $deleted,
                'days' => $days,
                'folder' => $folder,
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Supabase clean old files exception', [
                'days' => $days,
                'folder' => $folder,
                'message' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }
}