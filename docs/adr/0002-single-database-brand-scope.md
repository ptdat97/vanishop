# 0002 — Một database, cô lập theo phạm vi brand

- Trạng thái: Accepted
- Ngày: 2026-09-28

## Bối cảnh
Owner sở hữu mọi brand, cần khách hàng hợp nhất, loyalty chung, tồn kho dùng chung location và báo cáo hợp nhất. Brand không phải khách hàng độc lập như mô hình SaaS.

## Quyết định
Dùng **một database**; bảng thuộc brand có `brand_id` (và `channel_id` khi cần). Cô lập bằng Policy (lớp 1) + global scope `BelongsToBrand` dựa trên `CurrentContext` (lớp 2). Thiếu context trong job/console → exception.

## Hệ quả
- (+) Truy vấn xuyên brand đơn giản (khách, tồn, báo cáo).
- (+) Thêm brand không cần hạ tầng mới.
- (−) Rủi ro rò rỉ dữ liệu giữa brand nếu quên scope → bắt buộc test cô lập cho mọi model có phạm vi.

## Phương án đã cân nhắc
- **Database/schema riêng mỗi brand** (multi-tenant package): khó hợp nhất khách & tồn, migration nhân bản.
