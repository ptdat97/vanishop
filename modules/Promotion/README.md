# Promotion

> Vòng 1 · Mức domain: **Rich** · Tài liệu: [promotion.md](../../docs/03-domains/promotion.md)

## Trách nhiệm

Khuyến mãi, mã giảm giá, chiến dịch (khuyến mãi + bảng giá chạy chung lịch), engine tính giảm.

## Bất biến

- Tổng giảm không vượt mức trần cấu hình (VN: 50%) và không làm giá âm.
- Lượt dùng mã đếm trong transaction đặt hàng; huỷ đơn trả lượt.
- Chiến dịch sở hữu lịch; kết quả chiến dịch do plugin báo cáo dựng (M3).

## Bảng sở hữu

- `promotions`
- `promotion_rules`
- `vouchers`
- `promotion_usages`
- `campaigns`
- `campaign_price_lists`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `Campaigns` | interface | plugin (experimental) |
| `CartContentRule` | interface | plugin |
| `PromotionAction` | interface | tag `vani.promotion.actions`, plugin |
| `PromotionEngine` | interface | plugin |
| `PromotionRule` | interface | tag `vani.promotion.rules`, plugin |
| `PromotionUnavailable` | class | plugin |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
