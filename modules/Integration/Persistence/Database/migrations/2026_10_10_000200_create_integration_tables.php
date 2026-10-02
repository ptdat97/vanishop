<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integration platform (ADR-005, ADR-013, ADR-014). Xem docs/11-integration/integration-platform.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_clients', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();          // erp-main, odo, pos-kiotviet — cũng là giá trị `stock_authority`
            $table->string('name');
            $table->string('status', 16)->default('active'); // active | suspended
            $table->json('scopes');                          // ["orders:read", "inventory:write", …]
            $table->json('ip_allowlist')->nullable();
            $table->unsignedInteger('rate_limit')->default(600); // request/phút
            $table->timestamps();
        });

        // Tối đa 2 key còn hiệu lực song song để xoay vòng. Secret mã hoá bằng APP_KEY (cần để kiểm HMAC).
        Schema::create('integration_client_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('integration_clients')->cascadeOnDelete();
            $table->string('key_id', 40)->unique();
            $table->text('secret');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('integration_webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('integration_clients')->cascadeOnDelete();
            $table->string('url', 500);
            $table->json('event_types');                     // ["order.created", …] hoặc ["*"]
            $table->text('secret');
            $table->string('status', 16)->default('active'); // active | paused
            $table->timestamp('failing_since')->nullable();
            $table->timestamps();
        });

        // Event feed append-only: nguồn cho GET /events?after= và fan-out ra outbox.
        Schema::create('integration_events', function (Blueprint $table) {
            $table->id();                                    // con trỏ feed
            $table->uuid('event_id')->unique();
            $table->string('event_type', 64);
            $table->string('schema_version', 16);
            $table->string('aggregate_type', 32);
            $table->string('aggregate_id', 64);
            $table->json('payload');
            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('occurred_at');

            $table->index(['event_type', 'id']);
        });

        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();                                    // thứ tự gửi trong cùng aggregate
            $table->uuid('message_id')->unique();            // Idempotency-Key gửi đi; replay giữ nguyên
            $table->uuid('event_id')->nullable()->index();
            $table->string('target', 96);                    // webhook:<subscription id> | mã connector
            $table->string('message_type', 64);
            $table->string('schema_version', 16);
            $table->string('aggregate_type', 32);
            $table->string('aggregate_id', 64);
            $table->json('payload');
            $table->string('status', 16)->default('pending'); // pending | processing | sent | failed | dead
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->text('last_error')->nullable();
            $table->string('external_id', 128)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->index(['status', 'next_attempt_at']);
            $table->index(['target', 'aggregate_type', 'aggregate_id', 'id'], 'integration_outbox_aggregate_index');
        });

        Schema::create('integration_inbox', function (Blueprint $table) {
            $table->id();
            $table->string('system', 64);
            $table->string('external_event_id', 128);
            $table->string('message_type', 64);
            $table->json('payload');
            $table->string('status', 16)->default('received'); // received | processing | processed | failed | dead | ignored_stale
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();

            $table->unique(['system', 'external_event_id']);
            $table->index(['status', 'next_attempt_at']);
        });

        Schema::create('external_references', function (Blueprint $table) {
            $table->id();
            $table->string('system', 64);
            $table->string('entity_type', 32);               // order | payment | shipment | variant | customer …
            $table->string('internal_id', 64);
            $table->string('external_id', 128);
            $table->timestamps();

            $table->unique(['system', 'entity_type', 'internal_id']);
            $table->index(['system', 'entity_type', 'external_id']);
        });

        Schema::create('integration_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('system', 64);
            $table->string('type', 32);                      // warehouse | payment_method | order_status | province …
            $table->string('internal_value', 128);
            $table->string('external_value', 128);
            $table->timestamps();

            $table->unique(['system', 'type', 'internal_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_mappings');
        Schema::dropIfExists('external_references');
        Schema::dropIfExists('integration_inbox');
        Schema::dropIfExists('integration_outbox');
        Schema::dropIfExists('integration_events');
        Schema::dropIfExists('integration_webhook_subscriptions');
        Schema::dropIfExists('integration_client_keys');
        Schema::dropIfExists('integration_clients');
    }
};
