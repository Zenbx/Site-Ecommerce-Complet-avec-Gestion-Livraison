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
         DB::statement("DROP TYPE IF EXISTS payment_method_enum");
        // Créer le type ENUM pour les méthodes de paiement
        DB::statement("CREATE TYPE payment_method_enum AS ENUM ('MOMO', 'OM', 'CASH')");
        
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Référence à la commande
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('restrict');
            
            // Colonne payment_method SANS valeur par défaut
            // Note : cette colonne n'aura jamais de valeur par défaut car la méthode
            // de paiement doit toujours être spécifiée explicitement
            $table->string('payment_method');
            
            $table->decimal('amount', 10, 2);
            $table->string('transaction_reference', 255)->unique()->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        
        // Conversion en ENUM
        // Pas besoin de SET DEFAULT ici car payment_method est toujours requis
        DB::statement('ALTER TABLE payments ALTER COLUMN payment_method TYPE payment_method_enum USING payment_method::payment_method_enum');
        
        // Contrainte CHECK
        DB::statement('ALTER TABLE payments ADD CONSTRAINT check_payment_amount CHECK (amount > 0)');
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        DB::statement('DROP TYPE IF EXISTS payment_method_enum CASCADE');
    }
};