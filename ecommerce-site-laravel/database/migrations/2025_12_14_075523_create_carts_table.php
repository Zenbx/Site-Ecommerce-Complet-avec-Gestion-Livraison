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
        // Créer le type ENUM pour les statuts de panier
        DB::statement("CREATE TYPE cart_status_enum AS ENUM ('ACTIVE', 'ABANDONED', 'CONVERTED')");
        
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            
            // Référence au client propriétaire du panier
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')
                  ->references('id')
                  ->on('clients')
                  ->onDelete('cascade');
            
            // Colonne status créée SANS valeur par défaut
            // C'est crucial pour éviter l'erreur de conversion
            $table->string('status');
            
            $table->timestamps();
        });
        
        // Maintenant nous convertissons la colonne en type ENUM
        DB::statement('ALTER TABLE carts ALTER COLUMN status TYPE cart_status_enum USING status::cart_status_enum');
        
        // Et MAINTENANT nous pouvons ajouter la valeur par défaut
        // PostgreSQL comprend maintenant que 'ACTIVE' est une valeur valide du type cart_status_enum
        DB::statement("ALTER TABLE carts ALTER COLUMN status SET DEFAULT 'ACTIVE'::cart_status_enum");
        
        // Créer des index pour améliorer les performances
        Schema::table('carts', function (Blueprint $table) {
            $table->index('client_id', 'idx_cart_client');
            $table->index('status', 'idx_cart_status');
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
        DB::statement('DROP TYPE IF EXISTS cart_status_enum CASCADE');
    }
};