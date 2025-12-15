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
        DB::statement("DROP TYPE IF EXISTS delivery_status_enum");
        // Créer le type ENUM pour les statuts de livraison
        DB::statement("CREATE TYPE delivery_status_enum AS ENUM ('PENDING', 'ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'FAILED')");
        
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            
            // Référence à la commande
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('restrict');
            
            // Référence au livreur
            $table->unsignedBigInteger('delivery_person_id')->nullable();
            $table->foreign('delivery_person_id')
                  ->references('id')
                  ->on('delivery_persons')
                  ->onDelete('set null');
            
            $table->text('delivery_address');
            
            // Colonne status SANS valeur par défaut
            $table->string('status');
            
            $table->string('tracking_code', 100)->unique();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            // Champs pour le système de QR Code
            $table->string('qr_token', 255)->unique()->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->timestamp('qr_scanned_at')->nullable();
            $table->string('qr_status', 20)->nullable();
            
            // Preuve de livraison
            $table->string('confirmation_img_url', 500)->nullable();
        });
        
        // Conversion en ENUM
        DB::statement('ALTER TABLE deliveries ALTER COLUMN status TYPE delivery_status_enum USING status::delivery_status_enum');
        
        // Ajout de la valeur par défaut APRÈS la conversion
        DB::statement("ALTER TABLE deliveries ALTER COLUMN status SET DEFAULT 'PENDING'::delivery_status_enum");
        
        // Index
        Schema::table('deliveries', function (Blueprint $table) {
            $table->index('tracking_code', 'idx_delivery_tracking');
            $table->index('status', 'idx_delivery_status');
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
        DB::statement('DROP TYPE IF EXISTS delivery_status_enum CASCADE');
    }
};