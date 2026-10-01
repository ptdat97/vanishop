# VaniShop — Tài liệu kiến trúc

> **VaniShop** là một **microkernel thương mại** trên Laravel (Modular Monolith + DDD + API-first): lõi nhỏ giữ primitive, bất biến và extension point; **nghiệp vụ thêm bằng plugin** ([ADR-029](19-adr/ADR-029-commerce-microkernel.md)). Một bản cài đặt là **một cửa hàng thời trang**: một website, một giao diện, nhiều thương hiệu được xếp như nhóm sản phẩm trong catalog ([ADR-028](19-adr/ADR-028-single-store-brand-as-catalog.md)). Hỗ trợ storefront SSR và headless, ERP integration, và thêm nghiệp vụ mới (loyalty, marketplace, creator…) **bằng plugin, không fork Core**.
>
> Chủ sở hữu: 1 Owner vận hành 1 website bán hàng tại Việt Nam; các thương hiệu thời trang của Owner là thuộc tính sản phẩm (trang brand, bộ lọc brand), không phải storefront riêng. Mã nguồn viết mới 100% theo quy trình clean-room ([01-principles/clean-room-license.md](01-principles/clean-room-license.md)).

```text
                ┌───────────────────────────────────────┐
                │ 3. Plugin nghiệp vụ    custom/plugin/* │
                │ Cổng TT · Hãng VC · Rule khuyến mãi ·  │
                │ Loyalty · HĐĐT · ERP · Marketplace ·   │
                │ Creator · Báo cáo · Wishlist …         │
                ├───────────────────────────────────────┤
                │ 2. Plugin hệ thống (bundled)           │
                │ COD · Chuyển khoản · Phí ship · VAT VN │
                └───────────────────┬───────────────────┘
                                    │ Extension Points (Contract · Event · Hook · Registry)
                ┌───────────────────▼───────────────────┐
                │ 1. Commerce Core        modules/*      │
                │ Catalog · Pricing · Inventory · Cart · │
                │ Checkout · Promotion engine · Order ·  │
                │ Payment · Fulfillment · Returns ·      │
                │ Notification · Integration             │
                ├───────────────────────────────────────┤
                │ 0. Microkernel                         │
                │ Extension · Identity · Tenancy · Shared│
                └───────────────────┬───────────────────┘
                ┌───────────────────▼───────────────────┐
                │ Infrastructure  MySQL · Redis · S3 ·   │
                │ Search · External APIs                 │
                └───────────────────────────────────────┘
```

## Đọc gì trước

1. [ADR-028](19-adr/ADR-028-single-store-brand-as-catalog.md) + [12-store/store-and-brand.md](12-store/store-and-brand.md): **định hướng hiện hành** — một cửa hàng, một website, một giao diện, brand là thuộc tính catalog (code còn theo mô hình đa brand cũ cho tới slice 12).
2. [00-overview/status.md](00-overview/status.md): cái gì **đã có code**, cái gì mới chỉ là thiết kế.
3. [02-architecture/commerce-kernel.md](02-architecture/commerce-kernel.md): **bốn vòng** microkernel — cái gì vào Core, cái gì là plugin.
4. [01-principles/architecture-rules.md](01-principles/architecture-rules.md): các quy tắc bắt buộc.
5. [04-extension/extension-model.md](04-extension/extension-model.md): khi nào dùng Contract, Event, Hook.

## Dùng tài liệu nào khi nào

| Tình huống | Đọc |
|---|---|
| Mới vào dự án, cần mental model | [overview](02-architecture/overview.md) → [system-map](02-architecture/system-map.md) |
| Cần biết **hệ thống đang là gì** ở mức mã nguồn (bề mặt, module, middleware, lịch chạy) | [system-map](02-architecture/system-map.md), [request-lifecycle](02-architecture/request-lifecycle.md) |
| Mô hình cửa hàng, brand, lộ trình gỡ đa brand | [store-and-brand](12-store/store-and-brand.md) |
| Muốn biết **vì sao** làm thế này | [19-adr](19-adr/README.md) |
| Đang viết plugin, cần **chữ ký chính xác + checklist** | [05-plugin/contracts](05-plugin/contracts/README.md) |
| Tra extension point / hook public | [extension-point-catalog](04-extension/extension-point-catalog.md), `php artisan vani:plugin:hooks` |
| So với hệ tham chiếu VaniCommerce: học gì, giữ gì | [reference-comparison](02-architecture/reference-comparison.md) (đọc lưu ý clean-room trước) |

## Cấu trúc

| Thư mục | Tài liệu |
|---|---|
| **00-overview** | [vision](00-overview/vision.md) · [status](00-overview/status.md) · [glossary](00-overview/glossary.md) |
| **01-principles** | [architecture-rules](01-principles/architecture-rules.md) · [clean-room-license](01-principles/clean-room-license.md) · [coding-conventions](01-principles/coding-conventions.md) |
| **02-architecture** | [overview](02-architecture/overview.md) · [system-map](02-architecture/system-map.md) · [request-lifecycle](02-architecture/request-lifecycle.md) · [bounded-contexts](02-architecture/bounded-contexts.md) · [commerce-kernel](02-architecture/commerce-kernel.md) · [kernel-review](02-architecture/kernel-review.md) · [money](02-architecture/money.md) · [consistency](02-architecture/consistency.md) · [reference-comparison](02-architecture/reference-comparison.md) |
| **03-domains** | [catalog-pricing](03-domains/catalog-pricing.md) · [customer](03-domains/customer.md) · [cart-checkout](03-domains/cart-checkout.md) · [promotion](03-domains/promotion.md) · [notification](03-domains/notification.md) · [vietnam-localization](03-domains/vietnam-localization.md) |
| **04-extension** | [extension-model](04-extension/extension-model.md) · [extension-point-catalog](04-extension/extension-point-catalog.md) · [CHANGELOG-extension](04-extension/CHANGELOG-extension.md) |
| **05-plugin** | [plugin-system](05-plugin/plugin-system.md) · [plugin-catalog](05-plugin/plugin-catalog.md) · contracts: [lifecycle](05-plugin/contracts/plugin-lifecycle.md), [hook](05-plugin/contracts/hook-signatures.md), [payment-gateway](05-plugin/contracts/payment-gateway.md), [shipping-carrier](05-plugin/contracts/shipping-carrier.md) · specs: [loyalty](05-plugin/specs/loyalty.md), [store-omnichannel](05-plugin/specs/store-omnichannel.md) |
| **06-api** | [api](06-api/api.md) |
| **07-database** | [database](07-database/database.md) |
| **08-inventory** | [inventory](08-inventory/inventory.md) |
| **09-order** | [order](09-order/order.md) · [fulfillment](09-order/fulfillment.md) |
| **10-payment** | [payment](10-payment/payment.md) |
| **11-integration** | [integration-platform](11-integration/integration-platform.md) · [erp-integration](11-integration/erp-integration.md) |
| **12-store** | [store-and-brand](12-store/store-and-brand.md) (mô hình một cửa hàng + lộ trình gỡ đa brand khỏi code) |
| **13-marketplace** | [marketplace](13-marketplace/marketplace.md) · [creator-affiliate](13-marketplace/creator-affiliate.md) |
| **14-storefront** | [storefront](14-storefront/storefront.md) |
| **15-security** | [security](15-security/security.md) |
| **16-observability** | [observability](16-observability/observability.md) |
| **17-testing** | [testing](17-testing/testing.md) |
| **18-operations** | [operations](18-operations/operations.md) |
| **19-adr** | [Danh sách ADR](19-adr/README.md) |
| **20-roadmap** | [roadmap](20-roadmap/roadmap.md) |

## Quy ước tài liệu

- Mỗi tài liệu mở đầu bằng dòng **Trạng thái**, lấy một trong bốn giá trị: `Implemented` · `Partially Implemented` · `Designed` · `Planned`. Trạng thái tổng hợp nằm ở [status.md](00-overview/status.md). **Không ghi `Implemented` khi code chưa tồn tại.**
- Mỗi chủ đề chỉ có **một tài liệu gốc**, các nơi khác dẫn link đến đó, không chép lại.
- Tài liệu mô tả code (system-map, request-lifecycle, contracts) ghi **theo code**: số liệu kèm lệnh đếm lại, chữ ký chép từ `Contracts/`. Chỗ code còn thiếu ghi ở mục *Giới hạn hiện tại*, không giấu.
- Quyết định kiến trúc được ghi bằng ADR ([19-adr](19-adr/README.md)). Thay đổi quyết định thì tạo ADR mới, ADR cũ đánh dấu *Superseded*.
- Tài liệu được cập nhật **trong cùng PR** với code làm thay đổi hành vi.
- Tài liệu kiến trúc đã đủ để bắt đầu code. Từ đây ưu tiên **implementation theo vertical slice** ([roadmap](20-roadmap/roadmap.md)); chỉ mở rộng tài liệu khi code cần.
