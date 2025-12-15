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
            $table->id();

            // Nom du produit
            $table->string('name', 255);

            // Quantité en stock
            $table->unsignedInteger('quantity')->default(0);

            // Prix
            $table->decimal('price', 10, 2);

            // Identifiant série unique
            $table->string('serial_id', 100)->unique();

            // Description
            $table->text('description')->nullable();

            // Marque
            $table->string('brand', 255)->nullable();

            // Relation avec categories (UN produit → UNE catégorie)
            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Image
            $table->string('image_url', 500)->nullable();

            // Statut
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Index pour les recherches fréquentes
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id', 'idx_product_category');
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
