<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->string('id', 128)->primary();
            $table->string('version', 32);
            $table->string('status', 16);
            $table->text('last_error')->nullable();
            $table->dateTime('installed_at');
            $table->timestamps();
        });

        Schema::create('plugin_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_id', 128);
            $table->string('scope_type', 32);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->foreign('plugin_id')->references('id')->on('plugins')->cascadeOnDelete();
            $table->unique(['plugin_id', 'scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_scopes');
        Schema::dropIfExists('plugins');
    }
};
