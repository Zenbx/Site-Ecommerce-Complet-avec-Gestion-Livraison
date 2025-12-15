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
        DB::statement("DROP TYPE IF EXISTS order_status_enum");

        // Créer les deux types ENUM nécessaires pour les commandes
        DB::statement("CREATE TYPE order_status_enum AS ENUM ('PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED')");
        
        DB::statement("DROP TYPE IF EXISTS payment_status_enum");
        
        DB::statement("CREATE TYPE payment_status_enum AS ENUM ('PENDING', 'COMPLETED', 'FAILED', 'REFUNDED')");
        
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Référence au client
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')
                  ->references('id')
                  ->on('clients')
                  ->onDelete('restrict');
            
            $table->decimal('total_amount', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            
            // Les deux colonnes de statut créées SANS valeurs par défaut
            $table->string('status');
            $table->string('payment_status');
            
            $table->timestamps();
        });
        
        // Conversions en types ENUM
        DB::statement('ALTER TABLE orders ALTER COLUMN status TYPE order_status_enum USING status::order_status_enum');
        DB::statement('ALTER TABLE orders ALTER COLUMN payment_status TYPE payment_status_enum USING payment_status::payment_status_enum');
        
        // Ajout des valeurs par défaut APRÈS les conversions
        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'PENDING'::order_status_enum");
        DB::statement("ALTER TABLE orders ALTER COLUMN payment_status SET DEFAULT 'PENDING'::payment_status_enum");
        
        // Contraintes CHECK
        DB::statement('ALTER TABLE orders ADD CONSTRAINT check_order_total_amount CHECK (total_amount >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT check_order_delivery_fee CHECK (delivery_fee >= 0)');
        
        // Index
        Schema::table('orders', function (Blueprint $table) {
            $table->index('client_id', 'idx_order_client');
            $table->index('status', 'idx_order_status');
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        DB::statement('DROP TYPE IF EXISTS order_status_enum CASCADE');
        DB::statement('DROP TYPE IF EXISTS payment_status_enum CASCADE');
    }
};