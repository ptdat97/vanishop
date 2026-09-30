# Contract: chữ ký hook và domain event

> Kiểm chứng từ: `modules/Extension/Application/Hooks/HookManager.php`, `Domain/Hooks/*`, `modules/*/hooks.php`, `PluginServiceProvider`. Cơ chế: [extension-model §4](../../04-extension/extension-model.md). Danh mục hook public: [extension-point-catalog §4](../../04-extension/extension-point-catalog.md).

## 1. Bốn loại hook

| Loại | Core phát | Plugin nghe | Listener nhận → trả | Kết quả Core nhận |
|---|---|---|---|---|
| `filter` | `Hook::filter($name, $value, ...$args)` | `onFilter($name, fn ($value, ...$args) => $value2)` | Giá trị **cùng kiểu** với `$value` | Giá trị sau mọi listener |
| `action` | `Hook::action($name, ...$args)` | `onAction($name, fn (...$args): void)` | Không dùng giá trị trả về | — |
| `validate` | `Hook::collect($name, ...$args)` | `onValidate($name, fn (...$args): array)` | `list<string>` lỗi của mình (rỗng = hợp lệ) | Gộp lỗi của mọi listener |
| `slot` | `Hook::slot($name, ...$args)` | `onSlot($name, fn (...$args) => $item)` | **Một** phần tử hiển thị | `list` phần tử, theo priority |

Listener `validate` và `slot` **không** nhận danh sách tích luỹ: Core tự gộp, plugin không thể xoá lỗi hay phần tử của plugin khác.

## 2. Tên hook phải được khai báo

Hook nằm trong `modules/<Ctx>/hooks.php`:

```php
'vani.order.before_create' => [
    'type' => 'filter', 'visibility' => 'public', 'since' => '0.3',
    'args' => ['meta' => 'array', 'request' => CheckoutRequest::class, 'totals' => Totals::class],
    'description' => 'Bổ sung orders.meta (khoá theo plugin id). Chạy trong transaction, không I/O mạng.',
],
```

| Tình huống | Strict (local/testing) | Production |
|---|---|---|
| Phát/nghe hook chưa khai báo | `HookNotDeclared` | Cho chạy |
| Plugin nghe hook `internal` | `HookNotPublic` → plugin `failed` khi boot | như strict |
| Filter trả sai kiểu | `HookReturnTypeMismatch` | Log cảnh báo, **bỏ kết quả** của listener đó |

Tra hook đang có: `php artisan vani:plugin:hooks`. Không đoán tên hook; thiếu thì đề xuất PR Core (R1).

## 3. Priority

Mặc định **10**; **số nhỏ chạy trước**. Cùng priority: theo thứ tự nạp plugin (thứ tự phụ thuộc). Ghi priority tường minh khi thứ tự quan trọng. Riêng `TotalsCalculator` (contract, không phải hook) dùng dải priority riêng: plugin 300–399.

## 4. Quy tắc khi chạy

| Quy tắc | Hệ quả khi vi phạm |
|---|---|
| Hook chạy **trong transaction** (`vani.order.before_create`, `vani.order.after_create`, `vani.checkout.*_validate`, `vani.cart.validate_line`) chỉ ghi DB của plugin, không I/O mạng | Kéo dài khoá giỏ/tồn; exception → **rollback cả đơn** |
| Mỗi listener < 50 ms | `HookManager` đo mọi lần gọi theo hook × plugin, quá ngưỡng ghi log cảnh báo kèm plugin id |
| `filter`/`validate` trong flow giao dịch ném exception → flow thất bại | Cố ý fail-fast, không nuốt lỗi |
| `slot` ném exception → chỉ bỏ phần tử đó, ghi log | Plugin lỗi không làm sập trang |
| Filter dùng chung: **chỉ thêm/sửa khoá của mình**, không thay cả payload. `vani.integration.order_payload` chỉ được **thêm** khoá | Phá dữ liệu của plugin khác |
| Dữ liệu plugin gắn vào đơn/giỏ: khoá theo plugin id (`meta['vani.einvoice']`) | Đụng khoá plugin khác |

```php
// Đúng: validate trả lỗi của mình
$this->onValidate('vani.checkout.after_validate', function (CheckoutRequest $request, Totals $totals): array {
    return $totals->grandTotal->amount > 20_000_000 && $request->paymentMethod === 'cod'
        ? ['Đơn trên 20 triệu không hỗ trợ thu tiền khi giao.']
        : [];
});

// Đúng: filter trả cùng kiểu, chỉ thêm khoá của mình
$this->onFilter('vani.order.before_create', fn (array $meta, CheckoutRequest $request): array
    => $meta + ['vani.gift-wrap' => ['wrap' => (bool) ($request->extra['vani.gift-wrap']['wrap'] ?? false)]]);
```

(DTO: `modules/Checkout/Contracts/Data/CheckoutRequest.php`, `Totals.php`. Dữ liệu khách nhập cho plugin nằm ở `CheckoutRequest::$extra[<plugin id>]`.)

## 5. Slot UI

- Slot Admin (`vani.admin.dashboard.cards`, `vani.admin.order.sidebar`) trả **dữ liệu có cấu trúc** (mảng mô tả card/panel), Admin Vue tự render. Plugin không trả HTML cho Admin.
- Slot storefront (Designed, [ADR-025](../../19-adr/ADR-025-native-storefront-ssr-slots.md)) trả **view component** (tên Blade view của plugin + dữ liệu), theme render tại vị trí slot. Slot chỉ **nối thêm**; muốn thay khối thì override view trong theme brand.

## 6. Domain event

```php
$this->onEvent(OrderPlaced::class, AwardPendingPoints::class);   // class-string → handle(OrderPlaced $event)
```

- Dispatch **sau commit**; payload là DTO bất biến trong `Modules\<Ctx>\Events`.
- Chạy khi plugin bật cho `brandId` của event, trong phạm vi brand đó. Event không mang brand (khách hàng, tồn kho, giỏ) chỉ tới plugin bật ở `owner`.
- Lỗi được log, **không** làm hỏng flow gốc. Việc phải đảm bảo tới nơi (gửi ERP, webhook) đi qua Integration outbox, không gọi HTTP trực tiếp trong listener.
- Handler phải idempotent (R18): event có thể tới lại qua replay/đối soát.

## 7. Chọn hook, event hay contract

| Plugin muốn | Dùng |
|---|---|
| Chặn một thao tác kèm lý do | `validate` |
| Thêm dữ liệu vào payload/đơn trong flow | `filter` |
| Ghi dữ liệu của mình **cùng transaction** với Core | `action` trong transaction |
| Phản ứng sau khi việc đã xảy ra | `onEvent` |
| Cung cấp một cách làm khác (cổng, hãng, rule, kênh) | `contribute()` một contract |
| Thêm UI | `slot` (hoặc trang Admin qua registry) |

## 8. Checklist

- [ ] Hook có trong `vani:plugin:hooks`, `visibility: public`
- [ ] Filter trả **cùng kiểu**; validate trả `list<string>`; slot trả **một** phần tử
- [ ] Không I/O mạng trong hook chạy trong transaction; listener < 50 ms
- [ ] Chỉ thêm khoá của mình, khoá theo plugin id
- [ ] Event handler idempotent; việc cần đảm bảo tới nơi đi qua outbox
- [ ] Priority tường minh khi thứ tự quan trọng (mặc định 10, nhỏ chạy trước)
