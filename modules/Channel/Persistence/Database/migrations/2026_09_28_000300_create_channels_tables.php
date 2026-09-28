<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('type', 16);
            $table->string('locale', 8)->default('vi');
            $table->char('currency_code', 3)->default('VND');
            $table->string('theme', 64)->default('vani-base');
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        Schema::create('channel_brands', function (Blueprint $table) {
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->primary(['channel_id', 'brand_id']);
        });

        Schema::create('channel_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('host');
            $table->string('path_prefix', 64)->default('');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['host', 'path_prefix']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_domains');
        Schema::dropIfExists('channel_brands');
        Schema::dropIfExists('channels');
    }
};
