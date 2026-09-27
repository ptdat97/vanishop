# VaniShop — Tài liệu dự án

> **VaniShop** là nền tảng **Multi-Brand Commerce** tự xây dựng từ đầu cho mô hình **1 Owner (1 tập đoàn) — nhiều thương hiệu thời trang**, bán trực tiếp đến người tiêu dùng (D2C), kết nối **ODO** (hệ thống vận hành / đơn hàng / giao nhận — vai trò đang tạm hoãn), **ERP**, cửa hàng vật lý và các kênh bán khác, tối ưu cho **thị trường Việt Nam**.
>
> Dự án **lấy cảm hứng về ý tưởng** từ BeikeShop v3.0.0.11 nhưng **viết lại 100% mã nguồn** theo quy trình clean-room — xem [01-clean-room-va-license.md](01-clean-room-va-license.md) trước khi viết dòng code đầu tiên.

## Trạng thái

| Mục | Giá trị |
|---|---|
| Phiên bản tài liệu | 0.1 (bản khởi tạo) — 2026-09-28 |
| Nền tảng code | Laravel 13, PHP 8.4, MySQL 8.4, Inertia + Vue 3 (Admin), Pest 5, Tailwind CSS 4, Vite 8, `tormjens/eventy` |
| Cấu trúc | Module nghiệp vụ tại `modules/`, plugin tại `custom/plugin/` |
| Giai đoạn | Phase 0 — Nền móng (xem [15-lo-trinh.md](15-lo-trinh.md)) |

## Mục lục

### Phần A — Định hướng
| # | Tài liệu | Nội dung |
|---|---|---|
| 00 | [Tổng quan dự án](00-tong-quan-du-an.md) | Mục tiêu, phạm vi, các bên liên quan, chỉ số thành công |
| 01 | [Clean-room & License](01-clean-room-va-license.md) | Quy tắc bắt buộc để không vi phạm license BeikeShop |
| 02 | [Kiến trúc tổng thể](02-kien-truc-tong-the.md) | Modular monolith, bounded context, tech stack |
| 03 | [Mô hình đa thương hiệu](03-mo-hinh-da-thuong-hieu.md) | Owner → Pháp nhân → Brand → Kênh bán → Kho/Cửa hàng |
| 10 | [Hook, Plugin & Điểm mở rộng](10-hook-va-plugin.md) | **Hợp đồng giữa core và plugin** — đọc trước khi viết plugin |

### Phần B — Core thương mại

> Tài liệu tập trung vào **core**. Tính năng nghiệp vụ được đánh dấu *(plugin)* là yêu cầu đầu vào cho plugin — danh mục ở [17](17-danh-muc-plugin.md).

| # | Tài liệu | Nội dung |
|---|---|---|
| 04 | [Catalog & Giá](04-catalog-va-gia.md) | Style/Variant, ma trận màu–size, thuộc tính, bảng giá |
| 05 | [Tồn kho & Cửa hàng vật lý](05-ton-kho-va-cua-hang.md) | Location, giữ hàng, ATS, BOPIS, ship-from-store |
| 06 | [Đơn hàng, Thanh toán, Giao hàng, Đổi trả](06-don-hang-thanh-toan-giao-hang.md) | Máy trạng thái đa chiều, COD, đối soát |
| 07 | [Khách hàng (core), Khuyến mãi & Loyalty (plugin)](07-khach-hang-khuyen-mai-loyalty.md) | Tài khoản dùng chung toàn tập đoàn; yêu cầu cho plugin KM/loyalty |

### Phần C — Tích hợp & Việt Nam
| # | Tài liệu | Nội dung |
|---|---|---|
| 08 | [Module Integration & API tích hợp](08-module-integration.md) | Integration API, webhook, connector, outbox/inbox, nguồn dữ liệu gốc (ODO tạm hoãn) |
| 09 | [Đặc thù thị trường Việt Nam](09-dac-thu-viet-nam.md) | Địa giới hành chính, VNĐ, hoá đơn điện tử, pháp lý |

### Phần D — Kỹ thuật
| # | Tài liệu | Nội dung |
|---|---|---|
| 11 | [Cơ sở dữ liệu](11-co-so-du-lieu.md) | ERD, quy ước schema, danh sách bảng |
| 12 | [API](12-api.md) | Storefront API, Admin API, Integration API |
| 13 | [Bảo mật & Phân quyền](13-bao-mat-phan-quyen.md) | RBAC theo phạm vi brand, audit, dữ liệu cá nhân |
| 14 | [Hạ tầng & Vận hành](14-ha-tang-van-hanh.md) | Môi trường, queue, cache, search, giám sát |
| 15 | [Lộ trình](15-lo-trinh.md) | Các phase, milestone, tiêu chí hoàn thành |
| 16 | [Quy ước code & Kiểm thử](16-quy-uoc-code-kiem-thu.md) | Cấu trúc thư mục, naming, Pest, review |

### Phần E — Plugin
| # | Tài liệu | Nội dung |
|---|---|---|
| 17 | [Danh mục plugin nghiệp vụ](17-danh-muc-plugin.md) | Thanh toán, vận chuyển, KM, loyalty, omnichannel, HĐĐT, sàn, ERP… và điểm mở rộng mỗi plugin dùng |

### Phụ lục
- [Thuật ngữ](glossary.md)
- [ADR — Architecture Decision Records](adr/README.md)

## Cách dùng bộ tài liệu

1. **Người mới**: đọc 00 → 01 → 02 → 03, sau đó đọc tài liệu domain liên quan đến việc mình làm.
2. **Trước khi code một module**: đọc tài liệu domain + [16](16-quy-uoc-code-kiem-thu.md) + checklist clean-room trong [01](01-clean-room-va-license.md).
3. **Khi thay đổi quyết định kiến trúc**: tạo ADR mới trong [adr/](adr/README.md), không sửa ADR cũ (chỉ đánh dấu *Superseded*).
4. Tài liệu là "living docs": cập nhật cùng PR với code thay đổi hành vi.
