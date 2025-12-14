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
        // ÉTAPE 1 : Créer le type ENUM pour les rôles d'admin
        DB::statement("CREATE TYPE admin_role AS ENUM ('GESTIONNAIRE', 'SUPERVISEUR', 'ADMIN')");
        
        // ÉTAPE 2 : Créer la table admins avec la colonne role comme simple string SANS valeur par défaut
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            
            // On crée la colonne role comme string SANS default pour l'instant
            $table->string('role');
            
            $table->timestamps();
        });
        
        // ÉTAPE 3 : Convertir la colonne role en type ENUM
        DB::statement('ALTER TABLE admins ALTER COLUMN role TYPE admin_role USING role::admin_role');
        
        // ÉTAPE 4 : Maintenant que la colonne est de type ENUM, on peut ajouter la valeur par défaut
        // Cette fois, PostgreSQL comprend que 'ADMIN' est une valeur valide du type admin_role
        DB::statement("ALTER TABLE admins ALTER COLUMN role SET DEFAULT 'ADMIN'::admin_role");
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
        DB::statement('DROP TYPE IF EXISTS admin_role CASCADE');
    }
};