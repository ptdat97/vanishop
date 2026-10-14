# vani.tracking-pixels

Theo dõi quảng cáo: **Meta Pixel**, **Google Analytics 4**, **Google Tag Manager** ở trình duyệt, và sự kiện **mua hàng từ server** (Meta Conversions API, GA4 Measurement Protocol). Đặc tả: [docs/05-plugin/specs/tracking-pixels.md](../../../docs/05-plugin/specs/tracking-pixels.md). Chuyển từ plugin PixelTracking của VaniCommerce (viết lại từ đặc tả).

```bash
php artisan vani:plugin:install vani.tracking-pixels && php artisan vani:plugin:enable vani.tracking-pixels
```

Cấu hình ở **Admin → Cấu hình → Theo dõi quảng cáo**: mã Meta Pixel, GA4, GTM (trống = tắt nền tảng đó), chế độ đồng ý cookie, gửi server-side (token/secret lưu mã hoá), debug.

- **Sự kiện trình duyệt**: `view_item` (trang sản phẩm), `view_item_list` + `search` (danh mục, thương hiệu, tìm kiếm), `add_to_cart` / `remove_from_cart` (sự kiện `vani:cart-updated` của theme), `begin_checkout`, `add_payment_info` (bấm đặt hàng), `purchase` (trang đơn, **một lần mỗi đơn** nhờ đánh dấu trong `localStorage`). Gửi đồng thời tới GA4 (`gtag`), GTM (`dataLayer`, khoá `ecommerce`) và Meta (`ViewContent`, `Search`, `AddToCart`, `InitiateCheckout`, `AddPaymentInfo`, `Purchase`).
- **Đồng ý cookie**: `banner` (mặc định — thanh Đồng ý/Từ chối của plugin, nhớ 180 ngày trong cookie `vani_consent`), `custom` (biểu thức JS của công cụ khác; đúng thì ghi `vani_consent=granted`), `none`. Chưa đồng ý: không nạp script, sự kiện chờ trong trang; Đồng ý → nạp và gửi; Từ chối → bỏ.
- **Server-side**: nghe `OrderPlaced`; chỉ khi bật và cookie `vani_consent=granted` (hoặc chế độ `none`). Đẩy job `SendServerPurchase` (queue, 3 lượt, timeout 5 giây mỗi nền tảng); email, SĐT (E.164 chỉ số), mã khách **băm SHA-256**; cùng mã sự kiện `purchase-<id công khai đơn>` với trình duyệt để Meta khử trùng. Lỗi không ảnh hưởng đơn.
- **Cache CDN**: trang công khai chỉ chứa dữ liệu sản phẩm; dữ liệu đơn chỉ có ở trang đơn (không cache).
- Không nhúng thẻ `noscript` của GTM (sẽ nạp GTM khi khách chưa đồng ý).
