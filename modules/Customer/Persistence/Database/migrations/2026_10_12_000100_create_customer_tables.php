<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer (docs/03-domains/customer.md): tài khoản hợp nhất toàn Owner, định danh chính là SĐT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('phone', 16)->nullable();        // E.164; null khi đã ẩn danh hoá
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 8)->nullable();         // female | male | other
            $table->string('status', 16)->default('active'); // active | merged | anonymized
            $table->timestamp('registered_at')->nullable();  // null = profile ẩn (khách vãng lai theo SĐT)
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->foreignId('merged_into_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('last_login_at')->nullable();
            // Thống kê mua (tính lại từ đơn, không tính đơn huỷ).
            $table->unsignedInteger('orders_count')->default(0);
            $table->bigInteger('total_spent')->default(0);    // VND, minor unit
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();

            // Invariant: một SĐT / một email chỉ thuộc tối đa một khách đang hoạt động.
            $table->string('phone_active', 16)->nullable()->storedAs("CASE WHEN status = 'active' THEN phone END")->unique();
            $table->string('email_normalized')->nullable()->storedAs("CASE WHEN status = 'active' THEN LOWER(email) END")->unique();
            $table->index(['status', 'registered_at']);
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 32)->nullable();          // Nhà, Công ty…
            $table->string('full_name');
            $table->string('phone', 16);
            $table->string('province_code', 8);
            $table->string('province_name', 64);
            $table->string('ward_code', 8);
            $table->string('ward_name', 64);
            $table->string('street_line');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 16);                    // email | sms | zns
            $table->string('purpose', 32);                    // marketing
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('source', 64);
            $table->timestamps();

            $table->unique(['customer_id', 'channel', 'purpose']);
        });

        // Ledger append-only: mọi lần cấp/rút consent.
        Schema::create('customer_consent_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('channel', 16);
            $table->string('purpose', 32);
            $table->string('action', 16);                     // granted | revoked
            $table->string('source', 64);
            $table->string('ip', 45)->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 16);
            $table->string('purpose', 32);
            $table->string('code_hash', 64);
            $table->string('channel', 16);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at');

            $table->index(['phone', 'purpose', 'created_at']);
        });

        // Token Bearer cho Storefront API (ADR-024): chỉ lưu sha256.
        Schema::create('customer_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('name', 64)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tokens');
        Schema::dropIfExists('customer_otps');
        Schema::dropIfExists('customer_consent_events');
        Schema::dropIfExists('customer_consents');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
