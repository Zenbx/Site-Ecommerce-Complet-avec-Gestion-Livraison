<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Resource Collection pour une liste de produits
 * 
 * Les Collections permettent de personnaliser la façon dont une liste
 * de modèles est transformée, en ajoutant des métadonnées de pagination,
 * des statistiques globales, etc.
 */
class ProductCollection extends ResourceCollection
{
    /**
     * Transforme la collection de resources en tableau.
     * 
     * Par défaut, Laravel va automatiquement utiliser ProductResource
     * pour transformer chaque produit individuel dans la collection.
     * 
     * Cette méthode nous permet d'ajouter des métadonnées supplémentaires
     * autour de la liste de produits.
     */
    public function toArray(Request $request): array
    {
        return [
            // 'data' contient la liste des produits transformés
            // Chaque produit est automatiquement transformé via ProductResource
            'data' => $this->collection,
            
            // Métadonnées supplémentaires sur la collection
            'meta' => [
                // Nombre total de produits dans cette collection
                'total' => $this->collection->count(),
                
                // Statistiques calculées sur toute la collection
                'statistics' => [
                    // Prix moyen de tous les produits
                    'average_price' => $this->collection->avg('price'),
                    
                    // Prix minimum et maximum
                    'min_price' => $this->collection->min('price'),
                    'max_price' => $this->collection->max('price'),
                    
                    // Nombre de produits en stock
                    'in_stock_count' => $this->collection->where('quantity', '>', 0)->count(),
                    
                    // Nombre de produits en rupture de stock
                    'out_of_stock_count' => $this->collection->where('quantity', 0)->count(),
                ],
            ],
        ];
    }
    
    /**
     * Ajouter des métadonnées supplémentaires à la réponse
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}