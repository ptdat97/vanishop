<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cấu hình theo phạm vi, kế thừa kênh → brand → pháp nhân → owner (docs/12-multi-brand/multi-brand.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 16);          // owner | legal_entity | brand | channel
            $table->unsignedBigInteger('scope_id')->default(0); // 0 = owner
            $table->string('namespace', 64);           // core | <plugin id>
            $table->string('key', 96);
            $table->text('value');                      // JSON; mã hoá (APP_KEY) khi encrypted
            $table->boolean('encrypted')->default(false);
            $table->timestamps();

            $table->unique(['namespace', 'key', 'scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
