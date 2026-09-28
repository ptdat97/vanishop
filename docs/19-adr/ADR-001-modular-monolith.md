# ADR-001 — Modular Monolith trên Laravel

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner

## Context
Đội 4–6 dev, một Owner, thị trường VN. Checkout, tồn kho, khuyến mãi, đơn hàng cần nhất quán mạnh.

## Problem
Cần kiến trúc vừa phát triển nhanh, vừa có ranh giới rõ để mở rộng (plugin, tách service về sau), mà không gánh chi phí vận hành phân tán.

## Decision
- Một ứng dụng **Laravel 13**, chia module theo bounded context tại thư mục gốc **`modules/<Context>/`** (namespace `Modules\`), không đặt trong `app/`.
- Module giao tiếp qua `Contracts/` và `Events/` ([bounded-contexts](../02-architecture/bounded-contexts.md)).
- Chỉ tách service khi có số liệu vận hành chứng minh (rule R19).

## Alternatives
- Microservices từ đầu: transaction phân tán, chi phí vận hành cao, đội nhỏ không kham nổi.
- Module trong `app/Modules`: đơn giản autoload nhưng lẫn với khung ứng dụng. Owner chọn thư mục gốc riêng.
- Fork nền tảng có sẵn: vướng license và không khớp mô hình đa brand.

## Consequences
- (+) Một deploy, transaction cục bộ, debug dễ.
- (−) Phải tự viết `ModuleServiceProvider` và lệnh `vani:make:*`; phải có arch test giữ ranh giới.

## Trade-offs
Chấp nhận giới hạn scale theo chiều ngang của một codebase để đổi lấy tốc độ và tính nhất quán.
