# vani.bank-transfer

Plugin hệ thống ([ADR-029](../../../docs/19-adr/ADR-029-commerce-microkernel.md)): `"bundled": true` — `php artisan vani:install` tự cài + bật.

Chuyển khoản ngân hàng, nhân viên xác nhận đã nhận tiền (mã cổng `manual_bank_transfer`). Tài khoản nhận tiền cấu hình ở Admin → Cấu hình (`vani.bank-transfer`) hoặc `VANI_BANK_TRANSFER_*`; thiếu số tài khoản thì phương thức ẩn.

Tắt được khi đã có plugin khác cùng extension point đang bật; Extension từ chối tắt implementation cuối cùng của extension point bắt buộc.
