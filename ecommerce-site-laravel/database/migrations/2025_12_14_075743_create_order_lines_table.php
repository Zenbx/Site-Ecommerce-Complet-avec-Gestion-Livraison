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
        Schema::create('order_lines', function (Blueprint $table) {
            // Clé primaire
            $table->id();
            
            // Référence à la commande parente
            // onDelete('cascade') : si la commande est supprimée, ses lignes aussi
            // En pratique, on ne supprimera jamais les commandes, mais cette règle est là au cas où
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
            
            // Référence au produit
            // onDelete('restrict') : on ne peut pas supprimer un produit qui a été commandé
            // Important pour l'historique : même si un produit n'est plus vendu,
            // on doit pouvoir voir ce qui a été commandé dans le passé
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('restrict');
            
            // Prix unitaire au moment de la commande
            // Crucial car les prix changent avec le temps
            $table->decimal('unit_price', 10, 2);
            
            // Quantité commandée
            $table->unsignedInteger('quantity');
        });
        
        // Contraintes CHECK
        DB::statement('ALTER TABLE order_lines ADD CONSTRAINT check_order_line_price CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE order_lines ADD CONSTRAINT check_order_line_quantity CHECK (quantity > 0)');
        
        // Contrainte UNIQUE composite
        // Un même produit ne peut apparaître qu'une fois dans une commande donnée
        Schema::table('order_lines', function (Blueprint $table) {
            $table->unique(['order_id', 'product_id'], 'unique_order_product');
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};