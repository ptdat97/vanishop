# Đặc tả plugin `vani.wishlist` — Yêu thích

> Trạng thái: **Implemented** (plugin 1.0.0, Core 0.3.76, 2026-11-05). Nguồn: [reference-parity §3.15-3](../../20-roadmap/reference-parity-refactor.md), [plugin-catalog](../plugin-catalog.md). Plugin mới — không chuyển từ VaniCommerce (không có plugin cũ tương ứng), không đọc mã nguồn tham chiếu.

## 1. Phiếu kiểm kê

| Mục | Nội dung |
|---|---|
| Tính năng | Thêm/bỏ sản phẩm (theo mẫu — style) vào danh sách yêu thích từ thẻ sản phẩm và trang sản phẩm; trang danh sách trong tài khoản; khách vãng lai lưu ở trình duyệt, gộp vào tài khoản khi đăng nhập; báo khi có hàng lại / giảm giá (tuỳ chọn, cần consent marketing) |
| Dữ liệu | `plg_wishlist_items` (plugin sở hữu) |
| Cấu hình | `notify_back_in_stock`, `notify_price_drop` (bool, mặc định tắt) |
| Quyền | Không có màn hình Admin riêng; mẫu tin ở Admin → Thông báo |
| Storefront | Nút tim ở thẻ sản phẩm (`vani.storefront.plp.card_badges`) và PDP (`vani.storefront.pdp.after_add_to_cart`), link + số món ở header (`vani.storefront.header.actions`), trang `/tai-khoan/yeu-thich` (`/en/account/wishlist`), mục menu tài khoản, script (`vani.storefront.layout.body_end`, tài nguyên tĩnh của plugin) |
| Job/event | `AvailabilityChanged`, `PriceChanged` (thông báo), `CustomerMerged`, `CustomerAnonymized` (dữ liệu khách) |
| Tích hợp ngoài | Không (gửi tin qua `Notifier` của Core, kênh do plugin kênh cung cấp) |

## 2. Hành vi

1. **Đơn vị lưu**: một mẫu (style), không theo màu/size. Một khách tối đa 100 mẫu; thêm lại mẫu đã có không tạo dòng mới.
2. **Khách đã đăng nhập** (phiên web hoặc Bearer): lưu ở server. Nút tim là form POST — chạy được khi tắt JS (thêm/bỏ rồi quay lại trang).
3. **Khách vãng lai**: JS lưu tối đa 100 id mẫu trong `localStorage` của trình duyệt (không cookie, không server). Không JS: bấm tim → trang đăng nhập.
4. **Gộp khi đăng nhập**: lần đầu trang biết khách đã đăng nhập (qua dữ liệu phiên của trang — `vani:session`), JS gửi danh sách trong trình duyệt lên server rồi xoá bản trong trình duyệt. Gộp là hợp (union), giữ giới hạn 100; mẫu không còn bán bị bỏ.
5. **Trang cache CDN**: HTML của nút tim giống nhau với mọi khách; JS tô trạng thái theo danh sách của khách (server hoặc trình duyệt). Không lộ dữ liệu khách vào bản cache.
6. **Trang danh sách** `/tai-khoan/yeu-thich` (cần đăng nhập): thẻ sản phẩm (giá, còn hàng theo chính sách hiện giá của cửa hàng) mới thêm trước; mẫu đã ẩn/ngừng bán hiện "Không còn bán" kèm nút bỏ.
7. **Thông báo** (bật riêng từng loại trong cấu hình plugin): khi mẫu trong danh sách **có hàng lại** (trước hết hàng, nay còn) hoặc **giảm giá** (giá thấp nhất thấp hơn giá lúc thêm/lần báo trước) → tin `wishlist_back_in_stock` / `wishlist_price_drop` loại **marketing** qua `Notifier` (Core chỉ gửi khi khách có consent marketing theo kênh). Mỗi mẫu mỗi khách tối đa một tin mỗi loại trong 7 ngày (khoá idempotency theo ngày).
8. **Xoá/gộp khách**: khách bị ẩn danh hoá → xoá danh sách; gộp hai hồ sơ → chuyển danh sách sang hồ sơ đích (bỏ trùng).

## 3. Dữ liệu

`plg_wishlist_items`: `id`, `customer_id`, `style_id`, `price_amount` (giá thấp nhất lúc thêm/lần báo giá gần nhất, null khi ẩn giá), `in_stock` (bool, lần kiểm gần nhất), `notified_stock_at`, `notified_price_at`, `created_at`, `updated_at`; unique (`customer_id`, `style_id`). Không khoá ngoại sang bảng Core ([ADR-027](../../19-adr/ADR-027-plugin-data-no-core-columns.md)); manifest khai báo `owned`.

## 4. API

| Kênh | Đường dẫn | Ghi chú |
|---|---|---|
| Storefront API | `GET /api/storefront/v1/x/vani-wishlist/ids` | `{data: {ids[]}}`, `private, no-store` — wishlist.js tô nút tim |
| Storefront API | `GET/POST /api/storefront/v1/x/vani-wishlist/items`, `DELETE …/items/{styleId}` | `GET` trả thẻ sản phẩm; `POST`/`DELETE` trả `{data: {ids}}` |
| Storefront API | `POST /api/storefront/v1/x/vani-wishlist/items/merge` (`style_ids[]`) | Gộp danh sách của trình duyệt sau khi đăng nhập |
| Web | `POST /p/vani-wishlist/toggle` (`style_id`) | Nút tim khi **tắt JS** (quay lại trang; vãng lai → trang đăng nhập) |

Xác thực API: Bearer (app) hoặc phiên web cùng nguồn — wishlist.js gọi bằng cookie phiên + `X-CSRF-TOKEN` (ADR-044, 1.1.0).

## 5. Extension point dùng và phần Core bổ sung (bước 3)

Đủ sẵn: slot thẻ sản phẩm/PDP/header/body_end, `storefrontPages`, `storefrontRoutes`, `accountPage`, `ProductCards`, `VariantDirectory`, `Notifier` + `NotificationCatalog`, events giá/tồn/khách, `assets` + `vani_asset`.

Thiếu — thêm vào Core 0.3.76:
- **`accountRoutes($file, ['vi' => 'yeu-thich', 'en' => 'wishlist'])`**: trang plugin dưới đường dẫn tài khoản theo ngôn ngữ (`/tai-khoan/…`, `/en/account/…`), bắt buộc đăng nhập; đoạn đường dẫn không trùng trang tài khoản của Core hay plugin khác.
- **Dữ liệu phiên cho JS của plugin**: `csrf_token` trong `/_vani/phien` và dữ liệu nhúng (`CustomerSessionState`); theme phát `document` event `vani:session` (detail = dữ liệu phiên) và đặt `window.vaniSession` — trang cache CDN không có token CSRF trong HTML.

## 6. Tiêu chí chấp nhận

- Đã đăng nhập: thêm/bỏ ở thẻ và PDP (có/không JS), trang danh sách đúng thứ tự, giới hạn 100.
- Vãng lai: lưu trình duyệt; đăng nhập → gộp, bỏ trùng, bỏ mẫu không còn bán.
- HTML trang cache giống nhau giữa khách vãng lai và khách đăng nhập.
- Thông báo chỉ khi bật cấu hình, chỉ có hàng lại / giảm giá thật, tối đa 1 tin/loại/7 ngày, marketing (Core lọc consent).
- Ẩn danh hoá/gộp khách xử lý danh sách; gỡ plugin `--purge` xoá bảng.
