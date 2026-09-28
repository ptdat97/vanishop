# 16 — Quy ước code & Kiểm thử

> Bổ sung cho `AGENTS.md` / `CLAUDE.md` (Laravel Boost guidelines). Khi mâu thuẫn, `AGENTS.md` được ưu tiên, trừ khi có ADR mới thay đổi.

## 1. Cấu trúc thư mục

```
vanishop/
├── app/                 # Khung ứng dụng: Providers, middleware chung, Console
│   └── Providers/
│       └── ModuleServiceProvider.php   # đăng ký ServiceProvider của từng module + loader plugin
├── modules/             # Module nghiệp vụ (namespace Modules\\)
│   ├── Tenancy/
│   ├── Catalog/
│   ├── Pricing/
│   ├── Inventory/
│   ├── Customer/
│   ├── Checkout/        # Cart + Totals pipeline + PlaceOrder
│   ├── Ordering/
│   ├── Payment/
│   ├── Fulfillment/
│   ├── Returns/
│   ├── Content/
│   ├── Integration/     # Integration API, client, webhook, outbox/inbox, connector framework
│   ├── Identity/        # Staff, RBAC, audit
│   ├── Notification/
│   ├── Reporting/
│   ├── Extension/       # Hook, plugin loader
│   └── Shared/          # Money, Phone, enums, CurrentContext — không chứa nghiệp vụ
├── custom/
│   ├── plugin/          # Plugin/connector (namespace Plugin\\) — xem 10
│   └── theme/           # Theme storefront theo brand (xem 10 §4)
├── resources/
│   ├── js/              # Entry Admin Inertia (app.ts), layout, component Vue dùng chung
│   ├── css/
│   └── views/           # Layout gốc Blade (app.blade.php cho Inertia, storefront base)
└── tests/
```

Cấu trúc bên trong module: xem [02 §4](02-kien-truc-tong-the.md). Namespace: `Modules\<Module>\...`.

## 2. Quy ước code

| Chủ đề | Quy ước |
|---|---|
| Style | Laravel Pint (preset dự án); chạy `vendor/bin/pint --dirty --format agent` trước khi commit |
| Kiểu | `declare(strict_types=1);` ở file mới; type hint & return type đầy đủ; PHPDoc array shape |
| Constructor | Property promotion |
| Enum | Backed enum, key TitleCase (`OrderStatus::Confirmed`) |
| Use case | Class `Actions/<Verb><Noun>` có 1 method public `handle()`/`__invoke()`, nhận DTO |
| DTO | `final readonly class`, tạo từ Form Request (`toData()`) |
| Controller | Mỏng: validate (Form Request) → gọi Action → trả View/Resource |
| Model | Không chứa logic nghiệp vụ phức tạp; `$fillable` tường minh; cast enum/money |
| Tiền | Luôn dùng `Money`; cấm `float` cho tiền |
| Query | Tránh N+1 (`preventLazyLoading` bật ở local/test); eager load rõ ràng |
| Transaction | Action ghi nhiều bảng phải bọc `DB::transaction`; không gọi HTTP bên trong transaction |
| Thời gian | `CarbonImmutable`; so sánh theo UTC; hiển thị theo `Asia/Ho_Chi_Minh` |
| Dịch | Key dịch theo module: `ordering::status.confirmed`; không hard-code chuỗi tiếng Việt trong PHP |
| Admin UI | Inertia + Vue 3 + TypeScript (`<script setup lang="ts">`); trang đặt tại `modules/<M>/resources/js/Pages/`, render bằng `Inertia::render('<Module>::<Trang>')`; props lấy qua API Resource, không truyền model thô; quyền hiển thị nút/menu lấy từ shared prop `can` (kiểm tra lại ở server) |
| Route | Named route: `storefront.product.show`, `admin.orders.index`, `api.storefront.v1.carts.store` |
| Artisan | Tạo file bằng `php artisan make:*` rồi di chuyển vào module; lệnh riêng tiền tố `vani:` |
| Dependency | Mọi package mới cần ADR/phê duyệt; license MIT/BSD/Apache-2.0/ISC |

### Tên gọi thống nhất (ubiquitous language)

Dùng đúng thuật ngữ trong [glossary.md](glossary.md) cho tên class/bảng: `Style`, `Variant`, `Location`, `StockLevel`, `Reservation`, `Channel`, `LegalEntity`, `Shipment`, `ReturnRequest`… Không dùng từ đồng nghĩa lẫn lộn (`Product` vs `Item` vs `Goods`).

## 3. Kiểm thử (Pest)

| Loại | Phạm vi | Vị trí |
|---|---|---|
| Unit | Domain thuần: Money, pipeline tính tiền, luật khuyến mãi, state machine, chuẩn hoá SĐT/địa chỉ | `tests/Unit/<Module>/` |
| Feature | HTTP/Action có DB: checkout, đặt hàng, phân quyền, webhook | `tests/Feature/<Module>/` |
| Integration contract | Payload Integration API/webhook khớp JSON Schema; fake connector & fake client | `tests/Integration/` |
| Concurrency | Reservation không oversell (nhiều process song song trên MySQL) | `tests/Concurrency/` (chạy trong CI với MySQL 8.4) |
| Browser/E2E | Luồng mua hàng chính trên mobile viewport | `tests/Browser/` |

### Test bắt buộc

- [ ] Mỗi model có phạm vi brand: test cô lập dữ liệu giữa brand.
- [ ] Mỗi transition trạng thái đơn: hợp lệ + không hợp lệ.
- [ ] Totals pipeline: làm tròn VNĐ, phân bổ giảm giá, chồng KM.
- [ ] Webhook: chữ ký sai bị từ chối, gửi trùng không xử lý 2 lần, bản cũ không ghi đè bản mới.
- [ ] Mỗi endpoint Admin: nhân viên thiếu quyền/ngoài phạm vi nhận 403.

### Quy tắc

- Dùng factory (có state: `->forBrand($brand)`, `->outOfStock()`); không tạo dữ liệu thủ công dài dòng.
- Fake mọi gọi ra ngoài (`Http::fake()`, fake connector); CI không gọi dịch vụ thật.
- Chạy test hẹp khi phát triển: `php artisan test --compact --filter=...`; toàn bộ suite trước khi merge.
- Mục tiêu coverage: ≥ 80% cho `Domain/` và `Actions/` của module core (Checkout, Ordering, Inventory, Payment, Fulfillment) và của plugin chính thức xử lý tiền (Promotion, Loyalty).

## 4. Git & Review

- Nhánh: `feat/<module>-<mô-tả>`, `fix/...`, `docs/...`; commit theo Conventional Commits (`feat(inventory): add reservation TTL`).
- PR nhỏ (< 400 dòng thay đổi logic nếu có thể), mô tả: mục đích, tài liệu docs liên quan, ảnh chụp UI, **checklist clean-room**.
- Tối thiểu 1 reviewer; thay đổi Checkout/Payment/Inventory cần 2.
- Thay đổi hành vi → cập nhật `docs/` trong cùng PR.

## 5. Definition of Done

- [ ] Code + test pass, Pint, Larastan sạch.
- [ ] Phân quyền & phạm vi brand đã kiểm tra.
- [ ] Chuỗi hiển thị đã dịch (`vi` bắt buộc).
- [ ] Log/audit cho thao tác nhạy cảm.
- [ ] Tài liệu cập nhật; hook mới đã khai báo registry.
- [ ] Checklist clean-room đã tích.
