<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
| Lưu giữ dữ liệu (db-refactor G3): log kỹ thuật cũ bị xoá, message cần phát lại và dòng mới giữ lại; nhật ký thao tác cũ
| chuyển sang audit_logs_archive (không mất).
*/

it('integration: xoá sự kiện, outbox đã gửi, inbox đã xử lý cũ hơn 3 tháng; giữ message dead và dòng mới', function () {
    $old = now()->subMonths(4);
    $new = now()->subMonth();
    foreach ([[$old, 'sent'], [$old, 'dead'], [$new, 'sent']] as [$at, $status]) {
        DB::table('integration_outbox')->insert(['message_id' => (string) Str::ulid(), 'target' => 'erp', 'message_type' => 'order.placed', 'schema_version' => 1, 'aggregate_type' => 'order', 'aggregate_id' => '1',
            'payload' => '{}', 'status' => $status, 'attempts' => 0, 'created_at' => $at]);
    }
    foreach ([[$old, 'processed'], [$old, 'failed'], [$new, 'processed']] as [$at, $status]) {
        DB::table('integration_inbox')->insert(['system' => 'erp', 'external_event_id' => (string) Str::ulid(), 'message_type' => 'stock.level', 'payload' => '{}',
            'status' => $status, 'attempts' => 0, 'received_at' => $at]);
    }
    foreach ([$old, $new] as $at) {
        DB::table('integration_events')->insert(['event_id' => (string) Str::ulid(), 'event_type' => 'order.placed', 'schema_version' => 1, 'aggregate_type' => 'order',
            'aggregate_id' => '1', 'payload' => '{}', 'occurred_at' => $at]);
    }

    $this->artisan('vani:integration:prune')->assertSuccessful();

    expect(DB::table('integration_outbox')->orderBy('id')->pluck('status')->all())->toBe(['dead', 'sent'])
        ->and(DB::table('integration_inbox')->orderBy('id')->pluck('status')->all())->toBe(['failed', 'processed'])
        ->and(DB::table('integration_events')->count())->toBe(1);
});

it('notification: xoá nhật ký gửi tin cũ hơn 6 tháng', function () {
    foreach ([now()->subMonths(7), now()->subMonths(5)] as $at) {
        DB::table('notification_logs')->insert(['idempotency_key' => (string) Str::ulid(), 'type' => 'order_placed', 'category' => 'transactional', 'channel' => 'mail',
            'recipient' => 'a@example.com', 'status' => 'sent', 'attempts' => 1, 'created_at' => $at]);
    }

    $this->artisan('vani:notification:prune')->assertSuccessful();

    expect(DB::table('notification_logs')->count())->toBe(1);
});

it('audit: chuyển nhật ký cũ hơn 24 tháng sang archive, giữ nguyên id và nội dung', function () {
    $oldId = DB::table('audit_logs')->insertGetId(['actor_type' => 'staff', 'actor_id' => 1, 'action' => 'catalog.product.updated', 'changes' => '{"a":1}', 'created_at' => now()->subMonths(25)]);
    DB::table('audit_logs')->insert(['actor_type' => 'staff', 'actor_id' => 1, 'action' => 'catalog.product.updated', 'created_at' => now()->subMonth()]);

    $this->artisan('vani:audit:archive')->assertSuccessful();

    expect(DB::table('audit_logs')->count())->toBe(1)
        ->and(DB::table('audit_logs_archive')->sole())->toMatchObject(['id' => $oldId, 'action' => 'catalog.product.updated', 'changes' => '{"a":1}']);
});
