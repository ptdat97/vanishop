# 12 — API

## 1. Ba nhóm API

| Nhóm | Prefix | Người dùng | Xác thực |
|---|---|---|---|
| **Storefront API** | `/api/storefront/v1` | Web storefront (phần động), mobile app, Zalo Mini App | Kênh xác định qua header `X-Vani-Channel` hoặc domain; khách: Sanctum token / session cookie |
| **Admin API** | `/api/admin/v1` | Công cụ nội bộ, POS, script vận hành (Admin UI dùng Inertia qua route web, **không** cần API này) | Sanctum token của nhân viên + RBAC theo scope |
| **Integration API** | `/api/integration/v1` và `/api/integrations/{system}/webhooks` | Integration Client (ERP, POS, ODO khi có), hãng VC, cổng TT | Client credentials (API key + secret, HMAC chữ ký), IP allowlist tuỳ hệ thống |

Đặc tả mỗi nhóm viết bằng **OpenAPI 3.1** trong `docs/api/*.yaml` (sinh tài liệu HTML trong CI).

## 2. Quy ước chung

- JSON, `snake_case`, UTF-8. Thời gian ISO-8601 có offset (`2026-09-28T10:15:00+07:00`).
- **Tiền** trả về dạng object: `{ "amount": 1250000, "currency": "VND", "formatted": "1.250.000 ₫" }`.
- ID công khai dùng `public_id` (ULID), **không** lộ ID tự tăng.
- Phân trang cursor: `?cursor=...&limit=20` → `{ data: [...], meta: { next_cursor } }`.
- Lọc/sắp xếp: `?filter[status]=confirmed&sort=-placed_at`.
- Response dùng **Eloquent API Resource**; không trả model thô.
- Versioning theo URL (`v1`); thay đổi phá vỡ → `v2`, giữ `v1` tối thiểu 6 tháng.
- **Idempotency**: request tạo tài nguyên (đặt hàng, hoàn tiền, tạo shipment) nhận header `Idempotency-Key`; lưu kết quả 24h.
- Rate limit: storefront theo IP + token; integration theo client.

### Định dạng lỗi

```json
{
  "error": {
    "code": "inventory.insufficient_stock",
    "message": "Sản phẩm Áo sơ mi lụa (M) chỉ còn 1.",
    "details": [{ "field": "lines.0.quantity", "available": 1 }],
    "trace_id": "01J9Z8..."
  }
}
```

Mã lỗi dạng `<module>.<lý_do>`, ổn định để client xử lý; `message` đã dịch theo `Accept-Language`.

## 3. Storefront API — endpoint chính

| Method | Path | Mô tả |
|---|---|---|
| GET | `/catalog/categories` | Cây danh mục của kênh |
| GET | `/catalog/products` | Danh sách/tìm kiếm + facet |
| GET | `/catalog/products/{slug}` | Chi tiết style + màu + variant + giá + ATS |
| GET | `/catalog/products/{slug}/store-availability?province=` | Tồn theo cửa hàng (mức độ, không số chính xác) |
| POST | `/carts` | Tạo giỏ |
| GET/PATCH | `/carts/{id}` | Xem giỏ (kèm totals) |
| POST/PATCH/DELETE | `/carts/{id}/lines[/{lineId}]` | Thêm/sửa/xoá dòng |
| POST/DELETE | `/carts/{id}/vouchers` | Áp/gỡ voucher *(route do plugin `Promotion` đăng ký)* |
| GET | `/carts/{id}/shipping-options` | Phương thức VC + phí |
| GET | `/carts/{id}/payment-methods` | Phương thức thanh toán khả dụng |
| POST | `/checkout/{cartId}/orders` | Đặt hàng (Idempotency-Key) → order + payment initiation |
| GET | `/orders/track?number=&phone=` | Tra cứu đơn không cần đăng nhập |
| POST | `/auth/otp/request`, `/auth/otp/verify` | Đăng nhập OTP |
| GET/PATCH | `/me`, `/me/addresses`, `/me/orders`, `/me/loyalty` (plugin `Loyalty`), `/me/wishlist` (plugin `Wishlist`) | Tài khoản |
| POST | `/me/returns` | Tạo yêu cầu đổi trả |
| GET | `/geo/provinces`, `/geo/provinces/{code}/wards`, `/geo/search?q=` | Địa giới hành chính |

## 4. Admin API — nhóm tài nguyên

`brands`, `channels`, `locations`, `styles`, `variants`, `price-lists`, `stock-levels` (chỉ đọc + điều chỉnh có lý do), `customers`, `orders` (+ `transitions`, `notes`, `shipments`, `refunds`), `returns`, `pages`, `menus`, `staff`, `roles`, `plugins`, `integration/clients`, `integration/messages` (xem/replay), `reports/*`. Plugin thêm tài nguyên riêng dưới `/api/admin/v1/plugins/{code}/…` (ví dụ `promotions`, `vouchers` của plugin `Promotion`).

Mọi endpoint Admin kiểm tra **Policy** + phạm vi brand của nhân viên ([13](13-bao-mat-phan-quyen.md)).

## 5. Integration API

### 5.1 Nhận vào (inbound)

| Method | Path | Nguồn | Nội dung |
|---|---|---|---|
| * | `/api/integration/v1/*` | Integration Client | Đọc/ghi đơn, fulfillment, tồn, mã hàng, giá, đổi trả — danh sách đầy đủ ở [08 §4](08-module-integration.md) |
| POST | `/api/integrations/{connector}/webhooks` | Dịch vụ có connector plugin | Sự kiện riêng của dịch vụ, connector dịch sang lệnh nội bộ |
| POST | `/api/integrations/payments/{gateway}/ipn` | Cổng TT | IPN |
| POST | `/api/integrations/carriers/{carrier}/webhooks` | Hãng VC | Trạng thái vận đơn |

Envelope chuẩn cho message nội bộ/đối tác:

```json
{
  "event_id": "c1f3c7a2-...",
  "event_type": "inventory.adjusted",
  "schema_version": "1",
  "occurred_at": "2026-09-28T10:15:00+07:00",
  "source": "erp",
  "correlation_id": "LM2609-000123",
  "data": { "location_code": "WH-HN-01", "sku": "LM24FW-SH012-IVR-M", "on_hand": 42, "version": 18233 }
}
```

Chữ ký: header `X-Vani-Signature: t=<unix>,v1=<hex(hmac_sha256(secret, t + "." + body))>`; từ chối nếu lệch thời gian > 5 phút.

### 5.2 Gửi ra (outbound)

- Payload canonical có version (`vanishop.order.v1`), connector dịch sang định dạng đích.
- VaniShop gửi webhook cho đối tác cùng envelope + chữ ký, retry theo outbox.

## 6. Kiểm thử API

- Mỗi endpoint có Feature test (Pest) cho: thành công, validation, phân quyền/phạm vi brand, idempotency (nếu có).
- Contract test cho Integration API dựa trên JSON Schema của từng `event_type`.
