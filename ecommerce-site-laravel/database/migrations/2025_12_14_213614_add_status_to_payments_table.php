<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('payments', function (Blueprint $table) {
        $table->string('status', 50)->default('PENDING')->after('transaction_reference');
        // PENDING, COMPLETED, FAILED, REFUND_PENDING, REFUNDED
    });
}

public function down(): void
{
    Schema::table('payments', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}
};
