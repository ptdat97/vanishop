# vani.pricing-campaign

Một màn hình cho mọi **chiến dịch giá**: flash sale, hot deal, giá thành viên, sale cuối tuần, khung giờ vàng ([đặc tả](../../../docs/05-plugin/specs/pricing-campaign.md)). Giá chiến dịch là **bảng giá** của Core (giá hiện đúng ở thẻ sản phẩm, trang sản phẩm, giỏ, dòng đơn), không phải khuyến mãi giỏ.

## Quản trị

Admin → Marketing → **Chiến dịch giá** (quyền `pricing_campaign.view`, `pricing_campaign.manage`, preset Marketing).

- Mẫu điền sẵn: Flash sale, Hot deal, Giá thành viên, Sale cuối tuần (T7–CN), Tuỳ chỉnh.
- Hiển thị: nhãn cho khách, chữ và màu badge, màu nhấn, banner (Thư viện ảnh), nội dung, bật/tắt đếm ngược và số suất.
- Lịch: bắt đầu – kết thúc + lịch lặp theo tuần (khung giờ vàng, cuối tuần); trạng thái Nháp / Bật / Dừng ngay; ưu tiên (cao hơn thắng); nhóm khách = giá thành viên.
- Giới hạn: suất cả chiến dịch, mỗi khách tối đa (cả chiến dịch).
- Sản phẩm: thêm theo tên hoặc cả danh mục; mỗi sản phẩm chọn đồng giá / giảm % / giảm tiền, suất và giới hạn mỗi khách riêng; "Áp cho ô trống / tất cả". Bảng hiện giá niêm yết và giá chiến dịch xem trước.
- Cảnh báo không chặn: suất lớn hơn tồn bán được, sản phẩm ở chiến dịch khác chạy cùng lúc. Chặn: giá không thấp hơn giá niêm yết, giảm vượt mức tối đa của cửa hàng.

Mỗi chiến dịch đồng bộ thành bảng giá do plugin quản lý (`ManagedPriceLists`, kèm suất `PriceQuotas`) và một Campaign của Core (`Campaigns::sync`, báo cáo dùng chung) — hai nơi đó chỉ xem. Giá tính cho mọi size/màu từ giá niêm yết (`ListPriceReader`), làm tròn lên tới 1.000đ; giá niêm yết đổi thì giá chiến dịch tự tính lại.

## Storefront

- Thẻ sản phẩm (slot `vani.storefront.plp.card_badges`): badge của chiến dịch đang chạy, "còn N".
- Trang sản phẩm (slot `vani.storefront.pdp.after_price`): đếm ngược tới hết lượt (hoặc "Bắt đầu sau…"), thanh đã bán, "Còn N suất", "Mỗi khách tối đa N"; số suất đọc lại qua API không cache.
- Trang **Ưu đãi** `/uu-dai` (`/en/deals`): chiến dịch đang chạy và sắp chạy, banner, lưới sản phẩm.
- Block Builder **Chiến dịch giá**: một chiến dịch hoặc tự động chọn chiến dịch đang chạy ưu tiên cao nhất.
- Storefront API `x/vani-pricing-campaign`: `GET campaigns`, `GET products/{product}` (`openapi.json`, `schemas/`).
- Hết suất → giá về giá thường; khách đặt vượt giới hạn → checkout chặn kèm số còn mua được (Core).

## Dữ liệu

`plg_pricing_campaigns`, `plg_pricing_campaign_items` (id sản phẩm, bảng giá, campaign, ảnh của Core — không khoá ngoại).

## Giới hạn

- Badge/đếm ngược hiện theo chiến dịch chứa sản phẩm, kể cả khi sản phẩm đang có giá tốt hơn ở bảng giá khác ưu tiên cao hơn.
- Số suất trên HTML trang cache được có thể trễ; trang sản phẩm cập nhật lại bằng JS.
- Tắt plugin: bảng giá của chiến dịch vẫn là bảng giá của Core (giá vẫn áp tới hết lịch); tắt ở Admin → Bảng giá không được vì bảng giá chỉ xem — bật lại plugin để dừng chiến dịch.
