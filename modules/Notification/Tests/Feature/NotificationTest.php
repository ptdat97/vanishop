<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Application\ConsentService;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Notification\Application\ChannelRegistry;
use Modules\Notification\Application\SendNotificationJob;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Notification\Contracts\Notifier;
use Modules\Notification\Persistence\Models\NotificationLog;
use Modules\Notification\Persistence\Models\NotificationTemplate;
use Modules\Notification\Tests\Feature\Fixtures\FakeSmsChannel;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    FakeSmsChannel::$sent = [];
    FakeSmsChannel::$results = [];
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->placeOrder = function (string $key = 'notify-order-1', array $contact = []): Order {
        $api = '/api/storefront/v1';
        $headers = ['X-Vani-Channel' => 'web-lumiere'];
        $created = $this->postJson("{$api}/carts", [], $headers)->assertCreated();
        $headers['X-Vani-Cart-Token'] = $created->json('meta.token');
        $this->postJson("{$api}/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $number = $this->postJson("{$api}/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000, 'contact' => $contact]), [...$headers, 'Idempotency-Key' => $key])
            ->assertCreated()->json('data.number');

        return Order::query()->withoutGlobalScopes()->where('number', $number)->sole();
    };
    $this->mails = fn (): array => app('mailer')->getSymfonyTransport()->messages()->all();
});

it('đặt đơn → email "đã nhận đơn" theo mẫu mặc định, có nhật ký; event chạy lại không gửi trùng', function () {
    $order = ($this->placeOrder)();

    $mails = ($this->mails)();
    expect($mails)->toHaveCount(1);
    $email = $mails[0]->getOriginalMessage();
    expect($email->getTo()[0]->getAddress())->toBe('lan@example.com')
        ->and($email->getSubject())->toBe("{$this->brand->name}: đã nhận đơn {$order->number}")
        ->and($email->getTextBody())->toContain('Chào Nguyễn Thị Lan')->toContain('330.000');

    $log = NotificationLog::query()->sole();
    expect($log->status)->toBe('sent')->and($log->channel)->toBe('mail')->and($log->idempotency_key)->toBe("order_placed:{$order->id}:mail");

    OrderPlaced::dispatch($order->id, $order->public_id, $order->number, $order->brand_id, $order->channel_id, $order->customer_id, 330_000, 'VND');
    expect(NotificationLog::query()->count())->toBe(1)->and(($this->mails)())->toHaveCount(1);
});

it('mẫu của brand ghi đè mẫu mặc định; kênh plugin nhận tin theo SĐT; không có email thì không gửi mail', function () {
    app(Extensions::class)->tag([FakeSmsChannel::class], NotificationChannel::TAG);
    T::seed(fn () => NotificationTemplate::query()->create(['brand_id' => $this->brand->id, 'type' => 'order_placed', 'channel' => 'sms', 'body' => 'LUMIERE: da nhan don {{ order_number }}']));
    T::seed(fn () => NotificationTemplate::query()->create(['brand_id' => $this->brand->id, 'type' => 'order_placed', 'channel' => 'mail', 'subject' => 'Lumière cảm ơn', 'body' => 'Đơn {{ order_number }}']));

    $order = ($this->placeOrder)('notify-order-2', ['email' => '']);

    expect(FakeSmsChannel::$sent)->toHaveCount(1)
        ->and(FakeSmsChannel::$sent[0]->body)->toBe("LUMIERE: da nhan don {$order->number}")
        ->and(FakeSmsChannel::$sent[0]->recipient->phone)->toBe('+84912345678')
        ->and(($this->mails)())->toHaveCount(0)
        ->and(NotificationLog::query()->sole()->provider_message_id)->toStartWith('SMS-');
});

it('lỗi tạm thời → chờ thử lại; lỗi vĩnh viễn → failed', function () {
    app(Extensions::class)->tag([FakeSmsChannel::class], NotificationChannel::TAG);
    T::seed(fn () => NotificationTemplate::query()->create(['type' => 'order_placed', 'channel' => 'sms', 'body' => 'Don {{ order_number }}']));
    FakeSmsChannel::$results = [SendResult::retryable('http 503'), SendResult::permanent('invalid phone')];

    ($this->placeOrder)('notify-order-3');
    $log = NotificationLog::query()->where('channel', 'sms')->sole();
    expect($log->status)->toBe('queued')->and($log->attempts)->toBe(1)->and($log->error)->toBe('http 503');

    (new SendNotificationJob($log->id))->handle(app(ChannelRegistry::class));
    expect($log->fresh()->status)->toBe('failed')->and($log->fresh()->attempts)->toBe(2)->and($log->fresh()->error)->toBe('invalid phone');
});

it('tin marketing chỉ gửi khi có consent theo brand × kênh; không consent → ghi skipped', function () {
    Queue::fake();
    $order = ($this->placeOrder)('notify-order-4');
    T::seed(fn () => NotificationTemplate::query()->create(['type' => 'promo', 'channel' => 'mail', 'subject' => 'Sale', 'body' => 'Giảm 30%']));
    $request = fn (string $key) => new NotificationRequest('promo', $key, $this->brand->id, new Recipient(email: 'lan@example.com', customerId: $order->customer_id), [], NotificationRequest::MARKETING);

    expect(app(Notifier::class)->notify($request('promo:1')))->toBe([])
        ->and(NotificationLog::query()->where('type', 'promo')->sole()->status)->toBe('skipped');

    T::seed(fn () => app(ConsentService::class)->set($order->customer_id, $this->brand->id, 'email', 'marketing', true, 'test'));
    expect(app(Notifier::class)->notify($request('promo:2')))->toBe(['mail']);
    Queue::assertPushed(SendNotificationJob::class);
});

it('giao hàng: tin "đang giao" và "đã giao" kèm hãng + mã vận đơn, mỗi vận đơn một lần', function () {
    $order = ($this->placeOrder)('notify-order-5');
    $shipmentId = (int) DB::table('shipments')->where('order_id', $order->id)->value('id');
    DB::table('shipments')->where('id', $shipmentId)->update(['tracking_number' => 'VN123']);

    foreach (['in_transit', 'delivered', 'delivered'] as $to) {
        ShipmentStatusChanged::dispatch($shipmentId, $order->id, 'created', $to, 0);
    }

    $subjects = array_map(fn ($mail): string => $mail->getOriginalMessage()->getSubject(), ($this->mails)());
    expect($subjects)->toHaveCount(3)
        ->and($subjects[1])->toContain('đang được giao')
        ->and($subjects[2])->toContain('đã giao thành công')
        ->and(($this->mails)()[1]->getOriginalMessage()->getTextBody())->toContain('VN123');
});

it('Admin: xem/sửa mẫu tin (khoá lạc quan, chống trùng), xem nhật ký đã che người nhận; brand staff bị chặn', function () {
    ($this->placeOrder)('notify-order-6');
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'notifications.view', 'notifications.manage'])->create(), 'staff');

    $this->get('/admin/notifications/templates')->assertInertia(fn (Assert $page) => $page->component('Notification::Templates/Index')
        ->has('templates', 4)->where('channels', ['mail']));
    $template = NotificationTemplate::query()->where('type', 'order_placed')->sole();
    $this->put("/admin/notifications/templates/{$template->id}", ['subject' => 'Mới', 'lock_version' => 0])->assertSessionHasNoErrors();
    $this->put("/admin/notifications/templates/{$template->id}", ['subject' => 'Cũ', 'lock_version' => 0])->assertSessionHasErrors('lock_version');
    $this->post('/admin/notifications/templates', ['type' => 'order_placed', 'channel' => 'mail', 'subject' => 'x', 'body' => 'y'])->assertSessionHasErrors('type');
    $this->post('/admin/notifications/templates', ['brand_id' => $this->brand->id, 'type' => 'order_placed', 'channel' => 'mail', 'subject' => 'x', 'body' => 'y'])->assertSessionHasNoErrors();

    $this->get('/admin/notifications/logs')->assertInertia(fn (Assert $page) => $page->component('Notification::Logs/Index')
        ->where('logs.0.recipient', 'la***@example.com'));

    $this->actingAs(T::staffFor(Brand::query()->first(), ['admin.access', 'notifications.view']), 'staff');
    $this->get('/admin/notifications/templates')->assertForbidden();
});
