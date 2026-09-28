# 0004 — Transactional outbox cho tích hợp

- Trạng thái: Accepted
- Ngày: 2026-09-28

## Bối cảnh
Message tới hệ thống ngoài (ERP, đối tác webhook, ODO khi có) phải đến chắc chắn, không trùng, đúng thứ tự; hệ thống ngoài có thể chậm hoặc lỗi nhưng không được làm hỏng checkout.

## Quyết định
Ghi message vào bảng `integration_outbox` **trong cùng transaction** với thay đổi nghiệp vụ. Worker gửi bất đồng bộ, retry backoff, giữ thứ tự theo aggregate, chuyển `dead` sau N lần. Chiều vào dùng `integration_inbox` với khoá idempotency.

## Hệ quả
- (+) Không mất message, checkout độc lập với hệ thống ngoài.
- (+) Có màn hình theo dõi/replay.
- (−) Nhất quán cuối (eventual consistency): trạng thái ở hệ thống ngoài trễ vài giây–phút.

## Phương án đã cân nhắc
- Gọi API ngay trong request: mất đơn khi lỗi, checkout chậm.
- Chỉ dùng queue job: mất message nếu dispatch sau commit bị lỗi/crash.
- Message broker (Kafka/RabbitMQ): cân nhắc khi lưu lượng lớn; outbox vẫn là nguồn phát.
