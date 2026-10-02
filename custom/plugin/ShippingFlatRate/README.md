# vani.shipping-flat-rate

Plugin hệ thống ([ADR-029](../../../docs/19-adr/ADR-029-commerce-microkernel.md)): `"bundled": true` — `php artisan vani:install` tự cài + bật.

Phí giao cố định + ngưỡng miễn phí (phương thức `standard`). Cấu hình ở Admin → Cấu hình (`vani.shipping-flat-rate`) hoặc `VANI_SHIPPING_FLAT_FEE`, `VANI_SHIPPING_FREE_OVER`.

Tắt được khi đã có plugin khác cùng extension point đang bật; Extension từ chối tắt implementation cuối cùng của extension point bắt buộc.
