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
    Schema::table('deliveries', function (Blueprint $table) {
        $table->string('qr_path', 500)->nullable()->after('qr_token');
    });
}

/**
     * Reverse the migrations.
     */

public function down(): void
{
    Schema::table('deliveries', function (Blueprint $table) {
        $table->dropColumn('qr_path');
    });
}
};
