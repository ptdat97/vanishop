# Marketplace (plugin `vani.marketplace`)

> Trạng thái: **Designed** (chỉ ranh giới và extension point). Chưa có kế hoạch triển khai; đợt **Later** ([plugin-catalog](../05-plugin/plugin-catalog.md)). Quyết định: [ADR-015](../19-adr/ADR-015-marketplace-architecture.md).

Marketplace **không được làm biến dạng Commerce Core**. Seller là một khái niệm của plugin. Core không có cột `seller_id` và không có logic commission.

## 1. Use case

- Bán hàng ký gửi/concession của đối tác trên website của cửa hàng.
- Cho brand bên ngoài (không thuộc Owner) bán cùng.

> ⚠️ Khi bán hàng của **người bán khác** (pháp nhân khác) trên website, website trở thành **sàn giao dịch TMĐT** (phải đăng ký với Bộ Công Thương, có quy chế hoạt động) — khác mô hình một pháp nhân bán hàng của [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md). Bật plugin này cần pháp chế duyệt trước.

## 2. Ranh giới

| Core vẫn quản lý | Plugin Marketplace quản lý |
|---|---|
| Product (Style/Variant), Cart, Checkout, Order, Payment, Inventory, Fulfillment | Seller, Seller onboarding/KYC, Seller Product/SKU (gắn variant Core với seller), Commission rule, Seller Order (hình chiếu của order line theo seller), Settlement, Payout, Seller portal |

## 3. Mô hình dữ liệu (plugin)

```
plg_mkt_sellers(id, code, legal_name, tax_code, status, payout_account_encrypted, commission_plan_id)
plg_mkt_seller_products(seller_id, style_id UNIQUE)           -- style thuộc đúng 1 seller
plg_mkt_seller_locations(seller_id, location_id UNIQUE)        -- location type=virtual của seller
plg_mkt_commission_plans(id, rules json)
plg_mkt_seller_orders(id, seller_id, order_id, status, gross_amount, commission_amount, net_amount)
plg_mkt_seller_order_lines(seller_order_id, order_line_id UNIQUE, commission_amount)
plg_mkt_settlements(id, seller_id, period_start, period_end, status, net_amount)
plg_mkt_settlement_lines(settlement_id, seller_order_id, amount, type[sale|refund|adjustment])
plg_mkt_payouts(id, settlement_id, amount, status, bank_reference)
```

## 4. Extension point sử dụng

| Nhu cầu | Extension point Core |
|---|---|
| Sản phẩm của seller | Style có `brand_id` (brand catalog của seller, nếu có) + `plg_mkt_seller_products` (style ↔ seller); hook `vani.product.before_save` kiểm tra quyền seller |
| Tồn của seller | Location `type = virtual` + `stock_authority` = seller (qua Integration Client của seller hoặc portal) |
| Chọn kho theo seller | `SourcingStrategy` `marketplace` (dòng của seller → location của seller) |
| Tạo Seller Order | Hook `vani.order.after_create` (chỉ ghi DB) → tạo `plg_mkt_seller_orders` |
| Fulfillment theo seller | Shipment theo location của seller (Core đã hỗ trợ nhiều shipment/đơn) |
| Commission | Tính khi `OrderCompleted`; đảo khi `ReturnResolved` |
| Settlement, payout | Scheduled task + Admin/Seller portal pages |
| Khuyến mãi của seller | `PromotionRule` `seller_discount` + ngân sách tài trợ lưu ở plugin |
| Seller portal | Registry `adminPages()` với role `seller` giới hạn theo scope seller |

## 5. Invariant của plugin

- Σ commission + net của seller = gross của các dòng thuộc seller (sau giảm giá phân bổ).
- Settlement chỉ gồm seller order đã `completed` và hết hạn đổi trả; hoàn tiền sau settlement tạo dòng `refund` âm ở kỳ sau.
- Payout idempotent theo `settlement_id`.

## 6. Việc Core cần bổ sung khi bắt đầu làm

Không cần bổ sung. Nếu phát sinh nhu cầu (ví dụ phân quyền theo scope `seller`), thì bổ sung **scope type tổng quát** vào Identity, không thêm khái niệm seller vào Core.
