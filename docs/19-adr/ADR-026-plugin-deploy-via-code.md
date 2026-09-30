# ADR-026 — Plugin chỉ vào hệ thống qua mã nguồn và CI, không upload qua Admin

- Trạng thái: Accepted · Ngày: 2026-09-30 · Liên quan: [ADR-003](ADR-003-plugin-architecture.md), [plugin-system](../05-plugin/plugin-system.md), [security](../15-security/security.md) · Nghiên cứu: [reference-comparison §3](../02-architecture/reference-comparison.md)

## Context
Hệ tham chiếu cho phép quản trị viên upload file nén plugin trong Admin rồi giải nén vào thư mục plugin. Để an toàn, nó phải tự chặn path traversal, giới hạn kích thước và số file, chặn zip bomb — và chính nó ghi nhận đây là bề mặt tấn công cần giữ nghiêm. VaniShop có một Owner, một đội phát triển, triển khai qua CI.

## Problem
Có cần cài plugin bằng upload trong Admin không?

## Decision
- Plugin chỉ vào hệ thống qua **repo + CI**: mã trong `custom/plugin/<Name>/`, qua review (clean-room, arch test R4/R5, contract test), deploy như Core.
- Admin chỉ **cài/bật/tắt/gỡ** plugin đã có trong mã nguồn, theo scope, có audit. Không có endpoint nhận file mã nguồn.
- Không ghi file PHP vào thư mục thực thi lúc runtime.

## Alternatives
- Upload qua Admin kèm kiểm tra file nén: tiện cho bên thứ ba, nhưng biến Admin thành nơi thực thi mã tuỳ ý nếu tài khoản bị chiếm (Admin không dùng 2FA, ADR-020) và bỏ qua review/test.
- Composer package riêng cho từng plugin: để dành khi cần dùng lại ngoài repo (ADR-003).

## Consequences
- (+) Bỏ hẳn một bề mặt tấn công; mọi plugin đều qua review, arch test, contract test.
- (+) Triển khai nhiều server nhất quán (không có file chỉ nằm trên một máy).
- (−) Không có "chợ plugin" cài bằng một cú nhấp. Chấp nhận vì không có bên thứ ba.

## Trade-offs
Đổi tiện lợi cài nóng lấy an toàn và khả năng kiểm soát.
