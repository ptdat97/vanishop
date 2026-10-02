<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Danh tính đăng nhập bên ngoài đã liên kết với khách (AuthProvider, ADR-030 §4.E).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('provider', 64);
            $table->string('subject', 191);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'subject']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_identities');
    }
};
