# vani.promotion-rules

Plugin mở rộng bộ điều kiện áp dụng khuyến mãi cho VaniShop, minh hoạ extension point `vani.promotion.rules`.

## Các điều kiện hỗ trợ

| Mã (`type`) | Tên | Cấu hình mẫu | Ý nghĩa |
|---|---|---|---|
| `min_order_subtotal` | Giá trị đơn tối thiểu | `{"min_subtotal": 500000}` | Tổng giá trị các dòng ứng viên phải đạt mức tối thiểu |
| `min_quantity` | Số lượng sản phẩm tối thiểu | `{"min_quantity": 3}` | Tổng số lượng sản phẩm ứng viên phải đạt mức tối thiểu |
| `in_collections` | Thuộc bộ sưu tập | `{"slugs": ["he-2026", "sale"]}` | Chỉ giảm cho sản phẩm thuộc ít nhất một bộ sưu tập chỉ định |
| `first_order_only` | Đơn hàng đầu tiên | `{"scope": "brand"}` | Chỉ áp dụng nếu khách chưa từng có đơn hợp lệ nào (không tính huỷ) |

## Cách cài & kích hoạt

```bash
php artisan vani:plugin:install vani.promotion-rules
php artisan vani:plugin:enable vani.promotion-rules                # phạm vi toàn bộ hệ thống
php artisan vani:plugin:enable vani.promotion-rules --scope=brand:1 # hoặc theo brand cụ thể
```

Khi được bật, các điều kiện trên tự động xuất hiện trong form tạo/sửa khuyến mãi tại trang Admin.
