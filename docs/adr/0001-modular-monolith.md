# 0001 — Modular monolith trên Laravel, module tại `modules/`

- Trạng thái: Accepted
- Ngày: 2026-09-28
- Người quyết định: Owner

## Bối cảnh
Cần xây nền tảng đa brand với đội nhỏ–vừa, yêu cầu nhất quán mạnh giữa checkout, tồn kho, khuyến mãi; đồng thời phải mở rộng được về sau (thêm kênh, tách dịch vụ).

## Quyết định
- Xây **một ứng dụng Laravel 13** chia thành module theo bounded context.
- Module đặt tại **thư mục gốc `modules/<Module>/`** (không đặt trong `app/`), namespace `Modules\<Module>\`, autoload PSR-4 `"Modules\\": "modules/"`. `app/` chỉ giữ phần khung ứng dụng.
- Module giao tiếp qua `Contracts/` (interface + DTO) và Domain Event; không truy cập Model/bảng của module khác.

## Hệ quả
- (+) Một lần deploy, transaction DB cục bộ, dễ debug, tốc độ phát triển cao.
- (+) Ranh giới module nhìn thấy rõ ở cấp thư mục; có thể tách service (Search, Integration) sau.
- (−) Cần tự viết `ModuleServiceProvider` để nạp route, migration, view, lang, trang Inertia của từng module; lệnh `php artisan make:*` sinh file vào `app/` → phải di chuyển hoặc viết lệnh `vani:make:*` riêng.
- (−) Cần kỷ luật ranh giới: bổ sung Pest arch tests, ví dụ `arch()->expect('Modules\Ordering')->not->toUse('Modules\Inventory\Models')`.

## Phương án đã cân nhắc
- **Module trong `app/Modules`**: không cần sửa autoload nhưng lẫn với khung ứng dụng — Owner chọn tách ra thư mục gốc.
- **Microservices ngay từ đầu**: chi phí vận hành, transaction phân tán — quá sớm.
- **Nền tảng có sẵn (fork BeikeShop/khác)**: vướng license (xem [01](../01-clean-room-va-license.md)) và không khớp mô hình đa brand.
