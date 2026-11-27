# vani.promotion-rules

Plugin mở rộng bộ điều kiện áp dụng khuyến mãi cho VaniShop, minh hoạ extension point `vani.promotion.rules`.

## Các điều kiện hỗ trợ

| Mã (`type`) | Tên | Cấu hình mẫu | Ý nghĩa |
|---|---|---|---|
| `min_order_subtotal` | Giá trị đơn tối thiểu | `{"min_subtotal": 500000}` | Tổng giá trị các dòng ứng viên phải đạt mức tối thiểu |
| `min_quantity` | Số lượng sản phẩm tối thiểu | `{"min_quantity": 3}` | Tổng số lượng sản phẩm ứng viên phải đạt mức tối thiểu |
| `in_collections` | Thuộc bộ sưu tập | `{"slugs": ["he-2026", "sale"]}` | Chỉ giảm cho sản phẩm thuộc ít nhất một bộ sưu tập chỉ định |
| `in_products` | Thuộc sản phẩm | `{"product_ids": [12, 15]}` | Chỉ giảm cho các sản phẩm chỉ định (mọi size/màu) |
| `in_categories` | Thuộc danh mục | `{"category_ids": [3]}` | Chỉ giảm cho sản phẩm thuộc danh mục chỉ định, kể cả danh mục con |
| `exclude_on_sale` | Không áp cho hàng đang giảm giá | `{}` | Bỏ dòng có giá sale/thành viên hoặc giá gạch |
| `first_order_only` | Đơn hàng đầu tiên | `{"scope": "brand"}` | Chỉ áp dụng nếu khách chưa từng có đơn hợp lệ nào (không tính huỷ) |

## Cách cài & kích hoạt

```bash
php artisan vani:plugin:install vani.promotion-rules
php artisan vani:plugin:enable vani.promotion-rules                # phạm vi toàn bộ hệ thống
php artisan vani:plugin:enable vani.promotion-rules --scope=brand:1 # hoặc theo brand cụ thể
```

Khi được bật, các điều kiện trên tự động xuất hiện trong form tạo/sửa khuyến mãi tại trang Admin.

## Kiểm tra lại khi khách bớt hàng (0.2.0, Core 0.3.31)

`min_order_subtotal`, `min_quantity`, `in_collections`, `in_brands` implement `CartContentRule`: khi nhân viên huỷ một phần với nguyên nhân "khách yêu cầu bớt hàng", Core kiểm tra lại các rule này trên phần hàng còn lại và thu hồi ưu đãi không còn đủ điều kiện. `first_order_only` không được kiểm tra lại (đơn đã tồn tại thì luôn "không còn là đơn đầu tiên").

## Gợi ý "mua thêm" (0.5.0, Core 0.5.109)

`min_order_subtotal` và `min_quantity` implement `ExplainsIneligibility`: mã bị từ chối vì chưa đủ ngưỡng mang câu "Mua thêm 120.000 ₫ để dùng ưu đãi." (số tiền/số lượng còn thiếu, tính trên các dòng đã qua rule đứng trước). Đặt rule phạm vi (`in_products`, `in_categories`, `in_brands`, `in_collections`, `exclude_on_sale`) **trước** rule ngưỡng để ngưỡng tính trên hàng được giảm. `in_products`, `in_categories`, `exclude_on_sale` là `CartContentRule`.
