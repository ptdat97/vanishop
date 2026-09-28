# 07 — Khách hàng (core), Khuyến mãi & Loyalty (plugin)

> **Core**: §1 tài khoản hợp nhất, địa chỉ, nhóm khách, consent; khung đăng nhập OTP; §4 khung thông báo + email. **Plugin**: §2 `Promotion`, §3 `Loyalty`, đăng nhập mạng xã hội (`SocialLogin`), SMS/ZNS (`SmsBrandname`, `ZaloZns`), `Wishlist`, `SizeAdvisor` — xem [17](17-danh-muc-plugin.md). Nội dung §2–§3 là **yêu cầu đầu vào cho plugin**.

## 1. Khách hàng hợp nhất (Single Customer View)

- **Một tài khoản dùng cho mọi brand** của Owner ("Vani ID"). Đăng nhập ở brand A thì dùng được ở brand B (SSO trong cùng Owner — cookie/phiên theo domain, đăng nhập lại 1 chạm qua token redirect).
- **Định danh chính: số điện thoại** (chuẩn hoá E.164 `+84…`); email là phụ. Đăng nhập: mật khẩu và OTP (core, gửi qua `OtpSender` — email mặc định; SMS/ZNS do plugin); Google, Apple, Zalo qua plugin `SocialLogin`.
- Khách vãng lai đặt hàng → tạo **customer profile ẩn** theo số điện thoại; khi khách đăng ký bằng số đó → hợp nhất lịch sử đơn (sau xác thực OTP).
- **Hợp nhất trùng lặp** (merge) do CSKH thực hiện, có audit.
- Dữ liệu khách từ POS/cửa hàng, sàn (nếu có SĐT) đổ về qua module Integration → cùng hồ sơ.

### Hồ sơ khách

| Nhóm | Trường |
|---|---|
| Định danh | phone, email, họ tên, ngày sinh, giới tính |
| Địa chỉ | sổ địa chỉ (theo chuẩn địa giới mới), địa chỉ mặc định |
| Quan hệ brand | `customer_brand_profiles`: ngày đầu mua, tổng chi tiêu, số đơn, **consent marketing theo brand và theo kênh** (email/SMS/ZNS) |
| Phân khúc | nhóm khách (VIP, nhân viên, KOL, sỉ), tag; segment động RFM (*plugin `AdvancedReports`*) |
| Mở rộng | Tab hồ sơ do plugin thêm qua `customerProfileTabs()`: điểm thưởng, size profile, wishlist… |

## 2. Khuyến mãi *(plugin `Promotion`)*

> Core chỉ cần: `TotalsCalculator`, `CheckoutValidator`, hook `vani.checkout.order.placing`, event `OrderCancelled` (hoàn lượt voucher), bảng giá `sale` của Pricing. Bảng của plugin dùng tiền tố `plg_promotion_`.

### 2.1 Mô hình: Điều kiện → Hành động

```
promotions(id, scope[owner|brand|channel], name, type, starts_at, ends_at, priority,
           stacking[exclusive|combinable], budget_amount, usage_limit, usage_per_customer, status)
promotion_conditions(promotion_id, type, operator, value_json)
promotion_actions(promotion_id, type, value_json)
vouchers(promotion_id, code, usage_limit, used_count, customer_id NULL, expires_at)
```

| Điều kiện | Hành động |
|---|---|
| Giá trị giỏ ≥ X | Giảm % / số tiền trên đơn (có trần tối đa) |
| Số lượng ≥ N | Giảm % / số tiền trên dòng hàng đủ điều kiện |
| Chứa sản phẩm/danh mục/bộ sưu tập | Mua X tặng Y (BxGy), mua 2 giảm thêm 10%… |
| Nhóm khách / hạng thành viên | Freeship / giảm phí vận chuyển |
| Đơn đầu tiên (theo brand hoặc Owner) | Tặng quà (gift variant, trừ tồn) |
| Kênh, phương thức thanh toán, tỉnh/thành | Giá đồng giá (combo) |
| Ngày sinh, khung giờ (flash sale) | |

- **Chống chồng KM**: `exclusive` (không cộng), `combinable` (cộng dồn theo `priority`). Luôn có quy tắc "giá sau KM ≥ giá sàn" (brand cấu hình % tối đa).
- **Voucher**: mã chung (`SALE50K`), mã riêng 1 lần (sinh hàng loạt cho CRM, đối tác), gắn khách cụ thể.
- **Cross-brand** (scope `owner`): ví dụ "mua ở Lumière nhận voucher 100k dùng ở Urbanx". Chi phí KM phân bổ về brand/pháp nhân theo quy tắc kế toán.
- Mọi đơn lưu **snapshot** KM đã áp dụng (xem Totals Pipeline ở [06](06-don-hang-thanh-toan-giao-hang.md)).
- Tuân thủ quy định khuyến mãi VN: mức giảm tối đa, thời gian KM, đăng ký/thông báo với Sở Công Thương khi cần — xem [09](09-dac-thu-viet-nam.md).

### 2.2 Hiệu năng
- Promotion đang hoạt động của channel được cache (Redis), invalid khi thay đổi.
- Usage voucher trừ trong transaction `PlaceOrder` với khoá dòng; flash sale dùng counter Redis.

## 3. Loyalty toàn tập đoàn *(plugin `Loyalty`)*

> Core chỉ cần: `TotalsCalculator` (đổi điểm), events đơn/đổi trả, `CustomerDirectory`, Integration (đơn POS), `customerProfileTabs()`. Bảng của plugin dùng tiền tố `plg_loyalty_`.

| Thành phần | Thiết kế |
|---|---|
| **Hạng** | Member → Silver → Gold → Diamond, xét theo tổng chi tiêu 12 tháng **toàn tập đoàn** |
| **Tích điểm** | Theo tỷ lệ cấu hình từng brand (ví dụ 1 điểm/10.000đ), nhân hệ số theo hạng/campaign |
| **Trạng thái điểm** | `pending` khi đặt hàng → `available` khi hết hạn đổi trả → `expired` |
| **Đổi điểm** | Trừ tiền khi checkout (1 điểm = N đồng) hoặc đổi voucher; giới hạn % giá trị đơn |
| **Sổ điểm** | `loyalty_ledger` append-only (earn/redeem/expire/adjust/revert), số dư tính từ ledger (có bảng snapshot để đọc nhanh) |
| **Omnichannel** | Mua tại cửa hàng (qua POS/ERP) cũng tích điểm nếu có SĐT |
| **Chi phí** | Điểm đổi ở brand B nhưng tích ở brand A → báo cáo phân bổ chi phí liên brand |

Quyền lợi hạng: freeship, giảm giá thành viên (bảng giá `member`), quà sinh nhật, early access bộ sưu tập mới.

## 4. Giao tiếp khách hàng

- Kênh: **Email (core)**; SMS brandname, **Zalo ZNS** (template được Zalo duyệt), Web push là plugin qua `NotificationChannel`.
- Template **theo brand** (logo, màu, giọng văn), đa ngôn ngữ.
- Thông báo giao dịch (không cần consent marketing): xác nhận đơn, thanh toán, giao hàng, đổi trả, OTP.
- Thông báo marketing: chỉ gửi khi có consent theo brand + kênh; mỗi tin có link huỷ đăng ký.
- Tích hợp CDP/Marketing automation (Phase 3) qua event stream.
