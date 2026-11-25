# vani.bundles

Combo sản phẩm: bán theo bộ (vd. áo + chân váy + túi) và giảm theo % khi khách mua đủ bộ. Plugin tham chiếu cho `ContextualPromotionAction`, `ManagedPromotions` và `Carts::addLines` ([promotion §5.0](../../../docs/03-domains/promotion.md), [extension-point-catalog](../../../docs/04-extension/extension-point-catalog.md)).

## Quản trị

- Admin → Catalog → **Combo**: tên, đường dẫn, mô tả, ảnh, 2–6 sản phẩm kèm số lượng mỗi món (1–10), mức giảm 1–90%, thứ tự, lịch chạy, bật/tắt. Quyền `bundles.view`, `bundles.manage` (nhóm Marketing).
- Mỗi combo đồng bộ thành một khuyến mãi cộng dồn ở Admin → Khuyến mãi (`ManagedPromotions`, khoá = id combo). Khuyến mãi ở đó chỉ xem, sửa tại màn hình Combo. Xoá combo đã có đơn dùng thì khuyến mãi chuyển sang tắt để giữ lịch sử.

## Cách tính giảm

- Số bộ = số bộ đủ món nhỏ nhất qua các món, gộp mọi size/màu của cùng sản phẩm.
- Chỉ phần hàng thuộc các bộ đủ món được giảm; món mua dư giữ giá gốc.
- Một món có nhiều dòng (khác size) → giảm vào dòng có đơn giá thấp nhất trước.
- Rule `bundle_complete` (`CartContentRule`) và action `bundle_percent` (`ContextualPromotionAction`) mang sẵn danh sách món trong cấu hình khuyến mãi, nên đánh giá giỏ không đọc bảng của plugin. Khách bớt hàng sau khi đặt → tính lại theo cấu hình combo lúc đặt đơn.

## Storefront

- Trang sản phẩm (slot `vani.storefront.pdp.after_details`): khung "Mua trọn bộ" cho các combo đang chạy có sản phẩm này — chọn phân loại từng món, giá trọn bộ, số tiền tiết kiệm, nút "Thêm cả bộ vào giỏ" (`window.vani.cart.addItems`, một món hết hàng thì không món nào được thêm).
- Block **Combo sản phẩm** cho Builder Engine (trang chủ, bộ sưu tập, slot nhận block).
- Trang `/combo` (`/en/bundles`) liệt kê combo đang chạy; slot `vani.bundles.page.before_list` / `after_list` cho plugin/theme chèn nội dung bằng code.
- Storefront API `GET /api/storefront/v1/x/vani-bundles/bundles` (`openapi.json`, `schemas/bundles.json`).

## Dữ liệu

`plg_bundles`, `plg_bundle_items` (id sản phẩm và ảnh của Core, không khoá ngoại sang bảng Core). Combo có món đang ẩn hoặc ngừng bán không hiện ở storefront. Seed demo: "Set công sở" và "Set dạo phố 3 món".
