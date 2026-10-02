# ADR-030 — Bề mặt mở rộng v2: plugin bổ sung capability qua registry có kiểu

- Trạng thái: Proposed · Ngày: 2026-10-02 · Người quyết định: Owner
- Mở rộng: [ADR-004](ADR-004-extension-points.md), [ADR-029](ADR-029-commerce-microkernel.md). Liên quan: [ADR-025](ADR-025-native-storefront-ssr-slots.md), [ADR-027](ADR-027-plugin-data-no-core-columns.md).
- Tài liệu gốc: [extension-surface-v2](../04-extension/extension-surface-v2.md).

## Context
Extension point hiện có đủ cho capability **thay thế** (cổng, hãng, thuế, rule, kênh gửi) nhưng thiếu cho capability **bổ sung**: loyalty, hoá đơn điện tử, size chart, wishlist, marketplace cần thêm trường/cột/thao tác vào màn hình Admin của Core, thêm dữ liệu vào sản phẩm/giỏ/đơn trả cho storefront, thêm trang và API riêng, thuộc tính trên dòng giỏ. Nghiên cứu hệ tham chiếu (BeikeShop v3.0.0.11, vai trò nghiên cứu, chỉ khái niệm) cho thấy nó giải quyết bằng điểm lọc dữ liệu view + điểm sau-lưu ở gần như mọi màn hình và điểm bọc quanh khối theme.

## Problem
Làm sao để plugin bổ sung capability ở Admin, storefront và luồng giao dịch mà không sửa `modules/`, không làm mất kiểu dữ liệu, không để plugin xoá/đổi phần của Core hay plugin khác?

## Decision
1. Mở rộng theo **resource nghiệp vụ**, không theo controller/route.
2. Admin: **registry khai báo** trên `PluginServiceProvider` (`adminFormSection`, `adminColumn`, `adminAction`, `adminTab`, `adminFilter`) với `FieldDefinition` có kiểu; Admin render chung; `save` chạy trong transaction lưu của Core. Admin API dùng cùng khai báo.
3. Storefront: contract `StorefrontEnricher` (batch, ở Presenter, kết quả chỉ nằm dưới `extensions.<plugin-id>`), thêm slot theo ADR-025, registry `storefrontRoutes`/`storefrontPages`/`accountPages`.
4. Giao dịch: `meta` có không gian tên trên `cart_lines`/`order_lines` (dòng đơn chụp lại), `CartLineOption`, `vani.checkout.context`, authorize/capture tuỳ chọn.
5. Mọi chuyển trạng thái nghiệp vụ phát event sau commit (bổ sung `OrderCompleted`, `CartAbandoned`, `PaymentAuthorized`…).
6. Plugin công bố hook/contract cho plugin khác (`hooks.php` của plugin, `requires.plugins` bắt buộc); `kind` manifest chuẩn hoá.
7. Giữ nguyên các điều **không** mở rộng (extension-model §2): máy trạng thái, công thức ATS, totals cuối, cột bảng Core, viết lại HTML.

## Alternatives
- **Điểm lọc mảng dữ liệu ở mọi controller** (cách hệ tham chiếu): phủ rộng nhanh, nhưng không kiểu, vỡ khi đổi controller, plugin sửa được dữ liệu của Core. Loại.
- **Plugin tự viết trang Admin thay cho mở rộng màn hình Core**: đã có (`adminPages`), nhưng người dùng phải nhảy trang; không đủ cho cột/bộ lọc/nút trên đơn.
- **Cho plugin thêm cột vào bảng Core**: trái ADR-027.

## Consequences
- (+) Plugin catalog P1 → Later viết được không sửa Core (bảng kiểm chứng ở tài liệu gốc §7).
- (+) Admin, Admin API, native, Storefront API dùng chung một khai báo → không nhân đôi.
- (−) Thêm bề mặt public lớn: mỗi registry là cam kết compatibility; làm theo đợt, chỉ khi có plugin tham chiếu (R26).
- (−) Admin Vue cần bộ render form/cột/tab chung.

## Trade-offs
Phủ ít điểm hơn hệ tham chiếu, đổi lấy điểm mở rộng có kiểu, có không gian tên và sống qua việc đổi màn hình.
