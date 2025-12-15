<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Nom de la catégorie
            $table->string('name', 150);

            // Slug SEO unique
            $table->string('slug', 180)->unique();

            // Description optionnelle
            $table->text('description')->nullable();

            // Image illustrative
            $table->string('image_url', 500)->nullable();

            // Statut actif/inactif
            $table->boolean('is_active')->default(true);

            // Auto-relation : catégorie parente (sous-catégories)
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();

            $table->timestamps();
        });

        // Index utiles
        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id', 'idx_category_parent');
            $table->index('is_active', 'idx_category_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
