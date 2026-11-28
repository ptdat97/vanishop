# Đặc tả: Chiến dịch giá (`vani.pricing-campaign`)

> Trạng thái: **Implemented** (plugin 0.1.0, Core ≥ 0.5.119, 2026-11-28). Nguồn: plugin PricingCampaign 1.1.6 của VaniCommerce (Owner — [ADR-036](../../19-adr/ADR-036-service-result-hooks.md): chỉ kiểm kê tính năng, phần gọi lõi cũ viết lại trên contract VaniShop). Cần Core đợt 3 (B1–B4, §5).

## 1. Mục tiêu

Một màn hình để chạy mọi loại **chiến dịch giá**: flash sale, hot deal, giá thành viên, sale cuối tuần, khung giờ vàng. Nhân viên chọn sản phẩm và cách giảm theo **sản phẩm** (không phải từng SKU); khách thấy giá sale ngay ở thẻ sản phẩm, trang sản phẩm, giỏ, kèm badge, đếm ngược, thanh "đã bán".

**Giá chiến dịch là bảng giá** (sale hoặc thành viên) do plugin quản lý qua `ManagedPriceLists`, không phải khuyến mãi giỏ: giá hiện đúng ở mọi nơi, dòng đơn chụp `price_list_code`, có lịch sử giá, trần giảm, cache trang biết mốc đổi giá, báo cáo Campaign dùng lại.

## 2. Kiểm kê plugin cũ

| Tính năng cũ | Giữ / đổi |
|---|---|
| Chiến dịch: tên nội bộ, nhãn công khai, badge (chữ, kiểu màu), màu nhấn, banner, nội dung, bật đếm ngược, bật thanh tồn, ưu tiên, bật/tắt, bắt đầu/kết thúc | Giữ; banner từ Thư viện ảnh; nội dung Markdown an toàn |
| Sản phẩm trong chiến dịch: chiến lược đồng giá / giảm % / giảm số tiền, giá trị, suất, giới hạn mỗi khách, thứ tự | Giữ; giá tính cho mọi variant của sản phẩm từ giá niêm yết, ghi vào bảng giá được quản lý |
| Áp mặc định cho ô trống / tất cả; thêm theo tên, danh mục, thuộc tính | Giữ (thuộc tính → bộ sưu tập/thương hiệu qua `CatalogLookup`) |
| Suất cả chiến dịch, giới hạn mỗi khách cả chiến dịch | Giữ; suất giá Core (`PriceQuotas`) |
| Cảnh báo: giá sau giảm ≥ giá gốc, % ngoài 1–100, suất > tồn thực, sản phẩm đã ở chiến dịch đang chạy khác | Giữ; thêm cảnh báo vượt trần giảm của cửa hàng |
| Ghi lượt mua theo dòng đơn, trừ/hoàn suất khi huỷ | **Đổi**: Core trừ suất trong transaction đặt đơn, hoàn khi huỷ, trả khi huỷ một phần |
| Vượt giới hạn mỗi khách | **Chặn** ở giỏ/checkout: "Mỗi khách tối đa N sản phẩm giá chiến dịch" (Owner 2026-11-27) |
| Trang chiến dịch: lọc đang diễn ra / sắp diễn ra, sắp xếp mặc định / sắp kết thúc / sắp hết hàng | Giữ |
| Module page builder "chiến dịch" (tự chọn chiến dịch ưu tiên cao nhất) | Giữ; `StorefrontBlock` |
| Nhãn sale trên ảnh sản phẩm (gộp plugin nhãn cũ) | Giữ; slot `plp.card_badges` + PDP |
| Weekend sale, hot deal theo lịch lặp | Giữ; lịch lặp `WeeklySchedule` của Core |
| Giá thành viên | Giữ; bảng giá `member` gắn nhóm khách |

## 3. Dữ liệu

- `plg_pricing_campaigns`: id, mẫu (`flash_sale` \| `hot_deal` \| `member` \| `weekend` \| `custom` — chỉ để điền sẵn form), tên nội bộ, nhãn, badge chữ/kiểu, màu nhấn, `banner_media_id`, nội dung, hiện đếm ngược, hiện thanh suất, ưu tiên, nhóm khách (member), bắt đầu/kết thúc, lịch lặp (JSON `WeeklySchedule`), suất cả chiến dịch, giới hạn/khách cả chiến dịch, trạng thái, `price_list_id`, `campaign_id` (Core, không FK), `lock_version`.
- `plg_pricing_campaign_items`: campaign id, `product_id` (Core, không FK), chiến lược, giá trị, suất, giới hạn/khách, thứ tự. Duy nhất (chiến dịch, sản phẩm).
- Suất đã bán và lượt mua theo khách ở Core (`price_quotas`), không ở plugin.

## 4. Hành vi

### 4.1 Admin → Marketing → Chiến dịch giá

- Danh sách: tên, mẫu, lịch (kèm lịch lặp), trạng thái (Nháp / Sắp chạy / Đang chạy / Đã kết thúc / Đã dừng), số sản phẩm, suất đã bán/tổng.
- Form theo khối: Thông tin & hiển thị · Lịch chạy (khung ngày + lặp: ngày trong tuần, giờ bắt đầu–kết thúc) · Đối tượng (mọi khách / nhóm khách) · Giới hạn · Sản phẩm (bảng: sản phẩm, giá niêm yết thấp–cao, chiến lược, giá trị, giá sau giảm, suất, giới hạn/khách; công cụ "áp cho ô trống / tất cả").
- Lưu → tính giá từng variant = chiến lược trên giá niêm yết, làm tròn tới 1.000đ, không âm → `ManagedPriceLists::sync` (B1) + `PriceQuotas` (B3) + `Campaigns::sync` (B4). Cảnh báo không chặn: suất > tồn bán được, sản phẩm trùng chiến dịch đang chạy; chặn: giá sau giảm ≥ giá gốc, vượt trần giảm.
- Giá niêm yết đổi (`PriceChanged` của bảng `base`) → tính lại giá của chiến dịch chứa sản phẩm đó.
- Dừng ngay / kích hoạt / xoá nháp theo luật Campaign của Core. Quyền `pricing_campaign.view`, `pricing_campaign.manage` (preset Marketing; cần thêm `pricing.manage` như Campaign Core).

### 4.2 Storefront

- **Thẻ sản phẩm** (`StorefrontEnricher` `product_card` + slot `plp.card_badges`): badge của chiến dịch đang thắng giá (theo `ResolvedPrice::$priceListCode`), "Còn N suất" khi bật.
- **Trang sản phẩm**: đếm ngược tới hết khung hiện tại (lịch lặp: tới hết khung giờ hôm nay), thanh "Đã bán x%", "Mỗi khách tối đa N"; chiến dịch sắp diễn ra → "Bắt đầu sau…" kèm giá dự kiến.
- **Trang `/uu-dai`** (`storefrontPages`, `/en/deals`): chiến dịch đang chạy và sắp chạy, banner, nội dung, lưới sản phẩm; lọc, sắp xếp (tham số lọc thêm vào `page_cache.query_params`).
- **Block "Chiến dịch giá"** cho Builder: chọn chiến dịch hoặc "tự động — ưu tiên cao nhất đang chạy", số sản phẩm.
- Số suất còn lại đọc qua API không cache (JS cập nhật); HTML trong cache trang hết hạn theo mốc đổi giá (`nextChangeAfter` hiểu lịch lặp).
- Storefront API `x/vani-pricing-campaign/*` + `openapi.json` (ADR-044).

## 5. Core cần có trước (đợt 3)

| # | Điểm mở rộng |
|---|---|
| B1 | `ManagedPriceLists::sync/remove/ids` — bảng giá sale/member của plugin; Admin Bảng giá chỉ xem, liên kết sang plugin; kiểm trần, lịch sử giá, `PriceChanged` |
| B2 | `WeeklySchedule` (Shared): ngày trong tuần + khung giờ theo múi giờ cửa hàng, cho bảng giá, khuyến mãi, Campaign; chiến lược giá và `nextChangeAfter` hiểu lịch lặp |
| B3 | `PriceQuotas`: suất theo bảng giá / sản phẩm / variant + giới hạn/khách; hết suất → bộ giải giá bỏ mức giá đó; trừ trong transaction đặt đơn (UPDATE có điều kiện), hoàn khi huỷ, trả khi huỷ một phần; kiểm giới hạn/khách ở checkout; đọc tình trạng |
| B4 | `campaigns.managed_by` + `Campaigns::sync`: chiến dịch của plugin hiện chỉ xem ở Admin → Campaign, dùng lại báo cáo `CampaignUsage` |
| (0.5.119) | `ListPriceReader`: giá niêm yết theo variant để plugin tính giá chiến dịch (Owner duyệt 2026-11-28) |

## 6. Tiêu chí chấp nhận

1. Giá chiến dịch hiện như nhau ở thẻ, trang sản phẩm, giỏ, checkout, dòng đơn (`price_list_code`).
2. Flash sale 10 suất, 100 request đặt đồng thời → đúng 10 đơn hưởng giá sale; hết suất → giá về giá thường ngay, storefront hiện "Đã hết suất".
3. Khách đặt vượt giới hạn mỗi khách → bị chặn với số tối đa; huỷ đơn trả suất và lượt của khách đúng một lần.
4. Sale cuối tuần chỉ có giá sale trong khung đã đặt (theo múi giờ cửa hàng); cache trang đổi giá đúng mốc.
5. Giá thành viên chỉ áp cho khách thuộc nhóm; trang công khai cache được vẫn hiện giá chung.
6. Giá niêm yết đổi → giá chiến dịch tính lại; không giá nào vượt trần giảm hoặc ≥ giá gốc.
7. Tắt plugin: bảng giá được quản lý vẫn là bảng giá của Core (giá đã bán giữ nguyên); Admin cho tắt bảng giá đó.
