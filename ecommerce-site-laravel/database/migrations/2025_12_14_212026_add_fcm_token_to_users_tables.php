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
    Schema::table('clients', function (Blueprint $table) {
        $table->string('fcm_token', 255)->nullable()->after('password');
    });
    
    Schema::table('delivery_persons', function (Blueprint $table) {
        $table->string('fcm_token', 255)->nullable()->after('password');
    });
    
    Schema::table('admins', function (Blueprint $table) {
        $table->string('fcm_token', 255)->nullable()->after('password');
    });
}

public function down(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->dropColumn('fcm_token');
    });
    
    Schema::table('delivery_persons', function (Blueprint $table) {
        $table->dropColumn('fcm_token');
    });
    
    Schema::table('admins', function (Blueprint $table) {
        $table->dropColumn('fcm_token');
    });
}
};
