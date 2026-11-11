# ADR-044 — Storefront API-first: API v1 là hợp đồng, Blade là một cách dùng

- Trạng thái: **Accepted** · Ngày: 2026-11-11 · Người quyết định: Owner
- Bổ sung: [ADR-009](ADR-009-storefront-architecture.md) (native và headless dùng cùng Application layer), [ADR-021](ADR-021-storefront-composition-module.md) (tầng ghép Storefront). Sửa đổi: [ADR-022](ADR-022-guest-cart-token.md), [ADR-024](ADR-024-customer-api-token.md) (API nhận thêm phiên web cùng nguồn). Kế hoạch: [api-first-refactor](../20-roadmap/api-first-refactor.md).

## Context

ADR-009 quyết định native storefront (Blade) và Storefront API dùng chung Application layer. Đánh giá ngày 2026-11-11 ([api-first-refactor §3](../20-roadmap/api-first-refactor.md#3-đánh-giá-hiện-trạng)) cho thấy điều đó mới đúng với lớp đọc dữ liệu:

- Luật đầu vào của checkout, tài khoản, đổi trả được viết riêng ở controller Web và controller API.
- Quy tắc 0.3.104 (bỏ mã giảm giá không dùng được rồi vẫn đặt đơn) chỉ có ở controller Web: cùng giỏ, cùng mã hỏng, Blade đặt được đơn còn API bị từ chối.
- Mã giảm giá có hai mô hình: một mã trong phiên (Blade), tối đa 5 mã mỗi lần gọi (API).
- Menu, đầu/chân trang, trang chủ dựng bằng khối chỉ đến Blade qua `View::composer`, không có endpoint.
- JS của theme dùng endpoint JSON riêng (`/_vani/*`, `/p/vani-wishlist/*`) vì API chỉ nhận Bearer/cart token, còn khách trên Blade đăng nhập bằng phiên.
- Storefront API không có OpenAPI hay test schema.

## Problem

Khi hành vi được định nghĩa ở nơi render, mỗi kênh (Blade, app, Zalo Mini App, headless khi doanh nghiệp lớn lên) dần có luật riêng; thêm kênh nghĩa là viết lại luật.

## Decision

1. **Storefront API v1 là hợp đồng của mọi tính năng storefront.** Blade (theme `vani-base`) là một cách dùng hợp đồng đó — cách mặc định cho cửa hàng SME — gọi cùng use-case **trong cùng process** (không tự gọi HTTP, giữ ADR-009).
2. **Hành vi ở use-case, không ở controller.** Mỗi việc khách làm được là một use-case của `Storefront\Application` (hoặc contract của module sở hữu). Controller Web và API chỉ chuyển đổi đầu vào/đầu ra; luật validate viết một lần (FormRequest dùng chung).
3. **Có trên Blade thì có trên API.** Tính năng storefront mới (Core hoặc plugin) có endpoint API trước hoặc cùng lúc với trang Blade. Ngoại lệ chỉ cho phần trình bày web: sitemap, robots, JSON-LD, mã theo dõi quảng cáo, trang trả về của cổng thanh toán.
4. **Một bề mặt JSON.** JS của theme chỉ gọi `/api/storefront/v1`. API nhận thêm **phiên web cho request cùng nguồn** (cookie phiên + token CSRF) bên cạnh `Authorization: Bearer` (ADR-024) và `X-Vani-Cart-Token` (ADR-022). Bỏ `/_vani/*` và JSON phiên của `vani.wishlist`.
5. **Mã giảm giá một mô hình:** danh sách `voucher_codes[]` lưu theo giỏ, dùng chung mọi kênh; Blade hiện một ô nhập.
6. **Mã giảm giá không dùng được khi đặt hàng:** bỏ mã và vẫn đặt đơn ở mọi kênh (quy tắc 0.3.104 vào use-case); API trả mã bị bỏ trong `meta.dropped_vouchers`. Mã hợp lệ làm đổi tổng tiền thì vẫn yêu cầu xác nhận lại như hiện tại (`expected_total`).
7. **Hợp đồng HTTP được khoá:** `docs/api/openapi/storefront-v1.json` + test đối chiếu response thật; đổi phá vỡ theo [ADR-010](ADR-010-api-versioning.md).
8. **Phạm vi:** chỉ Storefront API. Admin API vẫn đóng băng; không làm app/headless mẫu. VaniShop đang ở môi trường dev: refactor không giữ alias route cũ, được đổi HTML/URL Blade, không chuyển dữ liệu phiên/giỏ cũ.

Rule đi kèm: **R37** ([architecture-rules](../01-principles/architecture-rules.md)).

## Alternatives

- **Theme tự giữ Bearer token để gọi API:** không phải sửa middleware, nhưng token lộ cho mọi script trên trang (mã theo dõi, plugin) và luồng đăng nhập Blade phải làm lại. Loại.
- **Giữ `/_vani/*` làm adapter mỏng gọi cùng use-case:** ít việc nhất, nhưng vẫn hai hợp đồng JSON không có version. Loại.
- **Blade gọi API qua HTTP nội bộ:** thêm độ trễ, là distributed monolith (ADR-009 đã loại). Loại.
- **Chuyển storefront sang SPA/Next.js:** thêm hạ tầng Node, SEO/cache phức tạp, trái R36 ở giai đoạn SME. Loại.

## Consequences

- (+) Mọi kênh cùng hành vi; thêm app/headless khi doanh nghiệp lớn lên không phải viết lại luật.
- (+) Header, footer, trang chủ (kể cả khối của plugin) dựng được từ API.
- (+) Đổi hợp đồng JSON làm đỏ test.
- (−) Thêm lớp use-case + FormRequest dùng chung; controller Web mỏng đi nhưng số file tăng.
- (−) API có hai cách xác thực khách (token và phiên cùng nguồn) — middleware và test bảo mật phải phủ cả hai.
- (−) Plugin storefront phải làm cả endpoint API, không chỉ trang Blade.

## Trade-offs

Thêm một lớp use-case và một cách xác thực để đổi lấy một nguồn hành vi cho mọi kênh, trong khi vẫn giữ storefront Blade render phía server, chạy in-process và không thêm hạ tầng.
