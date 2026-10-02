# Contract: plugin hãng vận chuyển (`ShippingCarrier` + `ShippingRateProvider`)

> Kiểm chứng từ: `modules/Fulfillment/Contracts/ShippingCarrier.php`, `Contracts/Data/*`, `Application/FulfillmentService.php`, `modules/Checkout/Contracts/ShippingRateProvider.php`, `Checkout/Application/ShippingOptions.php`, `custom/plugin/Ghn`. Nghiệp vụ: [fulfillment](../../09-order/fulfillment.md). Contract test: `Modules\Fulfillment\Testing\ShippingCarrierContract`.

Một hãng vận chuyển thường cài **hai** contract ở hai thời điểm khác nhau:

| Contract | Module | Thời điểm | Việc |
|---|---|---|---|
| `ShippingRateProvider` | Checkout | Khi khách xem/đặt đơn (quote, placeOrder) | Báo phương thức giao + phí |
| `ShippingCarrier` | Fulfillment | Sau khi đơn được xác nhận | Đặt vận đơn, huỷ, nhận webhook hành trình |

```php
public function boot(): void
{
    $this->contribute(ShippingCarrier::CARRIERS_TAG, GhnCarrier::class);
    $this->contribute(ShippingRateProvider::TAG, GhnCarrier::class);
}
```

## 1. `ShippingRateProvider`

```php
interface ShippingRateProvider
{
    public const TAG = 'vani.checkout.shipping_providers';

    /** @return list<ShippingOption> */
    public function options(TotalsContext $context): array;
}

final readonly class ShippingOption
{
    public function __construct(public string $code, public string $label, public Money $fee, public string $source = 'core') {}
}
```

| Quy tắc | Lý do |
|---|---|
| `code` duy nhất toàn hệ thống (nên bắt đầu bằng mã hãng: `ghn_standard`, `ghn_express`) | Khách chọn theo `code`; trùng mã với provider khác thì không phân biệt được |
| `source` = `code()` của carrier tương ứng | Đơn lưu `shippingMethod.source` để biết hãng |
| `fee` là `Money` (VND nguyên) | R16 |
| Không trả gì khi thiếu dữ liệu (chưa có địa chỉ, ngoài vùng phục vụ) | Checkout vẫn còn phương thức khác |
| **Cache báo cước** theo (mã phường/xã, khối lượng, giá trị thu hộ, dịch vụ) và timeout ngắn | `options()` chạy mỗi lần quote cho mọi provider đang bật; và **chạy trong transaction đặt hàng** khi placeOrder |

Core gọi `options()` qua `Extensions::call()`: provider ném lỗi → bị bỏ qua (có log + circuit breaker), checkout không hỏng. Sau đó hook `vani.checkout.shipping_options` cho phép plugin khác lọc/đổi phí. `TotalsContext` là DTO bất biến: dùng `lines`, `shippingAddress`, `channelId`, `currencyCode`.

## 2. `ShippingCarrier`

```php
interface ShippingCarrier
{
    public const CARRIERS_TAG = 'vani.shipping.carriers';

    public function code(): string;                       // = shipments.carrier_code, = {carrier} trong URL webhook
    public function label(): string;
    public function capabilities(): CarrierCapabilities;  // autoBooking, webhooks, cancel
    public function createShipment(ShipmentData $shipment): CarrierShipment;
    public function cancel(ShipmentData $shipment): void;
    /** @throws InvalidCarrierEvent */
    public function parseWebhook(Request $request): CarrierEvent;
}
```

| Bước | Core | Plugin |
|---|---|---|
| Tạo vận đơn | Khi đơn xác nhận (listener) hoặc nhân viên tạo; `SourcingStrategy` chọn kho theo hàng đang giữ; vận đơn đầu tiên mang tiền thu hộ nếu đơn ở `cod_pending` (cổng `collectsOnDelivery`) | — |
| Đặt với hãng | Nếu `autoBooking`: job queue `fulfillment` **sau commit** gọi `createShipment()`; lỗi → log, thử lại 3 lần (30s, 2 phút, 10 phút) | Trả `CarrierShipment(trackingNumber, labelUrl?, serviceCode?)`. **Idempotent theo `$shipment->publicId`** (gửi làm mã tham chiếu phía hãng) |
| Không tự đặt | `autoBooking = false`: nhân viên nhập mã vận đơn | — |
| Webhook | Route chung `/api/shipping/{code}/webhook` (600/phút/IP) → `parseWebhook()` | Xác minh chữ ký/token bằng `hash_equals`, chuẩn hoá trạng thái; ném `InvalidCarrierEvent` khi sai |
| Ghi nhận trạng thái | Khử trùng theo `eventId`, **không cho lùi trạng thái**, tính lại trạng thái fulfillment của đơn, commit giữ hàng khi rời kho, ghi nhận tiền thu hộ khi `delivered` | — |

`CarrierEvent::$status` phải là giá trị chuẩn của `ShipmentStatus` dạng chuỗi: `created`, `picked_up`, `in_transit`, `out_for_delivery`, `failed_attempt`, `delivered`, `returning`, `returned`, `cancelled`. Plugin map mã của hãng sang các giá trị này và **không** import enum Domain của Core (R5).

## 3. Lỗi thường gặp

| Lỗi | Hậu quả | Cách tránh |
|---|---|---|
| `options()` gọi API hãng mỗi lần, không cache/timeout | Checkout chậm, vượt rate limit hãng, kéo dài transaction đặt hàng | Cache + timeout; lỗi thì không trả option |
| `createShipment()` không idempotent | Job chạy lại tạo 2 vận đơn thật | Dùng `publicId` làm mã tham chiếu, tra lại trước khi tạo |
| `eventId` không ổn định (sinh ngẫu nhiên) | Core không khử trùng được webhook gửi lại | Lấy id sự kiện của hãng, hoặc băm (mã vận đơn + trạng thái + thời điểm) |
| Map trạng thái hãng thiếu nhánh | Webhook bị từ chối, đơn kẹt | Map đủ; trạng thái không biết → `InvalidCarrierEvent` + log để bổ sung |
| Plugin tự cập nhật bảng vận đơn | Bỏ qua quy tắc không lùi trạng thái, commit giữ hàng | Chỉ trả `CarrierEvent` |

## 4. Checklist

- [ ] `ShippingOption::$code` duy nhất, `source` = `code()` carrier
- [ ] Báo cước có cache + timeout; không ném lỗi khi thiếu dữ liệu
- [ ] `createShipment()` idempotent theo `publicId`; `cancel()` an toàn khi gọi lại
- [ ] `parseWebhook()` xác minh bằng `hash_equals`, map đủ trạng thái chuẩn, `eventId` ổn định
- [ ] `capabilities()` đúng (autoBooking, webhooks, cancel)
- [ ] Chạy `ShippingCarrierContract` + test webhook trùng/sai chữ ký/trạng thái lùi

## Giới hạn hiện tại

- Carrier của vận đơn theo lựa chọn của khách (2026-10-02): `FulfillmentService::createForOrder()` dùng carrier có `code()` = `shippingMethod.source` của đơn (dịch vụ = `shippingMethod.code`, truyền tới `ShipmentData::$serviceCode`) nếu carrier đang bật; phí không gắn hãng (`vani.shipping-flat-rate`) hoặc carrier đã tắt → `vanishop.fulfillment.default_carrier`. Nhân viên chỉ định carrier vẫn được ưu tiên.
- **`vani.ghn` là bản mô phỏng**: phí lấy từ cấu hình (mặc định 30.000đ), mã vận đơn sinh trong bộ nhớ, chưa gọi API GHN. Đủ để chứng minh extension point và chạy contract test, **chưa** dùng được cho vận hành. Khi làm thật (hoãn theo quyết định Owner 2026-10-02): báo cước bằng `shipping-order/preview` (nhận tên tỉnh/phường, không tạo đơn), tạo đơn với `client_order_code` = `publicId` + tra `detail-by-client-code` trước khi tạo, huỷ `switch-status/cancel`, webhook không có chữ ký → xác minh bằng custom header bí mật, khử trùng theo `OrderCode` + `Type` + `Time`.
- `FulfillmentMethod` (nhận tại cửa hàng): Designed.
