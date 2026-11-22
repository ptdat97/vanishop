<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('delivery_failed_at')->nullable();
            $table->index(['status', 'delivery_failed_at']);
        });

        $failed = ['returning', 'returned'];
        $firstEvent = DB::table('shipment_events')->whereColumn('shipment_events.shipment_id', 'shipments.id')
            ->whereIn('shipment_events.status', $failed)->selectRaw('min(shipment_events.occurred_at)');
        DB::table('shipments')->whereIn('status', $failed)->update(['delivery_failed_at' => $firstEvent]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['status', 'delivery_failed_at']);
            $table->dropColumn('delivery_failed_at');
        });
    }
};
