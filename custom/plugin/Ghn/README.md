# vani.ghn

Plugin tích hợp hãng vận chuyển Giao Hàng Nhanh (GHN) cho VaniShop, minh hoạ extension point `vani.shipping.carriers` và `vani.checkout.shipping_providers`.

## Tính năng

- **Báo giá vận chuyển tại Checkout**: Đăng ký `ShippingRateProvider`, cung cấp gói cước vận chuyển chuẩn GHN.
- **Tự động đặt đơn (Auto-booking)**: Thực thi `createShipment`, tạo mã vận đơn GHN tự động và idempotent sau khi đơn hàng được chốt/xác nhận.
- **Webhook đồng bộ hành trình**: Tiếp nhận callback từ GHN tại `/api/shipping/ghn/webhook`, xác minh token bí mật và chuyển đổi trạng thái của GHN sang `ShipmentStatus` chuẩn của VaniShop (`picked_up`, `in_transit`, `out_for_delivery`, `delivered`...).
- Tuân thủ bộ kiểm thử chuẩn `ShippingCarrierContract`.

## Cài đặt & Kích hoạt

```bash
php artisan vani:plugin:install vani.ghn
php artisan vani:plugin:enable vani.ghn
```
