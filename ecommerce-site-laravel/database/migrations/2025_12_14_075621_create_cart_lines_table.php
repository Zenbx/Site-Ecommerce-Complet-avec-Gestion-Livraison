<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Exécuter la migration.
     */
    public function up(): void
    {
        Schema::create('cart_lines', function (Blueprint $table) {
            // Clé primaire
            $table->id();
            
            // Référence au panier auquel appartient cette ligne
            // onDelete('cascade') : si le panier est supprimé, toutes ses lignes le sont aussi
            $table->unsignedBigInteger('cart_id');
            $table->foreign('cart_id')
                  ->references('id')
                  ->on('carts')
                  ->onDelete('cascade');
            
            // Référence au produit concerné
            // onDelete('cascade') : si le produit est supprimé, la ligne de panier l'est aussi
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
            
            // Quantité du produit dans cette ligne de panier
            // unsignedInteger() car on ne peut pas avoir une quantité négative
            $table->unsignedInteger('quantity');
            
            // Prix unitaire au moment de l'ajout au panier
            // C'est crucial de stocker le prix car il peut changer avec le temps
            // Si un client ajoute un produit à 1000 FCFA et que le prix monte à 1200 FCFA
            // le lendemain, son panier doit toujours afficher 1000 FCFA
            $table->decimal('unit_price', 10, 2);
            
            // Date d'ajout de cette ligne au panier
            // On utilise timestamp() plutôt que timestamps() car on veut seulement added_at
            $table->timestamp('added_at')->useCurrent();
        });
        
        // Contrainte CHECK pour garantir que la quantité est strictement positive
        // quantity > 0 signifie qu'on ne peut pas avoir une ligne avec 0 produits
        DB::statement('ALTER TABLE cart_lines ADD CONSTRAINT check_cart_line_quantity CHECK (quantity > 0)');
        
        // Contrainte CHECK pour le prix
        DB::statement('ALTER TABLE cart_lines ADD CONSTRAINT check_cart_line_price CHECK (unit_price >= 0)');
        
        // Contrainte UNIQUE composite
        // Un même produit ne peut apparaître qu'une seule fois dans un panier donné
        // Si le client veut plus du même produit, on augmente la quantité au lieu de créer une nouvelle ligne
        Schema::table('cart_lines', function (Blueprint $table) {
            $table->unique(['cart_id', 'product_id'], 'unique_cart_product');
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_lines');
    }
};