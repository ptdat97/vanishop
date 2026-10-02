<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('type', 16);                                 // warehouse | store | virtual
            $table->string('address')->nullable();
            $table->string('province_code', 16)->nullable();
            $table->boolean('ships_online_orders')->default(true);
            $table->boolean('allows_pickup')->default(false);
            $table->boolean('accepts_returns')->default(false);
            // "vanishop" = VaniShop quản lý tồn vật lý; ngược lại là mã Integration Client (ERP/POS/ODO).
            $table->string('stock_authority', 64)->default('vanishop');
            $table->integer('priority')->default(0);
            $table->string('status', 16)->default('active');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->constrained()->restrictOnDelete();
            $table->integer('on_hand')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('safety_stock')->default(0);
            $table->unsignedBigInteger('sync_version')->nullable();
            $table->timestamps();
            $table->unique(['location_id', 'variant_id']);
            $table->index('variant_id');
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_key', 128);                    // ví dụ "order:123", "checkout:<token>"
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status', 16)->default('active');           // active | committed | released
            $table->dateTime('expires_at')->nullable();
            $table->string('release_reason', 64)->nullable();
            $table->timestamps();
            $table->index(['reservation_key', 'status']);
            $table->index(['status', 'expires_at']);
        });

        // Sổ biến động append-only.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('variant_id');
            $table->string('type', 16);
            $table->integer('on_hand_delta')->default(0);
            $table->integer('reserved_delta')->default(0);
            $table->integer('on_hand_after');
            $table->integer('reserved_after');
            $table->string('reason')->nullable();
            $table->string('reference', 128)->nullable();
            $table->string('actor_type', 16)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->dateTime('created_at', 6);
            $table->index(['variant_id', 'created_at']);
            $table->index(['location_id', 'created_at']);
            $table->index('reference');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT stock_reservations_quantity_positive CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        foreach (['stock_movements', 'stock_reservations', 'stock_levels', 'locations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
