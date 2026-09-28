<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('carrier_code', 64);
            $table->string('service_code', 64)->nullable();
            $table->string('tracking_number', 64)->nullable();
            $table->string('label_url')->nullable();
            $table->bigInteger('cod_amount')->default(0);
            $table->char('currency_code', 3);
            $table->string('status', 24);
            $table->unsignedSmallInteger('booking_attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            // Unique khi có mã vận đơn (NULL không vi phạm unique ở MySQL/SQLite).
            $table->unique(['carrier_code', 'tracking_number']);
            $table->index(['brand_id', 'status']);
            $table->index(['status', 'delivered_at']);
        });

        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        // Append-only; unique chống webhook trùng.
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24);
            $table->string('event_id', 128);
            $table->string('source', 64);
            $table->string('description')->nullable();
            $table->json('payload_masked')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at', 6);

            $table->unique(['shipment_id', 'event_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE shipment_lines ADD CONSTRAINT shipment_lines_quantity_positive CHECK (quantity > 0)');
            DB::statement('ALTER TABLE shipments ADD CONSTRAINT shipments_cod_not_negative CHECK (cod_amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipment_lines');
        Schema::dropIfExists('shipments');
    }
};
