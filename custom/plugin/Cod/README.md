# vani.cod

Plugin hệ thống ([ADR-029](../../../docs/19-adr/ADR-029-commerce-microkernel.md)): `"bundled": true` — `php artisan vani:install` tự cài + bật.

Thanh toán khi nhận hàng (mã cổng `cod`). Ngưỡng đơn tối đa và tự xác nhận đơn cấu hình ở Admin → Cấu hình (`vani.cod`) hoặc `VANI_COD_MAX_AMOUNT`, `VANI_COD_AUTO_CONFIRM`.

Tự xác nhận là **điểm mở rộng, mặc định tắt** (1.2.0, [ADR-046](../../../docs/19-adr/ADR-046-staffed-operations-vietnam.md)): đơn COD dừng ở "Cần xác nhận" để nhân viên gọi/nhắn khách. Bật `trusted` (khách quen theo SĐT) hoặc `all` khi cửa hàng muốn; tự xác nhận đi qua `OrderTransitions` như nhân viên, nguồn `system`, lý do `cod_auto_confirm[_trusted]`.

Tắt được khi đã có plugin khác cùng extension point đang bật; Extension từ chối tắt implementation cuối cùng của extension point bắt buộc.
