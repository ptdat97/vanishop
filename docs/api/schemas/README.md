# JSON Schema: event tích hợp

Đây là schema của các event VaniShop gửi cho hệ thống ngoài: qua webhook, `GET /api/integration/v1/events` và connector outbox.

| File | Nội dung |
|---|---|
| `envelope.v1.json` | Phong bì chung: `event_id` (UUID, khoá khử trùng lặp), `event_type`, `schema_version`, `occurred_at`, `aggregate`, `correlation_id`, `data` |
| `vanishop.order.v1.json` | Ảnh chụp đơn hàng (canonical) |
| `events/<event_type>.json` | `data` của từng loại event |

## Quy tắc tương thích

- **Thay đổi không phá vỡ** (giữ v1): Core chỉ được thêm trường tuỳ chọn. Plugin cũng chỉ được thêm trường vào đơn hàng, qua hook `vani.integration.order_payload`; các trường canonical luôn giữ giá trị của Core.
- **Thay đổi phá vỡ** (xoá trường, đổi kiểu, đổi nghĩa): phải ra schema v2 và tăng `schema_version`. Bản v1 vẫn phát song song trong thời gian chuyển đổi.
- **Bên nhận** khử trùng lặp theo `event_id` và xử lý tuần tự theo `aggregate.id` (số đơn).
- **Tiền:** số nguyên theo đơn vị nhỏ nhất (VND tính bằng đồng). **Thời gian:** RFC 3339, theo giờ Việt Nam.

## Kiểm chứng

Test `modules/Integration/Tests/Feature/PayloadSchemaTest.php` cho chạy các luồng thật (đặt hàng, xác nhận, giao, thu tiền, đổi trả, hoàn tiền, huỷ, đối soát) rồi kiểm từng event theo các schema này. Test cũng kiểm rằng mỗi loại event đều có file schema tương ứng.
