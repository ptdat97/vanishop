# ADR-029 — Microkernel thương mại: bốn vòng, nghiệp vụ là plugin

- Trạng thái: Accepted · Ngày: 2026-10-01 · Người quyết định: Owner
- Mở rộng: [ADR-003](ADR-003-plugin-architecture.md), [ADR-004](ADR-004-extension-points.md). Liên quan: [ADR-028](ADR-028-single-store-brand-as-catalog.md), [kernel-review](../02-architecture/kernel-review.md).
- Tài liệu gốc: [commerce-kernel](../02-architecture/commerce-kernel.md).

## Context
ADR-003/004 đã tách Core và plugin, nhưng ranh giới còn rộng: Core vẫn chứa các mặc định mang **chính sách kinh doanh hoặc đặc thù thị trường** (COD, chuyển khoản thủ công, phí ship cố định, VAT Việt Nam), và chưa phân biệt phần **nền tảng** (plugin, hook, quyền, cấu hình) với phần **thương mại**. Owner muốn VaniShop đi theo hướng microkernel: lõi nhỏ, ổn định; nghiệp vụ thêm bằng plugin.

## Problem
Làm sao để mọi thay đổi nghiệp vụ (cổng, hãng, luật khuyến mãi, thuế, chính sách đổi trả, loyalty…) không chạm vào Core, mà vẫn giữ được các bất biến phải chạy chung transaction (tồn, đơn, tiền)?

## Decision
1. **Bốn vòng**, phụ thuộc chỉ hướng vào trong:

   | Vòng | Gồm | Biết gì |
   |---|---|---|
   | **0. Microkernel** | Shared, Tenancy (cửa hàng), Identity, Extension | Không biết thương mại: vòng đời plugin, registry extension, hook bus, event bus theo plugin, cấu hình, quyền, audit, `Money`, idempotency, context |
   | **1. Commerce Core** | Catalog, Pricing, Inventory, Customer, Cart, Promotion (engine), Checkout (totals engine), Ordering, Payment (abstraction + sổ giao dịch), Fulfillment (abstraction), Returns (khung), Notification (khung), Integration (khung), Storefront (tầng ghép) | Primitive + bất biến + extension point. **Không** chứa tích hợp nhà cung cấp, **không** chứa chính sách kinh doanh/đặc thù thị trường |
   | **2. Plugin hệ thống** | `vani.cod`, `vani.bank-transfer`, `vani.shipping-flat-rate`, `vani.tax-vn-vat` (sau thêm `vani.provinces-vn`, `vani.phone-vn` — 0.3.42) | Mặc định để bán được đơn đầu tiên tại VN; đóng gói sẵn, tự cài + bật khi cài đặt, tắt/thay được |
   | **3. Plugin nghiệp vụ** | Cổng, hãng, rule khuyến mãi, loyalty, ERP, marketplace, creator, báo cáo… | Mọi capability còn lại |

2. **Plugin-first**: capability mới mặc định là plugin. Muốn đưa vào Core phải thoả tiêu chí ở [commerce-kernel §2](../02-architecture/commerce-kernel.md) và có ADR.
3. **Mặc định trung lập ở Core, mặc định chính sách ở plugin hệ thống.** Core chỉ giữ implementation mặc định **trung lập thị trường** cho extension point bắt buộc (vận đơn nhập tay, kênh `mail`, OTP email, tìm kiếm `database`, chọn giá theo priority, ATS chuẩn, sourcing theo hàng đang giữ, đổi trả theo số ngày, action `percent_off`/`amount_off`). COD, chuyển khoản, phí ship cố định, VAT VN chuyển thành plugin hệ thống.
4. **Extension point bắt buộc**: Core khai báo extension point nào phải có ≥ 1 (hoặc đúng 1) implementation đang bật để cửa hàng vận hành (thanh toán, phí giao, thuế, carrier, kênh thông báo). Extension từ chối tắt/gỡ plugin cung cấp implementation cuối cùng; `vani:plugin:doctor` báo lỗi khi thiếu.
5. **Plugin có thể công bố extension point** (contract, event, hook trong `hooks.php` của plugin) cho plugin khác dùng, theo cùng compatibility policy; phụ thuộc khai báo trong `requires.plugins`.
6. Bất biến (reservation, state machine, snapshot, totals guard, outbox, quyền) **ở lại Core** và không có extension point (giữ [extension-model §2](../04-extension/extension-model.md)).

## Alternatives
- **Giữ mặc định trong Core** (hiện trạng): đơn giản, nhưng Core mang chính sách VN (COD, VAT) và mỗi đổi chính sách lại chạm Core.
- **Đẩy cả engine (promotion, totals, reservation) ra plugin**: Core mỏng hơn nhưng phá bất biến cần chung transaction; loại (rule R6, R14).
- **Microservice cho từng capability**: trái R19.

## Consequences
- (+) Core ổn định, đổi nghiệp vụ = đổi/thêm plugin; Core có thể dùng cho thị trường khác mà không sửa.
- (+) Chính các mặc định cũng chạy qua extension point → extension point luôn được kiểm chứng (R26).
- (−) Thêm 4 plugin phải cài khi dựng hệ thống; cần cơ chế "bundled" + "required extension point" trong Extension.
- (−) Code hiện có COD/chuyển khoản/flat rate/VAT trong module (`modules/Payment/Application/Gateways`, `modules/Checkout/Application`) → đã tách ở slice 12d (2026-10-02) ([commerce-kernel §6](../02-architecture/commerce-kernel.md)).

## Trade-offs
Thêm chi phí đóng gói và kiểm tra phụ thuộc để đổi lấy một lõi không mang chính sách kinh doanh.
