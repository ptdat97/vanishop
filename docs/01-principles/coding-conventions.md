# Coding Conventions

> Trạng thái: **Designed**. Chiến lược test: [testing](../17-testing/testing.md). Quy tắc kiến trúc: [architecture-rules](architecture-rules.md).

> Bổ sung cho `AGENTS.md` / `CLAUDE.md` (Laravel Boost guidelines). Khi mâu thuẫn, `AGENTS.md` được ưu tiên, trừ khi có ADR mới thay đổi.

## 1. Cấu trúc thư mục

- Dự án: [overview §4](../02-architecture/overview.md).
- Bên trong module: [bounded-contexts §4](../02-architecture/bounded-contexts.md).
- Bên trong plugin: [plugin-system §2](../05-plugin/plugin-system.md).
- Namespace: `Modules\<Context>\<Layer>\...`, `Plugin\<Name>\...`.

## 2. Quy ước code

| Chủ đề | Quy ước |
|---|---|
| Style | Laravel Pint (preset dự án); chạy `vendor/bin/pint --dirty --format agent` trước khi commit |
| Kiểu | `declare(strict_types=1);` ở file mới; type hint & return type đầy đủ; PHPDoc array shape |
| Constructor | Property promotion |
| Enum | Backed enum, key TitleCase (`OrderStatus::Confirmed`) |
| Use case | `Application/Commands/<Verb><Noun>` (ghi, mở transaction) và `Application/Queries/<Get…>` (đọc); một method public `handle()`, nhận DTO |
| DTO | `final readonly class`, tạo từ Form Request (`toData()`) |
| Controller | Mỏng: validate (Form Request) → gọi Command/Query của Application → trả View/Resource (rule R9) |
| Model | Không chứa logic nghiệp vụ phức tạp; `$fillable` tường minh; cast enum/money |
| Tiền | Luôn dùng `Money`; cấm `float` cho tiền |
| Query | Tránh N+1 (`preventLazyLoading` bật ở local/test); eager load rõ ràng |
| Transaction | Action ghi nhiều bảng phải bọc `DB::transaction`; không gọi HTTP bên trong transaction |
| Thời gian | `CarbonImmutable`; so sánh theo UTC; hiển thị theo `Asia/Ho_Chi_Minh` |
| Dịch | Key dịch theo module: `ordering::status.confirmed`; không hard-code chuỗi tiếng Việt trong PHP |
| Admin UI | Inertia + Vue 3 + TypeScript (`<script setup lang="ts">`); trang đặt tại `modules/<M>/resources/js/Pages/`, render bằng `Inertia::render('<Module>::<Trang>')`; props lấy qua API Resource, không truyền model thô; quyền hiển thị nút/menu lấy từ shared prop `can` (kiểm tra lại ở server) |
| Route | Named route: `storefront.product.show`, `admin.orders.index`, `api.storefront.v1.carts.store` |
| Artisan | Tạo file bằng `php artisan make:*` rồi di chuyển vào module (hoặc dùng `vani:make:*` khi đã có); lệnh riêng tiền tố `vani:` |
| Dependency | Mọi package mới cần ADR/phê duyệt; license MIT/BSD/Apache-2.0/ISC |

### Tên gọi thống nhất (ubiquitous language)

Dùng đúng thuật ngữ trong [glossary.md](../00-overview/glossary.md) cho tên class/bảng: `Style`, `Variant`, `Location`, `StockLevel`, `Reservation`, `Channel`, `LegalEntity`, `Shipment`, `ReturnRequest`… Không dùng từ đồng nghĩa lẫn lộn (`Product` vs `Item` vs `Goods`).

## 3. Kiểm thử

Xem [testing](../17-testing/testing.md). Khi phát triển thì chạy test hẹp: `php artisan test --compact --filter=...`; chạy toàn bộ suite trước khi merge. Mọi gọi ra ngoài phải fake (`Http::fake()`).

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
