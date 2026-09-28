# Plugin spec: Loyalty (`vani.loyalty`)

> Trạng thái: **Planned** (đợt P3). Đây là yêu cầu đầu vào; đặc tả chi tiết sẽ nằm trong `custom/plugin/Loyalty/README.md` khi bắt đầu làm.

## Extension point sử dụng

| Nhu cầu | Extension point |
|---|---|
| Đổi điểm trừ tiền ở checkout | `TotalsCalculator` (adjustment `type = loyalty`) |
| Tích điểm tạm khi đặt, khả dụng khi hoàn tất, thu hồi khi trả | Events `OrderPlaced`, `OrderCompleted`, `OrderCancelled`, `ReturnResolved` |
| Hiển thị điểm | `customerProfileTabs()`, slot `vani.storefront.pdp.after_price`, route `/api/storefront/v1/me/loyalty` |
| Tích điểm đơn tại cửa hàng | Integration API `pos-orders` + event tương ứng |
| Tra khách theo SĐT | `CustomerDirectory` |

## Yêu cầu nghiệp vụ

| Thành phần | Thiết kế |
|---|---|
| **Hạng** | Member → Silver → Gold → Diamond, xét theo tổng chi tiêu 12 tháng **toàn tập đoàn** |
| **Tích điểm** | Tỷ lệ cấu hình theo brand (ví dụ 1 điểm/10.000 ₫), nhân hệ số theo hạng/campaign |
| **Trạng thái điểm** | `pending` khi đặt hàng → `available` khi hết hạn đổi trả → `expired` |
| **Đổi điểm** | Trừ tiền khi checkout (1 điểm = N ₫) hoặc đổi voucher; giới hạn % giá trị đơn |
| **Sổ điểm** | `plg_loyalty_ledger` append-only (earn/redeem/expire/adjust/revert); số dư tính từ ledger, có bảng snapshot để đọc nhanh |
| **Omnichannel** | Mua tại cửa hàng (qua POS/ERP) cũng tích điểm nếu có SĐT |
| **Chi phí liên brand** | Tích ở brand A, đổi ở brand B → báo cáo phân bổ chi phí |
| **Quyền lợi hạng** | Freeship, bảng giá `member`, quà sinh nhật, early access |

## Invariant của plugin

- Số dư không âm; đổi điểm dùng `UPDATE … WHERE balance >= ?` trong transaction `PlaceOrder` (hook `vani.order.after_create`).
- Mỗi event đơn chỉ tạo ledger đúng một lần (unique `(order_id, entry_type)`).

## Bảng

`plg_loyalty_tiers`, `plg_loyalty_accounts`, `plg_loyalty_ledger`, `plg_loyalty_balances`.
