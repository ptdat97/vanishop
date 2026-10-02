# vani.cod

Plugin hệ thống ([ADR-029](../../../docs/19-adr/ADR-029-commerce-microkernel.md)): `"bundled": true` — `php artisan vani:install` tự cài + bật.

Thanh toán khi nhận hàng (mã cổng `cod`). Ngưỡng đơn tối đa và tự xác nhận đơn cấu hình ở Admin → Cấu hình (`vani.cod`) hoặc `VANI_COD_MAX_AMOUNT`, `VANI_COD_AUTO_CONFIRM`.

Tắt được khi đã có plugin khác cùng extension point đang bật; Extension từ chối tắt implementation cuối cùng của extension point bắt buộc.
