# Contract: plugin cổng thanh toán (`PaymentGateway`)

> Kiểm chứng từ: `modules/Payment/Contracts/PaymentGateway.php`, `Contracts/Data/*`, `Application/PaymentService.php`, `Http/Controllers/Api/GatewayCallbackController.php`, `custom/plugin/VietQr`. Nghiệp vụ: [payment](../../10-payment/payment.md). Contract test: `Modules\Payment\Testing\PaymentGatewayContract`.

## 1. Interface

```php
interface PaymentGateway
{
    public const TAG = 'vani.payment.gateways';

    public function code(): string;                       // = payments.gateway_code, = {gateway} trong URL callback
    public function label(): string;
    public function capabilities(): GatewayCapabilities;
    public function isAvailable(PaymentContext $context): bool;
    public function initiate(PaymentData $payment): PaymentInitiation;
}

// Khả năng tuỳ chọn — Core kiểm tra bằng instanceof (0.5.60, Extension API 1.18.0)
interface ReceivesCallbacks { /** @throws InvalidCallback */ public function verifyCallback(Request $request): GatewayCallback; }
interface CallbackResponder extends ReceivesCallbacks { public function callbackResponse(CallbackOutcome $outcome, ?GatewayCallback $callback): array; }
interface QueriesStatus     { public function query(PaymentData $payment): GatewayStatus; }
interface RefundsPayments   { public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): RefundResult; }
interface TracksRefunds extends RefundsPayments { public function refundStatus(PaymentData $payment, string $idempotencyKey, ?string $gatewayReference): RefundResult; }
interface CapturesLater     { public function capture(...): GatewayResult; public function void(...): GatewayResult; }
interface HandlesReturn     { public function parseReturn(Request $request): ReturnResult; }   // Return URL chung
```

Cổng không có khả năng nào thì không cài interface đó (COD, chuyển khoản tay chỉ cài `PaymentGateway`). Trạng thái là enum: `GatewayPaymentStatus` (`Paid`, `Failed`, `Pending`, `Authorized`) cho callback/tra cứu, `InitiationType` (`None`, `Redirect`, `Qr`, `Instructions`) cho `PaymentInitiation::$type` (hằng `PaymentInitiation::REDIRECT`… là viết tắt của các case).

Đăng ký: `$this->contribute(PaymentGateway::TAG, MyGateway::class);` trong `boot()`. Không cần view theo quy ước tên, không cần route riêng cho callback.

## 2. Ai làm gì

| Bước | Core | Plugin |
|---|---|---|
| Liệt kê phương thức ở checkout | Gọi `isAvailable()` của mọi cổng bật trong scope, **bọc lỗi** (`Extensions::call`, circuit breaker 5 lỗi/phút → bỏ qua 5 phút); rồi hook `vani.checkout.payment_methods` | Trả `false` khi thiếu cấu hình, ngoài hạn mức số tiền, health check lỗi |
| Tạo payment | Trong transaction đặt hàng | — |
| Khởi tạo thanh toán | Gọi `initiate()` **sau commit**; trả `payment.action` cho client | Trả `PaymentInitiation`: `none` / `redirect(url)` / `qr(qrPayload)` / `instructions([...])`. **Idempotent**: gọi lại cho cùng payment trả cùng kết quả |
| Callback/IPN | Route chung `/api/payments/{code}/callback` (GET/POST, 600/phút/IP, không phiên/CSRF) → gọi `verifyCallback()` nếu cổng `ReceivesCallbacks`, không thì 404 | **Chỉ xác minh chữ ký + chuẩn hoá**, không ghi DB |
| Ghi nhận tiền | Khoá payment → insert transaction (unique theo mã giao dịch cổng: IPN trùng bị bỏ) → **so số tiền và tiền tệ** (lệch → không ghi nhận, log) → cập nhật payment + trạng thái thanh toán của đơn, cùng transaction | — |
| Phản hồi cổng | Trả `GatewayCallback::$acknowledgement` dạng JSON | Điền đúng định dạng cổng mong đợi |
| Giao dịch treo | `vani:payment:reconcile` mỗi phút gọi `query()` nếu cổng `QueriesStatus` | Trả `GatewayStatus` |
| Hết hạn | `vani:payment:expire` theo `paymentTtlSeconds` | Khai báo TTL trong capabilities |
| Hoàn tiền | Kiểm tra số tiền hoàn được, tạo refund; cổng `RefundsPayments` (và `partialRefund` khi hoàn một phần) → gọi `refund()` sau commit, không thì chờ hoàn tay/`RefundPayout`. `RefundResult`: `Succeeded` → đã hoàn; `Pending` → giữ "đang hoàn"; `Failed` → chờ hoàn tay | Idempotent theo `$idempotencyKey`; **không trả `Succeeded` khi tiền chưa đi** |
| Hoàn bất đồng bộ | `vani:payment:refunds` (5 phút/lần) với khoản hoàn "đang hoàn" > 10 phút: cổng `TracksRefunds` → `refundStatus()`; cổng khác → gọi lại `refund()` cùng key (cũng cứu khoản hoàn kẹt khi tiến trình chết trước khi gọi cổng) | Trả trạng thái lệnh hoàn |

Return URL (trình duyệt quay về) **không** xác nhận thanh toán; chỉ callback đã xác minh mới được. Cổng `HandlesReturn` dùng Return URL chung của Core `PaymentData::$returnUrl` (`/_vani/payments/{code}/return`, route `storefront.payment.return`): `parseReturn()` xác minh tham số, trả `ReturnResult(paymentPublicId, GatewayPaymentStatus)`; Core kiểm tra khoản thuộc đúng cổng rồi đưa khách về trang đơn kèm thông báo — plugin không cần route/controller riêng.

**Ngữ cảnh và dữ liệu gửi cổng (0.5.62).** `PaymentContext` (cho `isAvailable()`): `amount`, `provinceCode`, `wardCode`, `shippingMethod`, `shippingSource` (mã hãng nếu phí do hãng báo), `itemCount`, `channel` (web | app | zalo | admin) — quote và kiểm tra lúc đặt dùng cùng ngữ cảnh. `PaymentData` (cho `initiate()`, `refund()`…): thêm `returnUrl`, `customer` (người nhận: `full_name`, `phone`, `email?`), `clientIp` (IP khách lúc đặt, giữ qua "thanh toán lại"; null với đơn tạo trong Admin), `locale`.

## 3. `GatewayCapabilities`

| Cờ | Ý nghĩa với Core |
|---|---|
| `partialRefund` | Hoàn một phần qua cổng (cổng `RefundsPayments`); không có → hoàn một phần thành hoàn tay |
| `manualConfirmation` | Nhân viên xác nhận đã nhận tiền (chuyển khoản tay) |
| `paymentTtlSeconds` | Hạn thanh toán; `null` = không hết hạn |
| `collectsOnDelivery` | Thu tiền khi giao: đơn bắt đầu `cod_pending`, vận đơn đầu mang số tiền thu hộ, tiền ghi nhận khi giao thành công. Core **đọc cờ này**, không so mã `'cod'` |

## 4. `verifyCallback()`

```php
public function verifyCallback(Request $request): GatewayCallback
{
    $signature = (string) $request->header('X-Signature');
    if (! hash_equals(hash_hmac('sha256', $request->getContent(), $this->secret), $signature)) {
        throw new InvalidCallback('Chữ ký không hợp lệ.');           // Core log kênh security, trả 400
    }

    return new GatewayCallback(
        paymentPublicId: $data['order_ref'],                          // public id của payment Core đã gửi ở initiate()
        gatewayTransactionId: $data['transaction_id'],                // unique → chống ghi nhận trùng
        status: GatewayPaymentStatus::Paid,                           // Paid | Failed | Pending (Authorized: chỉ CapturesLater)
        amount: Money::vnd((int) $data['amount']),                    // Core so với số tiền payment
        maskedPayload: Arr::except($data, ['card_number']),           // lưu vết, đã che dữ liệu nhạy cảm
        acknowledgement: ['RspCode' => '00'],                         // phản hồi cổng mong đợi
    );
}
```

Tên header/trường là ví dụ; theo tài liệu của từng cổng. `hash_equals` bắt buộc (chống timing attack).

## Giữ tiền rồi thu sau (`CapturesLater`, 0.3.5)

Cổng thẻ quốc tế/BNPL thường **giữ tiền** khi khách thanh toán và chỉ **thu** khi giao hàng. Cổng implement thêm interface tuỳ chọn:

```php
final class CardGateway implements PaymentGateway, CapturesLater
{
    public function capture(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult { /* thu, idempotent theo key */ }
    public function void(PaymentData $payment, string $idempotencyKey): GatewayResult { /* huỷ giữ tiền, idempotent */ }
}
```

| Bước | Core | Plugin |
|---|---|---|
| Khách thanh toán | — | IPN trả `GatewayCallback` status `authorized` (chỉ cổng `CapturesLater` được dùng) |
| Ghi nhận | Payment `authorized`, đơn xác nhận (`payment_status = authorized`), event `PaymentAuthorized` | — |
| Thu | Vận đơn rời kho (`picked_up`) → `capture()` (Admin → Cài đặt → Bán hàng & thuế → "Thu tiền thanh toán online đã giữ", mặc định `VANI_PAYMENT_CAPTURE_ON=shipped`); hoặc nhân viên bấm "Thu tiền". Lỗi → giữ `authorized` + log | Trả `GatewayResult` thành công + mã giao dịch; gọi lại cùng key trả cùng mã |
| Huỷ đơn | `void()` → payment `cancelled`, không tạo hoàn tiền | Huỷ giữ tiền |

Cổng xác nhận thu bằng IPN `paid` sau đó cũng được: Core bỏ qua bản trùng. Hết hạn giữ tiền phía cổng (thường 7 ngày): Designed.

## 5. Lỗi thường gặp

| Lỗi | Hậu quả | Cách tránh |
|---|---|---|
| Ghi DB/đổi trạng thái đơn trong plugin khi nhận callback | Bỏ qua khoá, unique và so số tiền của Core → ghi nhận trùng hoặc sai tiền | Chỉ trả `GatewayCallback`, để Core ghi nhận |
| So chữ ký bằng `==` | Timing attack | `hash_equals` |
| `initiate()` tạo giao dịch mới mỗi lần gọi | Khách bấm lại tạo nhiều mã QR/giao dịch | Khoá idempotent theo `payment->publicId` |
| `isAvailable()` gọi mạng không timeout | Làm chậm/hỏng trang checkout (dù Core bọc lỗi) | Timeout ngắn + cache health |
| Secret trong code hoặc log | R21 | `settings()` type `secret` hoặc `.env`; `maskedPayload` đã che |
| Plugin tự so `paymentMethod === 'cod'` để suy ra thu tiền khi giao | Sai với cổng thu hộ khác | Đọc `collectsOnDelivery` |

## 6. Checklist

- [ ] `code()` ổn định, trùng với phần `{gateway}` URL cổng gọi về
- [ ] Cài đúng interface tuỳ chọn (`ReceivesCallbacks`, `QueriesStatus`, `RefundsPayments`, `CapturesLater`); `capabilities()` khai TTL, `partialRefund`, `manualConfirmation`, `collectsOnDelivery`
- [ ] `isAvailable()` nhanh, không ném lỗi với cấu hình thiếu (trả `false`)
- [ ] `initiate()` idempotent, chạy được sau commit
- [ ] `verifyCallback()` xác minh chữ ký bằng `hash_equals`, ném `InvalidCallback` khi sai, không ghi DB
- [ ] `refund()` idempotent theo `$idempotencyKey`, trả `RefundResult` đúng sự thật (`Pending` nếu tiền chưa về; cổng hoàn bất đồng bộ cài `TracksRefunds`)
- [ ] Secret qua `settings()` (secret) hoặc `.env`; không log dữ liệu thẻ/tài khoản
- [ ] Chạy `PaymentGatewayContract` + test callback trùng/sai chữ ký/sai số tiền

## Giới hạn hiện tại

- `vani.vietqr` đọc cấu hình từ `Config/vietqr.php` + `.env`, chưa chỉnh được trong Admin qua `Settings`.
- Phê duyệt hoàn tiền 2 bước: chưa có.
