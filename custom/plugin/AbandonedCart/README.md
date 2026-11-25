# vani.abandoned-cart

Nhắc giỏ bỏ quên bằng email — plugin tham chiếu cho event `CartAbandoned` và tin marketing qua `Notifier` ([cart-checkout](../../../docs/03-domains/cart-checkout.md), [notification](../../../docs/03-domains/notification.md)).

- Core phát `Cart\Events\CartAbandoned` cho giỏ của khách có tài khoản, còn hàng, không hoạt động quá ngưỡng (Admin → Cài đặt → Bán hàng, mặc định 60 phút). Mỗi đợt không hoạt động chỉ phát một lần; khách sửa giỏ rồi bỏ tiếp thì phát lại.
- Plugin gửi loại tin `cart_abandoned` nhóm **marketing** qua email: chỉ khách đã đồng ý nhận ưu đãi qua email (đăng ký, checkout, Tài khoản → Hồ sơ) mới nhận; thiếu đồng ý thì nhật ký ghi "bỏ qua". Core tự gắn link huỷ nhận tin.
- Biến mẫu: `customer_name`, `item_count`, `subtotal`, `cart_url` (trang giỏ theo ngôn ngữ của khách; khách đăng nhập là thấy lại giỏ), `unsubscribe_url`. Mẫu mặc định tiếng Việt/Anh; Admin sửa ở Admin → Thông báo.
- Chống gửi trùng theo mã giỏ + thời điểm hoạt động cuối. Khách không có email thì bỏ qua.
- Giới hạn: email chưa liệt kê tên từng sản phẩm (event chỉ có tổng số món và tạm tính); khách vãng lai không có giỏ gắn khách nên không nhận nhắc.
