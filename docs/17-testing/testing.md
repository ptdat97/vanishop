# Testing Strategy

> Trạng thái: **Partially Implemented**. Đã có: testsuite `Modules` (`modules/*/Tests`), `Plugins` (`custom/plugin/*/Tests`), `Architecture` (`tests/Architecture`); Pest áp dụng `TestCase` + `RefreshDatabase` cho mọi thư mục `Tests/Feature`; unit test của module không boot framework; CI chạy SQLite và MySQL 8.4. Concurrency test: `tests/Concurrency` (reservation, giỏ, PlaceOrder: không vượt tồn, không vượt lượt voucher, một giỏ một đơn; group `concurrency`, bị loại khỏi lần chạy mặc định, tự skip nếu không phải MySQL; CI chạy bước riêng trên MySQL 8.4). Chưa có: contract test suite, E2E (Pest Browser chưa cài).
>
> Chạy test trên MySQL local: `DB_CONNECTION=mysql DB_DATABASE=vanishop_testing vendor/bin/pest`.
>
> Concurrency (nhiều tiến trình, MySQL thật): `DB_CONNECTION=mysql DB_DATABASE=vanishop_testing vendor/bin/pest --group=concurrency`. Test dùng `DatabaseTruncation` (không dùng transaction bọc test vì các tiến trình con phải thấy dữ liệu).
>
> Nếu `pest` thoát mà không in gì (Laravel PAO của Boost nuốt output khi có fatal error), chạy lại với `PAO_DISABLE=1` để thấy lỗi.

## 1. Tầng test

| Tầng | Phạm vi | DB | Vị trí |
|---|---|---|---|
| **Unit** | Domain, Value Object, Entity, Policy, Pricing, Promotion engine, Totals, State machine, Money | Không | `modules/<M>/Tests/Unit`, `custom/plugin/<P>/Tests/Unit` |
| **Application** | Command/Query: Checkout, PlaceOrder, Payment, Inventory reserve | Có (SQLite in-memory hoặc MySQL) | `modules/<M>/Tests/Feature` |
| **Integration** | Database thật (MySQL: lock, CHECK, unique), ERP/Payment/Shipping qua fake server | MySQL | `tests/Integration` |
| **Contract** | Plugin ↔ Core (contract test suite), Connector ↔ External (JSON Schema, fake server), API (OpenAPI) | Tuỳ | `modules/<M>/Tests/Contract` (bộ trừu tượng), plugin kế thừa |
| **Architecture** | Ranh giới module, hướng phụ thuộc | Không | `tests/Architecture` |
| **Concurrency** | Reservation, voucher, IPN, outbox worker | MySQL | `tests/Concurrency` |
| **End-to-End** | Browse → Cart → Checkout → Payment → Order → Fulfillment | MySQL | `tests/Browser` (Pest Browser) |

`phpunit.xml`/`Pest.php` khai báo testsuite bao gồm `modules/*/Tests` và `custom/plugin/*/Tests`.

## 2. Test bắt buộc theo capability

| Capability | Test tối thiểu |
|---|---|
| Money | Bảo toàn tổng khi `allocate`, làm tròn, khác tiền tệ ném lỗi |
| Brand scope | Mỗi model có phạm vi: nhân viên brand A không đọc/ghi brand B; job thiếu context bị từ chối |
| Inventory | Reserve/release/commit; hết hạn; concurrency không oversell |
| Checkout | PlaceOrder thành công/hết hàng/totals đổi/voucher hết/idempotent |
| Order | Mọi transition hợp lệ và không hợp lệ; snapshot không đổi khi catalog đổi |
| Payment | IPN trùng, sai chữ ký, đến muộn; hoàn tiền không vượt số đã thu |
| Integration | Outbox chỉ có khi commit; inbox chống trùng; stale update; ownership |
| API | Auth, scope, validation, pagination, idempotency, 403 ngoài phạm vi |
| Plugin loader | Dependency order, conflict, incompatible core, boot lỗi → `failed`, safe mode |

## 3. Unit test (ví dụ)

```php
it('không cho reserve vượt available', function () {
    $level = StockLevel::restore(onHand: 5, reserved: 3, safetyStock: 1);

    expect(fn () => $level->reserve(2))->toThrow(InsufficientStock::class);
    expect($level->reserve(1)->reserved())->toBe(4);
});
```

## 4. Concurrency test (ví dụ)

```php
it('không oversell khi nhiều tiến trình cùng mua', function () {
    $variant = Variant::factory()->withStock(onHand: 5)->create();

    $results = parallel(50, fn () => app(PlaceOrder::class)->handle(PlaceOrderCommand::fake($variant, qty: 1)));

    expect($results->successful())->toHaveCount(5)
        ->and(StockLevelRecord::for($variant)->reserved)->toBe(5);
})->group('concurrency', 'mysql');
```

(`parallel()` là helper test dùng `pcntl_fork` hoặc Symfony Process, mỗi tiến trình mở kết nối DB riêng.)

## 5. Architecture test

```php
arch('core không phụ thuộc plugin')
    ->expect('Modules')->not->toUse('Plugin');

arch('domain không phụ thuộc hạ tầng')
    ->expect('Modules\Inventory\Domain')   // lặp cho từng module (có thể sinh từ config/modules.php)
    ->not->toUse(['Illuminate\Database', 'Illuminate\Support\Facades', 'Illuminate\Http']);

arch('module không dùng implementation nội bộ của module khác')
    ->expect('Modules\Ordering')
    ->not->toUse(['Modules\Inventory\Persistence', 'Modules\Inventory\Application', 'Modules\Inventory\Domain']);

arch('plugin chỉ dùng API public của core')
    ->expect('Plugin')
    ->not->toUse(['Modules\*\Persistence', 'Modules\*\Application', 'Modules\*\Infrastructure']); // mở rộng theo từng module nếu wildcard không được hỗ trợ

arch('controller không chứa truy cập persistence')
    ->expect('Modules\*\Http\Controllers')->not->toUse('Modules\*\Persistence');

arch('không dùng float cho tiền')->expect('Modules')->not->toUse(['floatval']);
```

Nếu phiên bản Pest đang dùng không hỗ trợ wildcard ở giữa namespace, thì sinh các expectation bằng vòng lặp qua `config('modules')`.

## 6. Contract test cho plugin

Core cung cấp bộ test trừu tượng cho từng contract. Plugin chỉ cần khai báo factory:

```php
// custom/plugin/VietQr/Tests/Contract/VietQrGatewayContractTest.php
uses(PaymentGatewayContractTests::class);

beforeEach(function () {
    $this->gateway = new VietQrGateway(fakeClient());
    $this->validCallback = fn (PaymentData $p) => VietQrFixtures::signedCallback($p);
    $this->tamperedCallback = fn (PaymentData $p) => VietQrFixtures::tamperedCallback($p);
});
```

Bộ `PaymentGatewayContractTests` kiểm tra: `initiate` trả kết quả hợp lệ; callback đúng chữ ký thì được chấp nhận; callback sai chữ ký bị từ chối; `refund` idempotent theo key; số tiền luôn là `Money`.

Tương tự: `ShippingCarrierContractTests`, `PromotionRuleContractTests`, `TotalsCalculatorContractTests`, `ConnectorContractTests`, `ErpConnectorContractTests`.

## 7. End-to-End

Flow tối thiểu (chạy mỗi PR vào `main`, trên MySQL):

1. Browse PLP → PDP → chọn size → thêm giỏ.
2. Checkout với COD → đơn `pending` → xác nhận → `confirmed`.
3. Tạo shipment `manual` → `shipped` → `delivered` → reservation committed.
4. (Khi có plugin) Checkout VietQR → IPN giả → `paid`.

## 8. CI

| Bước | Công cụ |
|---|---|
| Format | `vendor/bin/pint --test` |
| Static analysis | Larastan level ≥ 6 |
| Unit + Architecture | Pest (SQLite) |
| Feature + Integration + Concurrency | Pest với service MySQL 8.4 |
| Plugin | Test của mọi `custom/plugin/*` + contract test |
| Clean-room & license | Grep chuỗi cấm, `composer licenses` ([clean-room](../01-principles/clean-room-license.md)) |
| E2E | Pest Browser (nightly + trước release) |

Mục tiêu coverage: ≥ 80% cho `Domain/` và `Application/` của Inventory, Checkout, Ordering, Payment, Promotion, Fulfillment.
