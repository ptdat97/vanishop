# vani.shipping-flat-rate

Plugin hệ thống ([ADR-029](../../../docs/19-adr/ADR-029-commerce-microkernel.md)): `"bundled": true` — `php artisan vani:install` tự cài + bật.

Phí giao cố định + ngưỡng miễn phí (phương thức `standard`). Cấu hình ở Admin → Cấu hình (`vani.shipping-flat-rate`) hoặc `VANI_SHIPPING_FLAT_FEE`, `VANI_SHIPPING_FREE_OVER`.

Tắt được khi đã có plugin khác cùng extension point đang bật; Extension từ chối tắt implementation cuối cùng của extension point bắt buộc.

## Thanh tiến độ miễn phí giao (1.1.0)

Chuyển từ plugin FreeShippingProgress của VaniCommerce (viết lại từ [đặc tả](../../../docs/05-plugin/specs/free-shipping-progress.md)). Dùng **cùng ngưỡng `free_over`** với phí giao nên thanh tiến độ và phí luôn khớp.

- Trang giỏ: theo tạm tính giỏ; trang thanh toán: theo tiền hàng sau giảm giá (giống cách tính phí). Storefront API giỏ có `extensions["vani.shipping-flat-rate"].free_shipping` (`threshold`, `remaining`, `percent`, `achieved`, `message`, `color`).
- Trang sản phẩm (có cache CDN): chỉ hiện "Miễn phí vận chuyển cho đơn từ …" — không dùng giỏ của khách. Tắt mặc định.
- Cấu hình: bật/tắt, vị trí (giỏ, thanh toán, trang sản phẩm), màu (`#rrggbb`), thông báo theo ngôn ngữ (bắt buộc có `{amount}`).
- Nâng từ 1.0.0: `php artisan vani:plugin:upgrade vani.shipping-flat-rate` (không có migration).
