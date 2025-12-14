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
        Schema::create('delivery_persons', function (Blueprint $table) {
            // Clé primaire
            $table->id();
            
            // Nom du livreur
            $table->string('name', 255);
            
            // Email unique
            $table->string('email', 255)->unique();
            
            // Mot de passe
            $table->string('password', 255);
            
            // Numéro de carte d'identité
            // Ce champ est unique car chaque carte d'identité n'appartient qu'à une seule personne
            $table->string('id_card_number', 50)->unique();
            
            // Adresse personnelle du livreur
            $table->text('address');
            
            // URL de la photo du livreur
            // Nullable car la photo peut être ajoutée plus tard
            $table->string('photo_url', 500)->nullable();
            
            // Disponibilité du livreur
            // Par défaut TRUE, le livreur est disponible quand il est créé
            $table->boolean('is_available')->default(true);
            
            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_persons');
    }
};