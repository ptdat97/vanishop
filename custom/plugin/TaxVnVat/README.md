# vani.tax-vn-vat

Plugin hệ thống ([ADR-029](../../../docs/19-adr/ADR-029-commerce-microkernel.md)): `"bundled": true` — `php artisan vani:install` tự cài + bật.

VAT Việt Nam gồm trong giá (mã `vn_vat_inclusive`, chọn bằng `core.tax.calculator`). Thuế suất ở Admin → Cấu hình (`vani.tax-vn-vat`) hoặc `VANI_VAT_RATE_BP`. Tắt plugin → Core dùng `none` (không thuế).

Tắt được khi đã có plugin khác cùng extension point đang bật; Extension từ chối tắt implementation cuối cùng của extension point bắt buộc.
