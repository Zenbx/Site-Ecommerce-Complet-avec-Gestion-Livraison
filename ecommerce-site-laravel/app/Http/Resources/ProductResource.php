<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour le modèle Product
 * 
 * Cette Resource transforme les produits pour l'affichage dans le catalogue
 * client et l'interface d'administration.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            
            // Prix formaté avec deux décimales
            // number_format() formate le nombre avec un séparateur de milliers
            // et le nombre de décimales spécifié
            'price' => number_format((float)$this->price, 2, '.', ''),
            
            // Prix comparatif formaté de la même manière
            // Cela permet d'afficher "Prix normal : 1200.00 FCFA, Prix promo : 1000.00 FCFA"
            'compare_price' => $this->compare_price 
                ? number_format((float)$this->compare_price, 2, '.', '') 
                : null,
            
            // Pourcentage de réduction calculé
            // Seulement si un prix comparatif existe
            'discount_percentage' => $this->when(
                $this->compare_price && $this->compare_price > $this->price,
                fn() => round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2)
            ),
            
            'quantity' => $this->quantity,
            
            // Indicateur de disponibilité en stock
            // C'est plus convivial que de demander au frontend de vérifier quantity > 0
            'in_stock' => $this->quantity > 0,
            
            // Niveau de stock textuel pour l'interface utilisateur
            'stock_status' => $this->getStockStatus(),
            
            'serial_id' => $this->serial_id,
            'brand' => $this->brand,
            'category' => $this->category,
            
            // URL de l'image
            // Si image_url est null, nous fournissons une URL vers une image placeholder
            'image_url' => $this->image_url ?? 'https://via.placeholder.com/400x400.png?text=No+Image',
            
            // Indicateur si le produit est actif dans le catalogue
            'is_active' => (bool)$this->is_active,
            
            // Indicateur si le produit est mis en avant
            'is_featured' => (bool)$this->is_featured,
            
            // Timestamps
            'added_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Détermine le statut du stock sous forme textuelle
     * 
     * Cette méthode helper transforme la quantité numérique en un message
     * convivial que le frontend peut afficher directement
     */
    protected function getStockStatus(): string
    {
        if ($this->quantity === 0) {
            return 'Rupture de stock';
        }
        
        if ($this->quantity < 5) {
            return 'Stock faible';
        }
        
        if ($this->quantity < 20) {
            return 'En stock';
        }
        
        return 'Stock important';
    }
    
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}