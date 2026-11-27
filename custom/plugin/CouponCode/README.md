# vani.coupon-code

Mã giảm giá sản phẩm trên một form gọn cho SME ([đặc tả](../../../docs/05-plugin/specs/coupon-code.md)). Mỗi mã đồng bộ thành một khuyến mãi **cần nhập mã** của Core (`ManagedPromotions`): lịch, lượt dùng, lượt mỗi khách, ghi nhận khi đặt đơn, hoàn khi huỷ, kiểm tra lại khi huỷ một phần do Core Promotion làm. Cần `vani.promotion-rules` ≥ 0.5 (rule phạm vi, đơn tối thiểu, nhóm khách).

## Quản trị

Admin → Marketing → **Mã giảm giá** (quyền `coupon_code.view`, `coupon_code.manage`, preset Marketing).

- **Mã dùng chung** (vd. `SALE10`): một mã cho mọi khách; "Sinh mã" tạo thêm mã cùng chương trình (tiền tố + 8 ký tự, giới hạn lượt từng mã) để in phiếu/gửi đối tác. Mã không đổi sau khi tạo.
- **Mã mẫu**: không có mã chung; "Phát mã" tạo cho mỗi khách một mã riêng dùng một lần (`TIENTO` + 8 ký tự, hết hạn sau N ngày, không quá thời gian kết thúc). Chọn khách cụ thể (tìm theo tên/SĐT/email) hoặc cả nhóm khách/tag (chạy nền theo lô 500, khách đã có mã bị bỏ qua).
- Ưu đãi: giảm % (1–90%, có giảm tối đa) hoặc số tiền; đơn tối thiểu (tính trên sản phẩm được giảm).
- Điều kiện: áp cho mọi sản phẩm / sản phẩm / danh mục (gồm danh mục con) / bộ sưu tập / thương hiệu; không áp cho hàng đang giảm giá; nhóm khách; tổng lượt; lượt mỗi khách (khách vãng lai đếm theo SĐT lúc đặt đơn).
- Thời gian, bật/tắt, không dùng chung với khuyến mãi khác, **hiện cho khách** (ví voucher, trang sản phẩm).
- Admin → Khuyến mãi hiện khuyến mãi của mã ở chế độ chỉ xem (kèm danh sách voucher). Xoá mã đã có đơn dùng → khuyến mãi chuyển sang tắt.

## Storefront

- **Ví voucher** ở giỏ (slot `vani.storefront.cart.after_summary`) và checkout (`checkout.order_summary`): mã công khai đang chạy + mã riêng của khách đăng nhập (tối đa 20), mỗi mã tính thử trên giỏ (`Checkout\Contracts\VoucherPreview`, giỏ của phiên qua `Storefront\Contracts\CurrentCart`): số tiền giảm, "Mua thêm … để dùng ưu đãi", "Đang dùng"; nút **Dùng** và **Chọn mã tốt nhất** (mã dùng được giảm nhiều nhất).
- **Trang sản phẩm** (`pdp.before_add_to_cart`): mã công khai có phạm vi chứa sản phẩm, nút sao chép. Lưu/xoá mã làm mới cache trang.
- **Tài khoản → Mã giảm giá** (`/tai-khoan/ma-giam-gia`): mã riêng dùng được / đã dùng / hết hạn.
- Storefront API `x/vani-coupon-code`: `GET products/{product}`, `GET carts/{cart}/wallet` (+ `X-Vani-Cart-Token`), `GET mine` (đăng nhập) — `openapi.json`, `schemas/`.

## Dữ liệu

`plg_coupons` (nội dung form, `promotion_id` của Core, không khoá ngoại). Voucher, lượt dùng, lượt mỗi khách ở bảng của Core Promotion.

## Giới hạn

- Chỉ mã giảm giá **sản phẩm**; mã giảm phí vận chuyển chưa có (roadmap §6b L72). Phát mã tự động theo sự kiện qua Workflow chưa có (L73).
- Một phạm vi mỗi mã (sản phẩm **hoặc** danh mục **hoặc** bộ sưu tập **hoặc** thương hiệu).
- Bộ sưu tập/thương hiệu lưu theo slug lúc lưu mã: đổi slug thì lưu lại mã.
