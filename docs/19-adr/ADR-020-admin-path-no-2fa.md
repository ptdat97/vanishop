# ADR-020 — Admin: đường dẫn cấu hình được, không dùng 2FA

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner
- Thay thế yêu cầu "bắt buộc 2FA" trước đây trong [security](../15-security/security.md).

## Context
Admin chạy cùng domain với storefront ([ADR-019](ADR-019-shared-domain-brand-path.md)). Owner quyết định **không dùng 2FA** cho nhân viên.

## Problem
Làm sao giảm bề mặt tấn công của Admin khi không có 2FA.

## Decision
- Đường dẫn Admin **cấu hình được** bằng `VANI_ADMIN_PATH` (mặc định `admin` cho dev; production đặt giá trị khó đoán, ví dụ `quan-tri-7f3k`).
- Không có link tới Admin từ storefront. Không khai báo đường dẫn Admin trong `robots.txt`, vì khai báo ở đó sẽ làm lộ nó; thay vào đó dùng header `X-Robots-Tag: noindex`.
- Kiểm soát bù trừ bắt buộc:
  - Mật khẩu tối thiểu 12 ký tự, kiểm tra với danh sách mật khẩu bị lộ.
  - Rate limit đăng nhập (đã có).
  - Session Admin dùng cookie riêng, hết hạn khi không hoạt động.
  - Audit mọi lần đăng nhập.
  - Tuỳ chọn IP allowlist (`VANI_ADMIN_IP_ALLOWLIST`).
  - Cảnh báo khi đăng nhập từ IP mới.

## Alternatives
- Bắt buộc 2FA (TOTP): an toàn hơn nhiều trước lộ mật khẩu/phishing, nhưng Owner không chọn.
- Admin trên subdomain riêng: tách cookie tốt hơn, nhưng trái với chủ trương một domain chung.

## Consequences
- (+) Đăng nhập đơn giản cho nhân viên và cửa hàng.
- (−) **Đường dẫn bí mật chỉ giảm bị dò quét tự động, không phải là lớp xác thực.** Nếu mật khẩu nhân viên bị lộ, kẻ tấn công vào được Admin. Các kiểm soát bù trừ ở trên là bắt buộc, và IP allowlist được khuyến nghị cho vai trò có quyền rộng (Owner Admin, Kế toán).

## Trade-offs
Tiện lợi hơn, đổi lại rủi ro cao hơn khi mật khẩu bị lộ. Có thể xem lại quyết định này bằng một ADR mới (ví dụ bật 2FA riêng cho vai trò nhạy cảm).
