# Đặc tả: Mã giảm giá sản phẩm (`vani.coupon-code`)

> Trạng thái: **Implemented** (plugin 0.1.0, Core ≥ 0.5.113, 2026-11-27). Nguồn: plugin CouponCode 1.1.0 của VaniCommerce (Owner — [ADR-036](../../19-adr/ADR-036-service-result-hooks.md): chỉ kiểm kê tính năng, phần gọi lõi cũ viết lại trên contract VaniShop). Cần Core đợt 1 (A1–A7, §5) và `vani.promotion-rules` 0.5.0.

## 1. Mục tiêu

Cửa hàng SME tạo **mã giảm giá cho sản phẩm** trên một form gọn (không phải ghép rule/action như Admin → Khuyến mãi), khách **thấy mã dùng được ngay ở giỏ/checkout/trang sản phẩm** và chọn mã tốt nhất bằng một nút. Toàn bộ tính tiền, lượt dùng, ngân sách, ghi nhận khi đặt đơn, hoàn khi huỷ, kiểm tra lại khi huỷ một phần do Core Promotion làm — plugin chỉ giữ nội dung hiển thị và đồng bộ.

Ngoài phạm vi: mã giảm **phí vận chuyển** (cần action nhắm phí giao trong Core — roadmap §6b), phát mã qua Workflow (chưa có `vani.workflow`).

## 2. Kiểm kê plugin cũ

| Tính năng cũ | Giữ / đổi |
|---|---|
| Mã: giảm trực tiếp / theo %, giảm tối đa, đơn tối thiểu, tổng lượt, lượt mỗi khách, bắt đầu/kết thúc, bật/tắt | Giữ; tính bằng Core (`percent_off` + `max_amount`, `amount_off`, rule `min_order_subtotal`, `usage_limit`, `usage_limit_per_customer`) |
| Phạm vi: sản phẩm, danh mục, thương hiệu, nhóm khách (kể cả "khách chưa đăng nhập"); loại trừ hàng đang giảm giá | Giữ; rule `in_products`, `in_categories`, `in_brands`, `in_collections`, `in_customer_groups`, `exclude_on_sale` của `vani.promotion-rules`. "Khách chưa đăng nhập" = không chọn nhóm |
| Mã giảm phí vận chuyển (phí tối thiểu, phương thức, khu vực, dùng kèm mã sản phẩm) | **Bỏ** khỏi plugin này (ngoài phạm vi §1) |
| Mã mẫu + phát mã riêng cho khách (một lượt, hạn riêng) | Giữ; mã riêng là voucher gán khách của Core (`Vouchers::issue`) |
| Hành động Workflow "phát mã" | Hoãn (chưa có Workflow) |
| Ô nhập mã, "Chọn mã tốt nhất", "Có N mã có thể áp dụng", danh sách voucher có trạng thái, modal điều kiện, sao chép mã | Giữ; trên ví voucher của Core API giỏ (`voucher_codes`, ADR-044) |
| Trang tài khoản "Voucher của bạn" | Giữ |
| Cột `coupon_code`/`coupon_discount` trên bảng đơn | **Đổi**: không thêm cột Core (ADR-027); đơn đã có `order_adjustments` + `promotion_usages` |

## 3. Dữ liệu

- `plg_coupons`: id, `promotion_id` (id khuyến mãi Core do `ManagedPromotions` trả, không FK), `kind` (`shared` mã dùng chung | `template` mã mẫu), mã chính / tiền tố mã riêng, tên nội bộ, mô tả cho khách theo ngôn ngữ, loại và mức giảm, giảm tối đa, đơn tối thiểu, một phạm vi (`scope_type` + id; slug bộ sưu tập/thương hiệu lúc lưu), bỏ hàng đang giảm giá, nhóm khách, lượt tổng/mỗi khách, số ngày hiệu lực mã riêng, lịch, bật/tắt, độc quyền, `public`, `lock_version`.
- Không có bảng lượt dùng/mã riêng: voucher, lượt dùng, lượt mỗi khách nằm ở Core (`vouchers`, `promotion_usages`).

## 4. Hành vi

### 4.1 Admin → Marketing → Mã giảm giá

- Danh sách: mã (hoặc "N mã" khi sinh hàng loạt / mã mẫu), ưu đãi ("Giảm 10%, tối đa 50.000đ"), điều kiện tóm tắt, đã dùng / tổng lượt, tổng giảm (`ManagedPromotions::usage`), trạng thái lịch (Sắp chạy / Đang chạy / Đã kết thúc / Tạm ngưng).
- Form: mã đơn hoặc sinh hàng loạt (tiền tố + số lượng ≤ 5000); giảm % (1–90) hoặc số tiền; giảm tối đa; đơn tối thiểu; phạm vi sản phẩm (sản phẩm / danh mục / bộ sưu tập / thương hiệu, tìm bằng `CatalogLookup`); "không áp cho hàng đang giảm giá"; nhóm khách; tổng lượt; lượt mỗi khách (mặc định 1); bắt đầu/kết thúc; cộng dồn với khuyến mãi khác hay độc quyền; hiện công khai; mô tả cho khách.
- Lưu → `ManagedPromotions::sync('vani.coupon-code', id, …)` với `requiresVoucher = true`; mã → `Vouchers::issue`. Lỗi validate của Core (`action_config`, `rules.N.*`) hiện tại ô tương ứng.
- **Mã mẫu → phát mã riêng**: chọn khách / nhóm khách / tag → mỗi khách một mã `TIENTO-XXXXXX`, 1 lượt, hạn = hạn mẫu hoặc N ngày. Phát trên queue khi > 200 khách; audit.
- Xoá: mã chưa dùng thì xoá hẳn; đã dùng thì Core chuyển sang tắt (`ManagedPromotions::remove`).
- Quyền `coupon_code.view`, `coupon_code.manage` (preset Marketing).

### 4.2 Storefront

- **Ví voucher** ở giỏ (`cart.after_summary`) và checkout (`checkout.order_summary`): mã công khai đang chạy + mã riêng của khách đăng nhập. Mỗi mã: ưu đãi, HSD, còn bao nhiêu lượt, trạng thái từ `VoucherPreview::forCart` — "Dùng được (−45.000đ)", "Mua thêm 120.000đ để dùng" (`ExplainsIneligibility`), "Không áp cho sản phẩm trong giỏ", "Bạn đã dùng hết lượt". Nút **Dùng** / **Bỏ** gọi API giỏ của Core (`PUT carts/{cart}/vouchers`). Nút **Chọn mã tốt nhất** = mã dùng được có mức giảm lớn nhất (bằng nhau → hết hạn sớm hơn).
- **Trang sản phẩm** (`pdp.before_add_to_cart`): "Mã giảm giá cho sản phẩm này" — mã công khai có phạm vi chứa sản phẩm; sao chép / lưu vào giỏ.
- **Tài khoản → Voucher của tôi** (`accountRoutes` + `accountPage`): mã riêng còn hạn, đã dùng, hết hạn.
- Modal "Điều kiện": thời gian, ưu đãi, đơn tối thiểu, giảm tối đa, phạm vi, lượt tổng/mỗi khách, nhóm khách.
- Storefront API `x/vani-coupon-code/*` (danh sách cho giỏ, cho sản phẩm, của khách) + `openapi.json` (ADR-044). Trang/khối có số liệu theo giỏ hoặc khách không vào cache trang.

## 5. Core cần có trước (đợt 1)

| # | Điểm mở rộng |
|---|---|
| A1 | `promotions.usage_limit_per_customer`; đếm theo (khuyến mãi, khách) bằng UPDATE có điều kiện trong `recordUsage`, hoàn trong `revertUsage`; lý do từ chối `customer_limit` |
| A2 | `vouchers.customer_id`; lý do `not_owner`; contract `Vouchers` (`issue`, `forCustomer`, `revoke`) |
| A3 | `ManagedPromotion::$requiresVoucher`, `$usageLimitPerCustomer`; `ManagedPromotions::usage(owner)` |
| A4 | `percent_off.max_amount` (phân bổ theo tỷ lệ dòng) |
| A5 | `PromotionLine::$compareAt`, `$priceListCode`; trần giảm tính theo giá niêm yết |
| A6 | `VoucherPreview::forCart(CartKey, codes)` |
| A7 | `ExplainsIneligibility` cho rule (số tiền/số lượng còn thiếu) |
| 2a (0.5.110) | `CategoryDirectory::idsForProducts`, `CustomerDirectory` (tìm khách, id theo nhóm/tag) |
| 2b (0.5.111–0.5.113) | `CurrentCart` (`currentKey`, `customerId`), `VoucherPreviewResult::$attached` |

## 6. Tiêu chí chấp nhận

1. Mã % có giảm tối đa không giảm quá mức tối đa trên cả đơn; tổng giảm sau mọi khuyến mãi không vượt trần so với giá niêm yết.
2. 100 request đồng thời của cùng một khách với mã "1 lượt/khách" → đúng 1 đơn dùng mã; khách vãng lai bị tính theo khách giải từ SĐT lúc đặt đơn.
3. Mã riêng chỉ khách được gán dùng được; khách khác nhận `not_owner`.
4. "Không áp cho hàng đang giảm giá" bỏ qua dòng có giá từ bảng giá sale/thành viên.
5. Ví hiện đúng trạng thái và số tiền còn thiếu; "Chọn mã tốt nhất" chọn đúng mã giảm nhiều nhất.
6. Huỷ đơn hoàn lượt tổng và lượt mỗi khách đúng một lần.
7. Tắt plugin: mã đã đồng bộ không áp nữa nếu dùng rule của plugin đang tắt (Core bỏ qua + log); đơn cũ giữ nguyên.
