# VaniShop — Tài liệu kiến trúc

> **VaniShop** là một **Commerce Kernel** xây trên Laravel theo kiểu Modular Monolith + DDD + API-first + Plugin Architecture. Nền tảng phục vụ single/multi-brand, multi-channel, fashion commerce, headless, ERP integration, và cho phép thêm nghiệp vụ mới (marketplace, creator, loyalty…) **bằng plugin, không fork Core**.
>
> Chủ sở hữu: 1 Owner (1 tập đoàn) với nhiều thương hiệu thời trang, thị trường Việt Nam. Mã nguồn viết mới 100% theo quy trình clean-room ([01-principles/clean-room-license.md](01-principles/clean-room-license.md)).

```text
                    ┌─────────────────────┐
                    │   Business Plugins  │  custom/plugin/*
                    │ Payment · Shipping  │
                    │ Promotion rules     │
                    │ ERP · Marketplace   │
                    │ Creator · Loyalty   │
                    └──────────┬──────────┘
                               │  Extension Points (Contract · Event · Hook · Registry)
                    ┌──────────▼──────────┐
                    │   Commerce Kernel   │  modules/*
                    │ Catalog · Pricing   │
                    │ Inventory · Cart    │
                    │ Checkout · Order    │
                    │ Payment · Fulfill.  │
                    └──────────┬──────────┘
                    ┌──────────▼──────────┐
                    │   Infrastructure    │
                    │ MySQL · Redis · S3  │
                    │ Search · Ext. APIs  │
                    └─────────────────────┘
```

## Đọc gì trước

1. [00-overview/status.md](00-overview/status.md): cái gì **đã có code**, cái gì mới chỉ là thiết kế.
2. [01-principles/architecture-rules.md](01-principles/architecture-rules.md): các quy tắc bắt buộc.
3. [02-architecture/commerce-kernel.md](02-architecture/commerce-kernel.md): ranh giới giữa Core và Plugin.
4. [04-extension/extension-model.md](04-extension/extension-model.md): khi nào dùng Contract, Event, Hook.

## Cấu trúc

| Thư mục | Tài liệu |
|---|---|
| **00-overview** | [vision](00-overview/vision.md) · [status](00-overview/status.md) · [glossary](00-overview/glossary.md) |
| **01-principles** | [architecture-rules](01-principles/architecture-rules.md) · [clean-room-license](01-principles/clean-room-license.md) · [coding-conventions](01-principles/coding-conventions.md) |
| **02-architecture** | [overview](02-architecture/overview.md) · [bounded-contexts](02-architecture/bounded-contexts.md) · [commerce-kernel](02-architecture/commerce-kernel.md) · [money](02-architecture/money.md) · [consistency](02-architecture/consistency.md) |
| **03-domains** | [catalog-pricing](03-domains/catalog-pricing.md) · [customer](03-domains/customer.md) · [cart-checkout](03-domains/cart-checkout.md) · [promotion](03-domains/promotion.md) · [vietnam-localization](03-domains/vietnam-localization.md) |
| **04-extension** | [extension-model](04-extension/extension-model.md) · [extension-point-catalog](04-extension/extension-point-catalog.md) |
| **05-plugin** | [plugin-system](05-plugin/plugin-system.md) · [plugin-catalog](05-plugin/plugin-catalog.md) · specs: [loyalty](05-plugin/specs/loyalty.md), [store-omnichannel](05-plugin/specs/store-omnichannel.md) |
| **06-api** | [api](06-api/api.md) |
| **07-database** | [database](07-database/database.md) |
| **08-inventory** | [inventory](08-inventory/inventory.md) |
| **09-order** | [order](09-order/order.md) · [fulfillment](09-order/fulfillment.md) |
| **10-payment** | [payment](10-payment/payment.md) |
| **11-integration** | [integration-platform](11-integration/integration-platform.md) · [erp-integration](11-integration/erp-integration.md) |
| **12-multi-brand** | [multi-brand](12-multi-brand/multi-brand.md) |
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
- Quyết định kiến trúc được ghi bằng ADR ([19-adr](19-adr/README.md)). Thay đổi quyết định thì tạo ADR mới, ADR cũ đánh dấu *Superseded*.
- Tài liệu được cập nhật **trong cùng PR** với code làm thay đổi hành vi.
- Tài liệu kiến trúc đã đủ để bắt đầu code. Từ đây ưu tiên **implementation theo vertical slice** ([roadmap](20-roadmap/roadmap.md)); chỉ mở rộng tài liệu khi code cần.
