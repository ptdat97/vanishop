# API

> Trạng thái: **Designed**. Chưa có route nào. Quyết định: [ADR-010](../19-adr/ADR-010-api-versioning.md), [ADR-014](../19-adr/ADR-014-idempotency.md).

## 1. Ba nhóm API

| Nhóm | Prefix | Người dùng | Xác thực |
|---|---|---|---|
| **Storefront API** | `/api/storefront/v1` | Web storefront (phần động), mobile app, Zalo Mini App | Kênh xác định qua header `X-Vani-Channel` (mã kênh, **bắt buộc**); ngôn ngữ = locale của kênh, đổi bằng header `X-Vani-Locale` (`vi`/`en`), **không** theo `Accept-Language` của trình duyệt; khách: Sanctum token / session cookie |
| **Admin API** | `/api/admin/v1` | Công cụ nội bộ, POS, script vận hành (Admin UI dùng Inertia qua route web, **không** cần API này) | Sanctum token của nhân viên + RBAC theo scope |
| **Integration API** | `/api/integration/v1` và `/api/integrations/{system}/webhooks` | Integration Client (ERP, POS, ODO khi có), hãng VC, cổng TT | Client credentials (API key + secret, HMAC chữ ký), IP allowlist tuỳ hệ thống |

Đặc tả mỗi nhóm viết bằng **OpenAPI 3.1** trong `docs/06-api/openapi/*.yaml`, tạo **cùng lúc** với code của endpoint (không viết trước, để tránh lệch với implementation).

## 2. Quy ước chung

- JSON, `snake_case`, UTF-8. Thời gian ISO-8601 có offset (`2026-09-28T10:15:00+07:00`).
- **Tiền** trả về dạng object: `{ "amount": 1250000, "currency": "VND", "formatted": "1.250.000 ₫" }`.
- ID công khai dùng `public_id` (ULID), **không** lộ ID tự tăng.
- Phân trang cursor: `?cursor=...&limit=20` → `{ data: [...], meta: { next_cursor } }`.
- Lọc/sắp xếp: `?filter[status]=confirmed&sort=-placed_at`.
- Response dùng **Eloquent API Resource** bọc DTO của Application layer; không trả model thô.
- Mọi response có header `X-Correlation-Id` ([observability](../16-observability/observability.md)).
- Versioning theo URL (`v1`); thay đổi phá vỡ → `v2`, giữ `v1` tối thiểu 6 tháng.
- **Idempotency**: request tạo tài nguyên (đặt hàng, hoàn tiền, tạo shipment) nhận header `Idempotency-Key`; lưu kết quả 24h.
- Rate limit: storefront theo IP + token; admin theo nhân viên; integration theo client (header `X-RateLimit-*`, vượt thì `429`).
- **Authorization** hai lớp: scope của token (integration) hoặc permission (admin) + **data scope** (brand/legal entity/location) + **ownership** dữ liệu (integration, [erp-integration](../11-integration/erp-integration.md)).

### Định dạng lỗi

```json
{
  "error": {
    "code": "inventory.insufficient_stock",
    "message": "Sản phẩm Áo sơ mi lụa (M) chỉ còn 1.",
    "details": [{ "field": "lines.0.quantity", "available": 1 }],
    "correlation_id": "01J9Z8..."
  }
}
```

Mã lỗi dạng `<module>.<lý_do>` (lỗi HTTP chung: `http.<status>`, validation: `validation.failed` kèm `details[{field, messages}]`), ổn định để client xử lý; `message` theo locale của request. **Implemented** (`Modules\Shared\Http\ApiErrorRenderer`).

## 3. Storefront API: endpoint chính

| Method | Path | Mô tả |
|---|---|---|
| GET | `/categories`, `/categories/{slug}` | Cây danh mục đang hiển thị của các brand trong kênh (**Implemented**) |
| GET | `/products?q=&category=&collection=&color=white,black&attr[material]=silk&sort=newest|code&page=&per_page=` | Danh sách/tìm kiếm + facet (`meta.facets.color_families`, `meta.facets.attributes`). Trong một nhóm lọc là OR, giữa các nhóm là AND. **Implemented** |
| GET | `/products/{slug}` | Chi tiết style: tên, mô tả, hướng dẫn bảo quản, SEO, breadcrumb, thuộc tính spec, màu + ảnh. **Implemented**, gồm `variants[].price`, `variants[].available`, `variants[].low_stock`, `price` (khoảng giá), `in_stock`. Danh sách `/products` có `price` và `in_stock`. Không trả số tồn chính xác |
| GET | `/catalog/products/{slug}/store-availability?province=` | Tồn theo cửa hàng (mức độ, không số chính xác) |
| POST | `/carts` | Tạo giỏ |
| GET/PATCH | `/carts/{id}` | Xem giỏ (kèm totals) |
| POST/PATCH/DELETE | `/carts/{id}/lines[/{lineId}]` | Thêm/sửa/xoá dòng |
| POST/DELETE | `/carts/{id}/vouchers` | Áp/gỡ voucher (Promotion framework của Core) |
| GET | `/carts/{id}/shipping-options` | Phương thức VC + phí |
| GET | `/carts/{id}/payment-methods` | Phương thức thanh toán khả dụng |
| POST | `/checkout/{cartId}/orders` | Đặt hàng (Idempotency-Key) → order + payment initiation |
| GET | `/orders/track?number=&phone=` | Tra cứu đơn không cần đăng nhập |
| POST | `/auth/otp/request`, `/auth/otp/verify` | Đăng nhập OTP |
| GET/PATCH | `/me`, `/me/addresses`, `/me/orders`, `/me/loyalty` (plugin `vani.loyalty`), `/me/wishlist` (plugin `vani.wishlist`) | Tài khoản |
| POST | `/me/returns` | Tạo yêu cầu đổi trả |
| GET | `/geo/provinces`, `/geo/provinces/{code}/wards`, `/geo/search?q=` | Địa giới hành chính |
| GET | `/search?q=&filter[...]` | Tìm kiếm qua `SearchProvider` (facet màu, size còn hàng, giá) |
| GET | `/cms/pages/{slug}`, `/cms/menus/{code}`, `/cms/home` | Nội dung, menu, block trang chủ |

## 4. Admin API: nhóm tài nguyên

Admin UI (Inertia) dùng route web + controller cùng Application layer. Admin API phục vụ POS, công cụ nội bộ, script vận hành.

| Nhóm | Tài nguyên |
|---|---|
| Catalog | `styles`, `variants`, `categories`, `collections`, `attributes`, `media`, `imports` |
| Pricing | `price-lists`, `prices` |
| Inventory | `locations`, `stock-levels` (đọc + `adjustments` có lý do), `transfers`, `reconciliations` |
| Order | `orders` (+ `transitions`, `notes`, `shipments`, `refunds`), `returns` |
| Customer | `customers` (+ `merge`, `consents`), `customer-groups` |
| Brand/Channel | `legal-entities`, `brands`, `channels`, `domains`, `settings` |
| Promotion | `promotions`, `vouchers` (+ `generate`) |
| Plugin | `plugins` (+ `enable`, `disable`, `settings`) |
| Integration | `integration/clients`, `integration/keys`, `integration/subscriptions`, `integration/messages` (xem/replay), `integration/mappings` |
| Identity | `staff`, `roles`, `audit-logs` |

Plugin thêm tài nguyên riêng dưới `/api/admin/v1/plugins/{code}/…`.

Mọi endpoint Admin kiểm tra **Policy** + phạm vi brand của nhân viên ([security](../15-security/security.md)).

## 5. Integration API

### 5.1 Nhận vào (inbound)

| Method | Path | Nguồn | Nội dung |
|---|---|---|---|
| * | `/api/integration/v1/*` | Integration Client | Đọc/ghi đơn, fulfillment, tồn, mã hàng, giá, đổi trả — danh sách đầy đủ ở [integration-platform §4](../11-integration/integration-platform.md) |
| POST | `/api/integrations/{plugin}/…` | Dịch vụ có connector plugin (cổng TT, hãng VC, sàn) | Webhook/IPN do plugin đăng ký qua `webhookRoutes()`; xác thực chữ ký theo từng dịch vụ rồi lưu inbox |

Envelope chuẩn cho message nội bộ/đối tác:

```json
{
  "event_id": "c1f3c7a2-...",
  "event_type": "inventory.adjusted",
  "schema_version": "1",
  "occurred_at": "2026-09-28T10:15:00+07:00",
  "source": "erp",
  "correlation_id": "01J9Z8Q4M3T6W7X8Y9Z0A1B2C3",
  "data": { "location_code": "WH-HN-01", "sku": "LM24FW-SH012-IVR-M", "on_hand": 42, "version": 18233 }
}
```

Chữ ký: header `X-Vani-Signature: t=<unix>,v1=<hex(hmac_sha256(secret, t + "." + body))>`; từ chối nếu lệch thời gian > 5 phút.

### 5.2 Gửi ra (outbound)

- Payload canonical có version (`vanishop.order.v1`), connector dịch sang định dạng đích.
- VaniShop gửi webhook cho đối tác cùng envelope + chữ ký, retry theo outbox.

## 6. Versioning

- URL version cho cả ba nhóm (`/v1`). Trong cùng version chỉ được thêm field/endpoint tuỳ chọn.
- Thay đổi phá vỡ → `v2`, chạy song song `v1` ít nhất 6 tháng; response `v1` có header `Deprecation` và `Sunset`.
- Payload canonical của integration có `schema_version` riêng, độc lập với URL version.

## 7. Kiểm thử API

- Mỗi endpoint có Feature test (Pest) cho: thành công, validation, phân quyền/phạm vi brand, idempotency (nếu có).
- Contract test cho Integration API dựa trên JSON Schema của từng `event_type`.
