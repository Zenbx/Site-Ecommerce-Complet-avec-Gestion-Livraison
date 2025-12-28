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
        Schema::table('delivery_persons', function (Blueprint $table) {
            // Coordinates
            $table->decimal('current_latitude', 10, 8)->nullable()->after('is_available');
            $table->decimal('current_longitude', 11, 8)->nullable()->after('current_latitude');
            
            // Status
            $table->boolean('is_online')->default(false)->after('current_longitude');
            $table->timestamp('last_location_update')->nullable()->after('is_online');
            
            // Optional: for tracking history
            $table->string('current_address')->nullable()->after('last_location_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_persons', function (Blueprint $table) {
            $table->dropColumn([
                'current_latitude',
                'current_longitude',
                'is_online',
                'last_location_update',
                'current_address'
            ]);
        });
    }
};