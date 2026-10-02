<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Facades\Hook;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Extension\Tests\Fixtures\SchedulingPluginProvider;
use Modules\Integration\Application\CanonicalPayloads;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\NotificationCatalog;
use Modules\Notification\Contracts\Notifier;
use Modules\Notification\Persistence\Models\NotificationTemplate;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/*
| P1-9: hook/registry mới cho plugin — order payload, checkout extra + order meta, listing query,
| schedule(), mẫu tin mặc định của loại tin.
*/

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../modules/Extension/Tests/Fixtures/FixturePlugins.php';

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->headers = [];
    $this->placeOrder = function (array $overrides = []): Order {
        $created = $this->postJson('/api/storefront/v1/carts', [], $this->headers);
        $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $number = $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000, ...$overrides]), [...$headers, 'Idempotency-Key' => 'p1-order-'.uniqid()])
            ->assertCreated()->json('data.number');

        return Order::query()->withoutGlobalScopes()->where('number', $number)->sole();
    };
});

it('checkout extra + vani.order.before_create: plugin kiểm tra trường của mình và lưu vào orders.meta', function () {
    Hook::onValidate('vani.checkout.before_validate', function (CheckoutRequest $request): array {
        $taxCode = (string) ($request->extra['vani.einvoice']['tax_code'] ?? '');

        return $taxCode !== '' && preg_match('/^\d{10}$/', $taxCode) !== 1 ? ['Mã số thuế không hợp lệ.'] : [];
    });
    Hook::onFilter('vani.order.before_create', fn (array $meta, CheckoutRequest $request): array => [
        ...$meta, 'vani.einvoice' => ['tax_code' => $request->extra['vani.einvoice']['tax_code'] ?? null], 5 => 'khoá số bị bỏ',
    ]);

    $created = $this->postJson('/api/storefront/v1/carts', [], $this->headers);
    $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers);
    $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000, 'extra' => ['vani.einvoice' => ['tax_code' => 'abc']]]), [...$headers, 'Idempotency-Key' => 'p1-invalid-1'])
        ->assertStatus(422)->assertJsonPath('error.code', 'checkout.invalid');

    $order = ($this->placeOrder)(['extra' => ['vani.einvoice' => ['tax_code' => '0312345678']]]);
    expect($order->meta)->toBe(['vani.einvoice' => ['tax_code' => '0312345678']])
        ->and(T::seed(fn () => app(OrderReader::class)->find($order->id))->meta)->toBe(['vani.einvoice' => ['tax_code' => '0312345678']]);
});

it('vani.integration.order_payload: plugin chỉ thêm được field, không sửa field canonical', function () {
    $order = ($this->placeOrder)();
    Hook::onFilter('vani.integration.order_payload', fn (array $payload): array => [...$payload, 'number' => 'GIẢ', 'gift_wrap' => true]);

    $payload = T::seed(fn () => app(CanonicalPayloads::class)->order(app(OrderReader::class)->find($order->id)));

    expect($payload['number'])->toBe($order->number)->and($payload['gift_wrap'])->toBeTrue();
});

it('vani.catalog.listing.query: plugin sửa truy vấn danh sách sản phẩm', function () {
    $this->getJson('/api/storefront/v1/products', $this->headers)->assertOk()->assertJsonCount(1, 'data');

    Hook::onFilter('vani.catalog.listing.query', fn (mixed $query): ProductSearchQuery => new ProductSearchQuery(
        brandIds: [$this->brand->id], now: time(), text: 'khong-co-san-pham-nao',
    ), priority: 20);
    $this->getJson('/api/storefront/v1/products', $this->headers)->assertOk()->assertJsonCount(0, 'data');
});

it('NotificationCatalog: loại tin của plugin có mẫu mặc định gửi được ngay; mẫu trong DB thắng', function () {
    app(NotificationCatalog::class)->define(new NotificationType('cart_abandoned', 'Giỏ bị bỏ quên', ['customer_name'], [
        'mail' => ['subject' => 'Bạn quên giỏ hàng', 'body' => 'Chào {{ customer_name }}, giỏ của bạn vẫn còn.'],
    ]));
    $send = fn (string $key) => app(Notifier::class)->notify(new NotificationRequest('cart_abandoned', $key, new Recipient(email: 'lan@example.com'), ['customer_name' => 'Lan']));

    expect($send('cart:1'))->toBe(['mail']);
    $mails = app('mailer')->getSymfonyTransport()->messages()->all();
    expect(end($mails)->getOriginalMessage()->getTextBody())->toBe('Chào Lan, giỏ của bạn vẫn còn.');

    T::seed(fn () => NotificationTemplate::query()->create(['type' => 'cart_abandoned', 'channel' => 'mail', 'subject' => 'Tuỳ chỉnh', 'body' => 'Mẫu Admin cho {{ customer_name }}']));
    $send('cart:2');
    $mails = app('mailer')->getSymfonyTransport()->messages()->all();
    expect(end($mails)->getOriginalMessage()->getSubject())->toBe('Tuỳ chỉnh');
});

it('schedule(): tác vụ của plugin chỉ chạy khi plugin đang bật', function () {
    $root = FixturePlugins::install(['Scheduling' => ['id' => 'fixture.scheduling']]);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    $this->app->register(SchedulingPluginProvider::class);
    $plugins = app(PluginManager::class);
    $plugins->install('fixture.scheduling');
    $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains((string) $event->command, 'inspire'));

    app(PluginActivation::class)->flush();
    expect($event->filtersPass($this->app))->toBeFalse();

    $plugins->enable('fixture.scheduling');
    app(PluginActivation::class)->flush();
    expect($event->filtersPass($this->app))->toBeTrue();

    File::deleteDirectory($root);
});
