<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Clé primaire
            $table->id();
            
            // Nom du produit
            $table->string('name', 255);
            
            // Quantité en stock
            // unsigned() garantit que la quantité ne peut pas être négative
            // default(0) initialise à zéro pour les nouveaux produits
            $table->integer('quantity')->unsigned()->default(0);
            
            // Prix du produit
            // decimal(10, 2) signifie 10 chiffres au total, dont 2 après la virgule
            // Par exemple: 12345678.90
            $table->decimal('price', 10, 2);
            
            // Identifiant série unique du produit
            // Utilisé pour le tracking et la gestion d'inventaire
            $table->string('serial_id', 100)->unique();
            
            // Description détaillée du produit
            // nullable() car tous les produits n'ont pas forcément une description au début
            $table->text('description')->nullable();
            
            // Marque du produit
            $table->string('brand', 255)->nullable();
            
            // Catégorie du produit
            $table->string('category', 100)->nullable();
            
            // URL de l'image principale du produit
            $table->string('image_url', 500)->nullable();
            
            // Statut actif/inactif
            // Un produit inactif n'est plus vendu mais reste dans le système pour l'historique
            $table->boolean('is_active')->default(true);
            
            // Timestamps : created_at sera renommé added_at dans le modèle
            $table->timestamps();
        });
        
        // Créer des index pour améliorer les performances des recherches
        // Ces index accélèrent considérablement les requêtes qui filtrent par catégorie ou marque
        Schema::table('products', function (Blueprint $table) {
            $table->index('category', 'idx_product_category');
            $table->index('brand', 'idx_product_brand');
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};