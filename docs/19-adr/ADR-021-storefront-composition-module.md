# ADR-021 — Module Storefront làm tầng ghép

- Trạng thái: Accepted · Ngày: 2026-10-01 · Bổ sung cho [ADR-009](ADR-009-storefront-architecture.md)

## Context
Slice 3 thêm Pricing. Pricing phụ thuộc Catalog (giá gắn với variant). API sản phẩm của storefront cần cả dữ liệu Catalog lẫn giá; về sau còn cần tồn kho (Inventory) và khuyến mãi.

## Problem
Nếu API sản phẩm nằm trong Catalog và gọi Pricing, sẽ có phụ thuộc vòng Catalog ↔ Pricing, và càng thêm module thì càng rối.

## Decision
- Tạo module **`modules/Storefront`**: không có bảng riêng, chỉ ghép dữ liệu từ contract của các module Core (`CatalogReader`, `PriceResolver`, sau này `AvailabilityReader`…).
- Toàn bộ `/api/storefront/v1` nằm trong Storefront. Native storefront (Blade) sau này dùng cùng lớp `ProductViews`.
- Catalog công bố `CatalogReader` (đọc catalog hiển thị) và `VariantDirectory` (tra variant cho module khác); Pricing công bố `PriceResolver`.

## Alternatives
- Để Catalog lắng nghe hook để Pricing "bơm" giá vào: dùng hook cho giao tiếp giữa các module Core làm luồng dữ liệu khó theo dõi.
- Gộp Pricing vào Catalog: trái ranh giới context, và giá sẽ thay đổi theo nghiệp vụ khác hẳn catalog.

## Consequences
- (+) Phụ thuộc một chiều: Storefront → {Catalog, Pricing, …}; Pricing → Catalog. Không có vòng.
- (−) Thêm một module và một lớp DTO dạng mảng giữa các module.
