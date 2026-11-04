# ADR-040 — Link có kiểu và menu storefront theo vị trí của theme

- Trạng thái: Accepted · Ngày: 2026-11-04 · Người quyết định: Owner (duyệt đề xuất làm §3.16-1/2 trước §3.16-7)
- Liên quan: [reference-parity-refactor §3.16-1/2](../20-roadmap/reference-parity-refactor.md), [ADR-021](ADR-021-storefront-composition-module.md) (Storefront không có bảng), [ADR-025](ADR-025-native-storefront-ssr-slots.md), [ADR-038](ADR-038-localized-storefront-urls.md) (URL theo ngôn ngữ).

## Context
Header, footer của theme `vani-base` cứng một vài link; `vani.cms` chèn link trang/tin tức qua slot. Cửa hàng cần menu nhiều cấp do nhân viên chỉnh, trỏ tới danh mục, thương hiệu, sản phẩm, trang nội dung, và link không được vỡ khi đổi slug hay ẩn đối tượng. Trang công khai có cache CDN (`s-maxage`).

## Decision
1. **Link có kiểu** `Storefront\Contracts\Data\LinkTarget {type, value, label?}`: lưu loại + giá trị (id đối tượng, tên route, URL), không lưu URL đã sinh. `LinkResolver` phân giải lúc render thành `ResolvedLink {url, label, exists}`; `exists = false` (đối tượng đã ẩn/xoá, plugin tắt) → nơi render bỏ link.
2. **Extension point `LinkType`** (tag `vani.storefront.link_types`): `type`, `label`, `searchable`/`options`/`search` cho Admin, `accepts` (kiểm dạng), `resolve(values, locale)` **theo lô** — một truy vấn mỗi loại cho cả menu. Core: `category`, `collection`, `brand`, `product` (qua contract mới `Catalog\Contracts\CatalogLookup`), `route` (danh sách route công khai cho phép), `url` (chỉ `/…`, `http(s)://`, `mailto:`, `tel:`). Tham chiếu từ plugin: `vani.cms` `page`, `article`. Contract test: `Modules\Storefront\Testing\LinkTypeContract`.
3. **Không cache qua request**: `LinkResolver`/`MenuPresenter` không chia sẻ instance; ghi nhớ trong một lượt render. Đổi slug thấy ngay trên trang không cache; trang có cache CDN đổi theo `s-maxage`.
4. **Vị trí menu do theme khai báo** trong `theme.json` (`menus: {mã: {label, depth 1–3}}`, theme gần nhất trong chuỗi có khai báo). `vani-base`: `header` (3 cấp), `mobile` (2), `footer_1..4` (1). Vị trí chưa xuất bản → theme dùng link mặc định của nó.
5. **Lưu ở cấu hình cửa hàng** `core.storefront.menu.<vị trí>` (Storefront không có bảng): nháp, bản xuất bản, 10 bản xuất bản trước. Admin → Giao diện → Menu: sửa nháp, xuất bản (audit), bỏ nháp, khôi phục bản cũ vào nháp. Mục: link (hoặc không link = tiêu đề nhóm), nhãn ghi đè theo ngôn ngữ, huy hiệu `new`/`sale`, ảnh (chỉ cấp 1), bật/tắt, khung giờ hiển thị (giờ cửa hàng). Tối đa 200 mục mỗi menu.

## Alternatives
- **Lưu URL đã sinh**: đơn giản nhưng vỡ khi đổi slug, không ẩn được link tới đối tượng đã ẩn. Loại.
- **Bảng `menus`/`menu_items` riêng**: truy vấn từng mục dễ hơn, nhưng Storefront không có bảng (ADR-021) và module Content (Designed) chưa có; menu nhỏ (≤ 200 mục) đọc một lần là đủ. Chuyển sang bảng khi Content được làm.
- **Cache kết quả phân giải qua request**: nhanh hơn nhưng cần vô hiệu khi đổi slug/ẩn đối tượng ở nhiều module. Loại theo đặc tả.
- **Kéo-thả** trong Admin: thay bằng nút lên/xuống/thụt vào/thụt ra (dùng được bằng bàn phím, không thêm thư viện). Kéo-thả để sau nếu nhân viên cần.

## Consequences
- (+) Link tự theo slug/tên; mục trỏ tới đối tượng đã ẩn tự biến mất; plugin thêm loại link không sửa Core.
- (+) Xuất bản/khôi phục có lịch sử; storefront chỉ đọc bản đã xuất bản.
- (−) Danh mục phân giải qua cây danh mục đang hiện (một truy vấn cho cả cây), không chọn theo id.
- (−) Trang có cache CDN thấy menu mới sau tối đa `s-maxage` (mặc định 300 s).
- (−) `vani.cms` vẫn chèn link "Trang"/"Tin tức" qua slot cạnh menu; muốn chỉ dùng menu thì tắt hiển thị link trong cấu hình trang của plugin.
