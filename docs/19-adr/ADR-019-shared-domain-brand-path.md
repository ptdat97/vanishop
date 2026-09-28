# ADR-019 — Một domain chung, storefront brand theo đường dẫn

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner

## Context
Mỗi brand cần storefront riêng (giao diện, catalog, giá, khuyến mãi), nhưng Owner muốn toàn bộ chạy trên **một domain chung**.

## Problem
Chọn cách tổ chức URL cho nhiều brand.

## Decision
- Một domain chung (cấu hình bằng `APP_URL`, ví dụ `vani.vn`). Mỗi brand là một **channel web** có `path_prefix = /{brand-slug}`:
  `vani.vn/lumiere/…`, `vani.vn/urbanx/…`.
- Mỗi storefront brand vẫn tách riêng: theme tokens, catalog, bảng giá, khuyến mãi, giỏ hàng, checkout, SEO.
- Phần dùng chung đặt ở gốc domain: trang tập đoàn `/`, tài khoản khách `/tai-khoan`, API `/api`, Admin (`VANI_ADMIN_PATH`, [ADR-020](ADR-020-admin-path-no-2fa.md)).
- Slug brand không được trùng các đường dẫn dành riêng.
- Schema `channel_domains(host, path_prefix)` giữ nguyên, nên sau này vẫn có thể gắn domain riêng cho một brand mà không phải sửa code.

## Alternatives
- Mỗi brand một domain: nhận diện thương hiệu mạnh hơn, nhưng phải quản lý nhiều domain/SSL và đăng nhập chéo domain phức tạp.
- Subdomain (`lumiere.vani.vn`): trung gian, cookie chia sẻ được, nhưng SEO bị tách thành nhiều host.

## Consequences
- (+) Một phiên đăng nhập, một cookie cho mọi brand; SEO dồn về một domain; một chứng chỉ SSL.
- (+) Chỉ một website cần thông báo với Bộ Công Thương.
- (−) **Pháp lý**: một website bán hàng của **nhiều pháp nhân** có thể bị xem là sàn giao dịch TMĐT (phải đăng ký, không chỉ thông báo). Pháp chế cần xác định mô hình trước khi go-live ([vietnam-localization §6](../03-domains/vietnam-localization.md)).
- (−) Nhận diện domain của từng brand yếu hơn; tracking/analytics phải tách theo đường dẫn.

## Trade-offs
Đơn giản vận hành và SEO tập trung, đổi lại nhận diện domain riêng cho từng brand.
