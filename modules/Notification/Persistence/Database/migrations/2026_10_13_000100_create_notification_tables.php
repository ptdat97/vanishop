<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notification (docs/03-domains/notification.md): template theo loại tin × kênh, nhật ký gửi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);                         // order_placed, shipment_delivered…
            $table->string('channel', 32);                      // mail | sms | zns | …
            $table->string('locale', 8)->default('vi');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->json('meta')->nullable();                   // tham số riêng của kênh (vd. ZNS template_id + params)
            $table->boolean('active')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['type', 'channel', 'locale']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 160)->unique(); // listener chạy lại không gửi trùng (R18)
            $table->string('type', 64);
            $table->string('category', 16);                    // transactional | marketing
            $table->string('channel', 32);
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('recipient', 190);                  // SĐT E.164 hoặc email
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->string('status', 16);                      // queued | sent | failed | skipped
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_message_id', 128)->nullable();
            $table->string('error', 500)->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('sent_at')->nullable();

            $table->index(['status', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        // Mẫu email mặc định (tiếng Việt). SMS/ZNS do Admin tạo khi bật plugin (ZNS cần template đã được Zalo duyệt).
        $now = now();
        DB::table('notification_templates')->insert(array_map(fn (array $row): array => [
            ...$row, 'channel' => 'mail', 'locale' => 'vi', 'meta' => null, 'active' => true,
            'lock_version' => 0, 'created_at' => $now, 'updated_at' => $now,
        ], [
            ['type' => 'order_placed', 'subject' => '{{ store_name }}: đã nhận đơn {{ order_number }}',
                'body' => "Chào {{ customer_name }},\n\n{{ store_name }} đã nhận đơn {{ order_number }}, tổng {{ total }}.\nChúng tôi sẽ báo khi đơn được giao cho đơn vị vận chuyển.\n\nCảm ơn bạn đã mua sắm!"],
            ['type' => 'order_cancelled', 'subject' => '{{ store_name }}: đơn {{ order_number }} đã huỷ',
                'body' => "Chào {{ customer_name }},\n\nĐơn {{ order_number }} đã được huỷ. Nếu bạn đã thanh toán, tiền sẽ được hoàn theo phương thức ban đầu.\n\n{{ store_name }}"],
            ['type' => 'shipment_shipped', 'subject' => '{{ store_name }}: đơn {{ order_number }} đang được giao',
                'body' => "Chào {{ customer_name }},\n\nĐơn {{ order_number }} đã được giao cho {{ carrier }}. Mã vận đơn: {{ tracking_number }}.\n\n{{ store_name }}"],
            ['type' => 'shipment_delivered', 'subject' => '{{ store_name }}: đơn {{ order_number }} đã giao thành công',
                'body' => "Chào {{ customer_name }},\n\nĐơn {{ order_number }} đã giao thành công. Cảm ơn bạn và hẹn gặp lại!\n\n{{ store_name }}"],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
