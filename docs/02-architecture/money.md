# Money

> Trạng thái: **Designed**. Quyết định: [ADR-011](../19-adr/ADR-011-money-representation.md).

## 1. Biểu diễn

- Tiền = **số nguyên theo minor unit** + **mã tiền tệ ISO 4217**. **Không dùng FLOAT/DOUBLE/DECIMAL** (rule R16).
- Cột DB: `<tên>_amount BIGINT NOT NULL` (có dấu, để biểu diễn adjustment âm) + `currency_code CHAR(3)` ở cấp bản ghi (đơn, bảng giá, payment).
- VND có **minor unit = 0** (không có hào/xu): `159000` nghĩa là 159.000 ₫.

| Tiền tệ | Exponent | 159.000 ₫ / 12,50 $ lưu thành |
|---|---|---|
| VND | 0 | `159000` |
| USD | 2 | `1250` |

Bảng `currencies(code, exponent, symbol, rounding_step, active)`; ban đầu chỉ bật VND.

## 2. Value object

```php
namespace Modules\Shared\Domain\Money;

final readonly class Money
{
    private function __construct(public int $amount, public Currency $currency) {}

    public static function of(int $amount, Currency $currency): self;
    public static function vnd(int $amount): self;

    public function add(Money $other): self;          // ném CurrencyMismatch nếu khác tiền tệ
    public function subtract(Money $other): self;
    public function multiply(int $quantity): self;     // chỉ nhân số nguyên
    public function percentage(int $basisPoints, RoundingMode $mode): self; // 1050 = 10,50%
    /** @return list<Money> chia theo trọng số, tổng các phần bằng đúng số gốc */
    public function allocate(array $weights): array;
    public function isNegative(): bool;
    public function isZero(): bool;
}
```

- Tỷ lệ phần trăm biểu diễn bằng **basis points** (số nguyên), không dùng float.
- Eloquent cast `MoneyCast` đọc/ghi cặp `(x_amount, currency_code)`.
- API trả về: `{ "amount": 159000, "currency": "VND", "formatted": "159.000 ₫" }`.

## 3. Làm tròn

| Tình huống | Quy tắc |
|---|---|
| Giảm theo % trên một dòng | Tính trên tổng dòng, làm tròn **half-up** đến `rounding_step` của brand (mặc định 1 ₫; brand có thể chọn 1.000 ₫) |
| Giảm cấp đơn phân bổ xuống dòng | `Money::allocate()` theo **largest remainder**: tổng phân bổ **luôn bằng** số giảm cấp đơn |
| Thuế VAT (giá đã gồm thuế) | Tách thuế theo từng dòng: `tax = round_half_up(gross × rate / (10000 + rate))` với `rate` tính bằng basis points; tổng thuế đơn bằng tổng thuế các dòng |
| Tổng đơn | Tổng các dòng + adjustment; không làm tròn lại lần hai |
| Hoàn tiền một phần | Dựa trên `line_total` sau phân bổ của dòng trả; không tính lại từ giá niêm yết |

Ví dụ phân bổ voucher 50.000 ₫ cho 3 dòng có giá 100.000 / 200.000 / 100.000:

```text
tỷ trọng 1:2:1 → 12.500 / 25.000 / 12.500 (chia hết)
voucher 10.001 ₫ → 2.500,25 / 5.000,5 / 2.500,25 → sàn 2.500 / 5.000 / 2.500 = 10.000,
dư 1 ₫ cho phần có phần thập phân lớn nhất (dòng 2) → 2.500 / 5.001 / 2.500
```

## 4. Đa tiền tệ (Planned)

Schema đã sẵn sàng, tính năng chưa bật:

- Mỗi **channel** có một `currency_code`; bảng giá theo tiền tệ (không quy đổi động khi hiển thị giá bán).
- Nếu cần quy đổi (báo cáo hợp nhất): bảng `exchange_rates(base, quote, rate_micros BIGINT, effective_at)`, tỷ giá **snapshot** vào đơn (`fx_rate_micros`) tại thời điểm đặt.
- Thuế, giảm giá, làm tròn luôn tính theo tiền tệ của đơn; chỉ quy đổi khi báo cáo.

## 5. Kiểm thử

- Unit test `Money`: cộng/trừ khác tiền tệ ném lỗi, `allocate` luôn bảo toàn tổng (property-based với dữ liệu ngẫu nhiên), `percentage` làm tròn đúng.
- Arch/CI: cấm `float` trong chữ ký method có tên chứa `price|amount|total`; grep migration cấm `->decimal(`/`->float(` cho cột tiền.
