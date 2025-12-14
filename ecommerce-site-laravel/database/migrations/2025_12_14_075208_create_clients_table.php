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
        Schema::create('clients', function (Blueprint $table) {
            // Clé primaire
            $table->id();
            
            // Nom du client
            $table->string('name', 255);
            
            // Email unique pour l'authentification
            $table->string('email', 255)->unique();
            
            // Mot de passe hashé
            $table->string('password', 255);
            
            // Adresse de livraison par défaut du client
            // TEXT permet des adresses longues et détaillées
            $table->text('address');
            
            // Timestamps automatiques
            $table->timestamps();
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};