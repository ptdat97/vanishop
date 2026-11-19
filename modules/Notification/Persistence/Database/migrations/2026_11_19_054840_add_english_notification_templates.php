<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mẫu mail tiếng Anh mặc định cho các loại tin của Core (ADR-049, backlog L2): khách đặt hàng bằng tiếng Anh nhận tin
 * tiếng Anh thay vì bản tiếng Việt. Chỉ thêm khi cửa hàng chưa tự tạo mẫu `en` cho loại tin đó.
 */
return new class extends Migration
{
    private const TEMPLATES = [
        'order_placed' => ['{{ store_name }}: order {{ order_number }} received',
            "Hi {{ customer_name }},\n\n{{ store_name }} has received order {{ order_number }}, total {{ total }}.\nWe will let you know when the order is handed to the carrier.\n\nThank you for shopping with us!"],
        'order_cancelled' => ['{{ store_name }}: order {{ order_number }} cancelled',
            "Hi {{ customer_name }},\n\nOrder {{ order_number }} has been cancelled. If you have paid, the money will be refunded to your original payment method.\n\n{{ store_name }}"],
        'shipment_shipped' => ['{{ store_name }}: order {{ order_number }} is on its way',
            "Hi {{ customer_name }},\n\nOrder {{ order_number }} has been handed to {{ carrier }}. Tracking number: {{ tracking_number }}.\n\n{{ store_name }}"],
        'shipment_delivered' => ['{{ store_name }}: order {{ order_number }} delivered',
            "Hi {{ customer_name }},\n\nOrder {{ order_number }} has been delivered. Thank you, and see you again!\n\n{{ store_name }}"],
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::TEMPLATES as $type => [$subject, $body]) {
            $exists = DB::table('notification_templates')->where('type', $type)->where('channel', 'mail')->where('locale', 'en')->exists();
            if (! $exists) {
                DB::table('notification_templates')->insert([
                    'type' => $type, 'channel' => 'mail', 'locale' => 'en', 'subject' => $subject, 'body' => $body, 'meta' => null,
                    'active' => true, 'lock_version' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::TEMPLATES as $type => [$subject]) {
            DB::table('notification_templates')->where('type', $type)->where('channel', 'mail')->where('locale', 'en')->where('subject', $subject)->delete();
        }
    }
};
