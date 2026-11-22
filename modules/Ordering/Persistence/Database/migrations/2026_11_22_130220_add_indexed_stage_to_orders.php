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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stage', 24)->virtualAs(<<<'SQL'
                CASE
                    WHEN order_status = 'cancelled' THEN 'cancelled'
                    WHEN return_status IN ('requested', 'in_progress') THEN 'returns'
                    WHEN order_status = 'completed' THEN 'completed'
                    WHEN fulfillment_status = 'returned_to_sender' OR delivery_issue IS NOT NULL THEN 'issue'
                    WHEN order_status = 'pending' AND payment_status IN ('unpaid', 'failed') THEN 'awaiting_payment'
                    WHEN order_status = 'pending' THEN 'to_confirm'
                    WHEN fulfillment_status = 'delivered' THEN 'delivered'
                    WHEN fulfillment_status IN ('shipped', 'partially_shipped') THEN 'shipping'
                    ELSE 'to_ship'
                END
                SQL);
            $table->index(['stage', 'placed_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['stage', 'placed_at', 'id']);
            $table->dropColumn('stage');
        });
    }
};
