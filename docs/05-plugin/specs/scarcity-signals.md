# Đặc tả: Tín hiệu khan hiếm (`vani.scarcity-signals`)

> Trạng thái: **Implemented** (plugin 1.0.0, Core ≥ 0.3.85, 2026-11-05). Nguồn: plugin ScarcitySignals 1.0.1 của VaniCommerce (Owner — [ADR-036](../../19-adr/ADR-036-service-result-hooks.md): mã do Owner viết, phần gọi lõi cũ viết lại trên contract VaniShop). Đợt: M2.

## 1. Mục tiêu

Hai con số trên trang sản phẩm giúp khách quyết định mua: **còn bao nhiêu cái của đúng size đang chọn**, và **đã bán bao nhiêu trong N ngày qua**; nhãn "Sắp hết" ở thẻ sản phẩm.

**Không có chế độ bịa số** — ràng buộc thiết kế:
- Số còn lại = số bán được online (ATS, `Inventory\Contracts\AvailabilityReader`) — cùng số Checkout dùng để giữ hàng; không hệ số, không sàn, không làm tròn.
- Số đã bán = số lượng của đơn **đã thu tiền hoặc đã rời kho** (`OrderStatistics::confirmedQuantitiesByStyle`). COD vừa đặt / đơn online chưa trả không tính (nếu tính, ai cũng bơm được bằng đơn đặt rồi bỏ); đơn huỷ, hoàn tiền toàn bộ, đơn thay thế khi đổi hàng không tính.
- Không có "N người đang xem" (không đo được từ dữ liệu thật).
- "Chỉ hiện khi đã bán từ" (mặc định 3) là **giấu** số không thuyết phục ("Đã bán 1" là bằng chứng ngược), không phải làm đẹp số.

## 2. Kiểm kê plugin cũ

| Tính năng cũ | Giữ / đổi |
|---|---|
| "Chỉ còn N" theo size đang chọn, ngưỡng chung + ngưỡng riêng theo danh mục (lấy nhỏ nhất) | Giữ; danh mục ghi bằng **đường dẫn hoặc id** (`vay-cuoi = 1`); áp cho danh mục chính và danh mục cha |
| "Đã bán N trong X ngày" từ đơn đã thanh toán, gộp sẵn mỗi giờ (bảng xoá-rồi-chèn + lệnh theo lịch) | Giữ ý; **đổi**: cache 1 giờ cho mọi sản phẩm (một truy vấn gộp), không bảng, không lệnh — đổi cửa sổ thống kê = khoá cache mới nên tính lại ngay |
| Nhãn danh sách qua plugin nhãn khác | **Đổi**: slot `plp.card_badges` của theme; theo **tổng** số bán được của mọi size (cũ: SKU mặc định) |
| Bù dữ liệu danh mục / tồn bằng truy vấn riêng | Không cần: PDP có `breadcrumb` (id, đường dẫn), thẻ có `variant_ids` cho enricher (Core 0.3.85) |

## 3. Hành vi

- **PDP**: enricher `product` → `extensions["vani.scarcity-signals"] = {low_stock: {variant id: còn lại}, threshold, sold: {count, days} | null}`; chỉ variant có 0 < còn lại ≤ ngưỡng mới có số. Slot `pdp.add_to_cart_fields` in bảng số còn lại; `scarcity.js` hiện "Chỉ còn N sản phẩm" khi khách chọn size có trong bảng (không JS hoặc chưa chọn size: không hiện). Slot `pdp.after_price`: "Đã bán N trong X ngày qua".
- **Thẻ sản phẩm**: enricher `product_card` → `low_stock: true` khi tổng số bán được > 0 và ≤ ngưỡng chung; slot `plp.card_badges` hiện "Sắp hết".
- Storefront API có cùng dữ liệu (headless).
- Trang cache CDN: số trên HTML trễ tối đa bằng thời gian cache trang; "đã bán" trễ tối đa 1 giờ (bằng chứng xã hội, không phải tồn kho).

## 4. Cấu hình

| Khoá | Kiểu | Mặc định |
|---|---|---|
| `show_low_stock` | bool | bật |
| `low_stock_threshold` | số (1–100) | 5 |
| `threshold_overrides` | text, mỗi dòng `danh mục = ngưỡng` | — |
| `show_list_label` | bool | bật |
| `show_sold_count` | bool | bật |
| `sold_window_days` | số (1–90) | 7 |
| `sold_min_to_show` | số (1–1000) | 3 |

## 5. Extension point

`StorefrontEnricher` (`product`, `product_card`), slot `vani.storefront.pdp.add_to_cart_fields`, `vani.storefront.pdp.after_price`, `vani.storefront.plp.card_badges`, `AvailabilityReader`, `OrderStatistics::confirmedQuantitiesByStyle` (0.3.85), manifest `assets`.

## 6. Tiêu chí chấp nhận

1. Số còn lại chỉ hiện khi 0 < ATS ≤ ngưỡng của size đang chọn; ngưỡng riêng nhỏ nhất trong danh mục chính/cha được áp.
2. "Đã bán" chỉ đếm đơn đã thu tiền hoặc đã rời kho; dưới mức tối thiểu thì không hiện.
3. Nhãn thẻ theo tổng số bán được; tắt từng tín hiệu được.
4. Không có số nào không đọc từ tồn kho hoặc đơn thật.
