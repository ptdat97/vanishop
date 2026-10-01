# ADR-025 — Native storefront: SSR-first, JS tăng cường cục bộ, slot UI chỉ nối thêm

- Trạng thái: Accepted · Ngày: 2026-09-30 · **Sửa đổi bởi [ADR-028](ADR-028-single-store-brand-as-catalog.md)**: "theme brand" đọc là theme đang hoạt động của cửa hàng · Bổ sung cho [ADR-009](ADR-009-storefront-architecture.md), [ADR-021](ADR-021-storefront-composition-module.md) · Nghiên cứu: [reference-comparison](../02-architecture/reference-comparison.md) L2, L3

## Context
ADR-009 chốt storefront native là Blade SSR + Alpine, dùng chung Application layer với Storefront API. Chưa chốt: JS được làm đến đâu, plugin chèn UI vào theme bằng cách nào, và khi slot không đủ thì làm gì. Hệ tham chiếu cho thấy ba điều: (1) SSR + JS cục bộ cho SEO và trang vẫn dùng được khi JS lỗi; (2) theme có sẵn nhiều điểm chèn UI giúp plugin thêm giao diện không cần sửa view; (3) lối thoát "viết lại HTML của view bất kỳ lúc render" làm plugin phụ thuộc vào cấu trúc HTML, dễ vỡ.

## Problem
Làm sao để plugin thêm UI vào storefront của nhiều brand mà không fork theme, không để JS lỗi làm mất nội dung, và không tạo phụ thuộc mong manh vào HTML?

## Decision
1. **SSR là nguồn nội dung.** Mọi nội dung public (PDP, PLP, danh mục, trang) render đầy đủ ở server. Alpine chỉ gắn vào **đảo tương tác** (gallery, chọn màu/size, mini-cart, form checkout).
2. **Không** mount một ứng dụng JS toàn trang, **không** router phía client thay điều hướng server.
3. Dữ liệu cho JS đi qua **JSON bridge đã render sẵn** (`@js`/`data-*`), không gọi API lấy lại thứ server đã có. Phần cá nhân hoá (giỏ, giá thành viên) tải qua Storefront API sau khi trang hiện.
4. **Không ẩn nội dung SSR chờ JS.** Nếu cần tránh nhảy layout, việc hiện lại không được phụ thuộc JS chạy thành công (CSS mặc định hiện; `<noscript>`).
5. **Slot UI storefront** là hook `type: slot` khai báo trong registry như mọi hook khác. Listener trả **một view component** (view của plugin + dữ liệu); theme render tại vị trí slot bằng một component chung. Slot **chỉ nối thêm**: plugin không xoá, không thay nội dung của Core hay plugin khác. Lỗi một listener → bỏ phần tử đó, ghi log.
6. **Danh mục slot storefront chốt cùng theme `vani-base`** và ghi vào [extension-point-catalog §4](../04-extension/extension-point-catalog.md) trước khi plugin dùng. Slot là public API theo compatibility policy: đổi/xoá vị trí slot là thay đổi major.
7. **Thay một khối = override view trong theme brand** (`custom/theme/<brand-theme>/` → `vani-base` → view mặc định), không có cơ chế viết lại HTML lúc render.
8. Dữ liệu cho view đến từ Presenter/Query của module `Storefront`; Blade không query DB (R10).

## Alternatives
- **Viết lại HTML lúc render theo selector** (lối thoát của hệ tham chiếu): chèn được vào mọi view, nhưng plugin gắn chặt với cấu trúc HTML, vỡ âm thầm khi theme đổi, tốn chi phí phân tích DOM, khó review. Loại.
- **Slot kiểu "bọc/thay thế"** (listener nhận HTML trước đó và trả HTML mới): linh hoạt nhưng plugin có thể xoá UI của nhau. Loại; thay khối bằng override view.
- **SPA/Next.js cho mọi brand**: đã loại ở ADR-009; brand cần SPA dùng headless.

## Consequences
- (+) SEO và trang vẫn đọc được khi JS lỗi; Storefront API và native dùng cùng Presenter.
- (+) Plugin thêm UI qua slot có khai báo, có kiểm soát lỗi, không phụ thuộc HTML.
- (−) Muốn chèn ở vị trí chưa có slot phải PR Core thêm slot (R1), hoặc override view trong theme brand.
- (−) Phải duy trì danh mục slot như public API.

## Trade-offs
Ít tự do hơn "chèn vào đâu cũng được", đổi lại nâng cấp theme không làm vỡ plugin.

## Bài học thực tế
Ở hệ tham chiếu, một trang sản phẩm ẩn mô tả và cả footer bằng CSS, chỉ hiện lại trong hook `mounted` của JS; JS lỗi thì footer biến mất vĩnh viễn dù nội dung đã render sẵn. → Quyết định 4.
