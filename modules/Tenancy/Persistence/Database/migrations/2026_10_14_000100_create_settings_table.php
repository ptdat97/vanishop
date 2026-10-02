<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cấu hình của cửa hàng (một cấp, ADR-028).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('namespace', 64);           // core | <plugin id>
            $table->string('key', 96);
            $table->text('value');                      // JSON; mã hoá (APP_KEY) khi encrypted
            $table->boolean('encrypted')->default(false);
            $table->timestamps();

            $table->unique(['namespace', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
