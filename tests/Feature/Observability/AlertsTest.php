<?php

use App\Observability\AlertManager;
use App\Observability\AlertRules;
use App\Observability\OpenAlertsWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Contracts\AlertChannel;
use Modules\Shared\Contracts\Data\Alert;

require_once __DIR__.'/../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Phase 6: cảnh báo tự động — vani:alerts:check đánh giá điều kiện, báo qua AlertChannel, không gửi lặp, nhắc lại, báo hết.
*/

final class RecordingAlertChannel implements AlertChannel
{
    /** @var list<Alert> */
    public static array $sent = [];

    public function code(): string
    {
        return 'recording';
    }

    public function send(Alert $alert): void
    {
        self::$sent[] = $alert;
    }
}

final class BrokenAlertChannel implements AlertChannel
{
    public function code(): string
    {
        return 'broken';
    }

    public function send(Alert $alert): void
    {
        throw new RuntimeException('kênh hỏng');
    }
}

function deadOrderMessage(): void
{
    DB::table('integration_outbox')->insert([
        'message_id' => (string) Str::uuid(), 'target' => 'webhook:1', 'message_type' => 'order.created', 'schema_version' => '1',
        'aggregate_type' => 'order', 'aggregate_id' => 'VN0001', 'payload' => '{}', 'status' => 'dead', 'attempts' => 10, 'created_at' => now(),
    ]);
}

/** @return list<string> */
function sentStates(string $key): array
{
    return array_values(array_map(fn (Alert $alert): string => $alert->state, array_filter(RecordingAlertChannel::$sent, fn (Alert $alert): bool => $alert->key === $key)));
}

beforeEach(function () {
    RecordingAlertChannel::$sent = [];
    app(Extensions::class)->tag([BrokenAlertChannel::class, RecordingAlertChannel::class], AlertChannel::TAG);
    config(['mail.default' => 'array', 'vanishop.alerts.mail_to' => ['ops@vanishop.test', 'owner@vanishop.test']]);
});

it('sự cố mới → báo ngay (kênh lỗi không chặn kênh khác); còn → không gửi lặp, nhắc lại theo mức độ; hết → báo đã ổn', function () {
    $this->freezeTime();
    deadOrderMessage();

    $this->artisan('vani:alerts:check')->expectsOutputToContain('[KHẨN] Message order.* vào dead')->assertSuccessful();
    expect(sentStates('integration.order_event_dead'))->toBe([Alert::FIRING]);

    $this->travel(5)->minutes();
    $this->artisan('vani:alerts:check')->expectsOutputToContain('Không có cảnh báo cần gửi.')->assertSuccessful();
    expect(sentStates('integration.order_event_dead'))->toBe([Alert::FIRING]);

    $this->travel(26)->minutes(); // khẩn: nhắc lại sau 30 phút
    $this->artisan('vani:alerts:check')->assertSuccessful();
    expect(sentStates('integration.order_event_dead'))->toBe([Alert::FIRING, Alert::REMINDER])
        ->and(DB::table('alert_states')->where('key', 'integration.order_event_dead')->value('notify_count'))->toBe(2);

    DB::table('integration_outbox')->update(['status' => 'sent']);
    $this->artisan('vani:alerts:check')->expectsOutputToContain('[ĐÃ ỔN] [KHẨN]')->assertSuccessful();
    $this->artisan('vani:alerts:check')->assertSuccessful();
    expect(sentStates('integration.order_event_dead'))->toBe([Alert::FIRING, Alert::REMINDER, Alert::RESOLVED]);

    // Phát sinh lại sau khi đã ổn → báo như sự cố mới.
    deadOrderMessage();
    $this->artisan('vani:alerts:check')->assertSuccessful();
    expect(sentStates('integration.order_event_dead'))->toBe([Alert::FIRING, Alert::REMINDER, Alert::RESOLVED, Alert::FIRING]);
});

it('kênh email của Core gửi ngay tới VANI_ALERT_EMAILS; --dry-run không gửi, không lưu trạng thái', function () {
    deadOrderMessage();

    $this->artisan('vani:alerts:check --dry-run')->expectsOutputToContain('Message order.* vào dead')->assertSuccessful();
    expect(DB::table('alert_states')->count())->toBe(0)
        ->and(RecordingAlertChannel::$sent)->toBe([])
        ->and(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(0);

    $this->artisan('vani:alerts:check')->assertSuccessful();
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $email = $messages->first()->getOriginalMessage();
    expect($email->getSubject())->toContain('[KHẨN] Message order.* vào dead')
        ->and(array_map(fn ($address) => $address->getAddress(), $email->getTo()))->toBe(['ops@vanishop.test', 'owner@vanishop.test'])
        ->and($email->getTextBody())->toContain('Admin → Tích hợp');
});

it('tỷ lệ thanh toán online thất bại: bỏ COD/chuyển khoản tay, cần đủ mẫu 60 phút gần nhất', function () {
    ['s' => $variant] = C::store(stock: 2);
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $variant->id, 'quantity' => 1], $headers);
    $orderNumber = $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'alerts-order-0001'])
        ->assertCreated()->json('data.number');
    $orderId = DB::table('orders')->where('number', $orderNumber)->value('id');
    $payments = function (string $gateway, string $status, int $count, int $minutesAgo = 0) use ($orderId): void {
        foreach (range(1, $count) as $_) {
            DB::table('payments')->insert([
                'public_id' => (string) Str::ulid(), 'order_id' => $orderId, 'gateway_code' => $gateway, 'amount' => 330_000, 'currency_code' => 'VND',
                'status' => $status, 'created_at' => now()->subMinutes($minutesAgo), 'updated_at' => now()->subMinutes($minutesAgo),
            ]);
        }
    };
    $rule = fn () => app(AlertRules::class)->evaluate()['payments.failure_rate'];

    $payments('vnpay', 'failed', 4);
    $payments('vnpay', 'paid', 5);
    $payments('cod', 'failed', 20);
    $payments('vnpay', 'failed', 20, minutesAgo: 90);
    expect($rule())->toBeNull(); // 9 khoản online trong 60 phút < mẫu tối thiểu 10

    $payments('vnpay', 'paid', 1);
    expect($rule())->toMatchArray(['severity' => Alert::CRITICAL])->and($rule()['detail'])->toContain('4/10');

    $payments('vnpay', 'paid', 4); // 4/14 ≈ 29% ≤ 30%
    expect($rule())->toBeNull();
});

it('ô "Cảnh báo vận hành" liệt kê cảnh báo đang mở, mức nặng trước', function () {
    deadOrderMessage();
    app(AlertManager::class)->run();

    $table = app(OpenAlertsWidget::class)->render()->toArray();
    expect($table['rows'])->toHaveCount(1)
        ->and($table['rows'][0])->toMatchArray(['level' => 'Khẩn', 'title' => 'Message order.* vào dead']);
});
