# ADR-038 — URL storefront theo ngôn ngữ: tiếng Việt không tiền tố, tiếng Anh dưới `/en`

- Trạng thái: Accepted · Ngày: 2026-11-03 · Người quyết định: Owner
- Triển khai: **Implemented** (Core 0.3.59–0.3.62) — mục 1–3, 9: định tuyến, ngôn ngữ từ URL, `LocalizedUrls`/`vani_route`, tiền tố plugin theo ngôn ngữ (bảng đường dẫn ở `config/vanishop.php` `locale.paths` thay vì một class, vì arch test chặn mã ngôn ngữ trong `modules/`); mục 4: slug theo ngôn ngữ cho sản phẩm/danh mục/bộ sưu tập (`vani.cms` chưa đa ngôn ngữ nên chỉ một ngôn ngữ, bản `/en` noindex); mục 5–7: contract `PageSeo`, canonical/hreflang/noindex, sitemap đa ngôn ngữ, nút chuyển ngôn ngữ; mục 8: `orders.locale`, `customers.preferred_locale`, thông báo theo ngôn ngữ của đơn; mục 10: chỉ có cache CDN theo URL (`s-maxage`), không có purge ở ứng dụng — đổi slug thấy sau tối đa `s-maxage`.
- Liên quan: [ADR-025](ADR-025-native-storefront-ssr-slots.md), [ADR-037](ADR-037-vani-helpers.md), [reference-parity-refactor §3.16-7](../20-roadmap/reference-parity-refactor.md), [storefront](../14-storefront/storefront.md), [vietnam-localization](../03-domains/vietnam-localization.md).

## Context
- Nội dung catalog đã có bản dịch vi/en (`style_translations`, `category_translations`, `collection_translations`, …), nhưng **slug chỉ có một** trên bảng gốc (`styles.slug`, `categories.slug`, `collections.slug`, `brands.slug`, unique toàn bảng).
- Storefront native lấy ngôn ngữ từ header `X-Vani-Locale` (middleware `vani.storefront-context`). Trình duyệt không gửi header này → trang web thực tế **luôn tiếng Việt**; trang cache CDN gửi `Vary: X-Vani-Locale`.
- Đường dẫn storefront là tiếng Việt cố định (`/san-pham`, `/danh-muc`, `/thuong-hieu`, `/tim-kiem`, `/gio-hang`, `/thanh-toan`, `/tai-khoan`, …); plugin xin tiền tố riêng qua `storefrontPages(prefix:)` (`vani.cms`: `/trang`, `/tin-tuc`).
- Đơn hàng và khách hàng chưa lưu ngôn ngữ; thông báo chọn mẫu theo locale nhưng không có nguồn locale của đơn.
- Owner cần bản tiếng Anh **được Google index riêng**. Search engine không gửi header ngôn ngữ, và chỉ coi hai bản là hai trang khi chúng có URL khác nhau.

## Decision
1. **Cấu trúc URL**
   - `vi` (mặc định): giữ nguyên mọi URL hiện tại, không tiền tố. Không link nào đang được index bị đổi.
   - `en`: tiền tố `/en` + **đoạn đường dẫn tiếng Anh**: `/en/products/{slug}`, `/en/category/{slug}`, `/en/brands`, `/en/brands/{slug}`, `/en/search`, `/en/cart`, `/en/checkout`, `/en/orders/{order}`, `/en/track-order`, `/en/account/...`. Trang chủ `en` là `/en`.
   - Bảng ánh xạ **tên route → đường dẫn theo locale** khai báo **một chỗ** trong Storefront (`LocalizedRoutes`); route web đăng ký qua bảng này, không viết tay hai lần. Thêm locale sau này chỉ thêm cột trong bảng.
   - `en` thêm vào `vanishop.reserved_paths`; đường dẫn Admin không được là `en`.
2. **Ngôn ngữ lấy từ URL** cho storefront web: middleware đọc tiền tố, đặt `ContextScope` + `App::setLocale`. Không đọc session, cookie, `Accept-Language` hay IP; **không tự chuyển hướng** theo ngôn ngữ trình duyệt (bot crawl từ nước ngoài sẽ bị đẩy sai bản). Trang web không còn `Vary: X-Vani-Locale` (URL đã khác nhau). Storefront API giữ nguyên header `X-Vani-Locale`.
3. **Sinh URL theo locale**: mọi URL storefront trong view, email, sitemap, `LinkResolver`, helper `vani_link` đi qua `LocalizedUrls::route(string $name, array $params = [], ?string $locale = null)` — mặc định locale hiện hành. Không dùng `route('storefront.*')` trực tiếp trong view theme (arch test cho `modules/`, hướng dẫn cho theme).
4. **Slug theo ngôn ngữ**
   - Slug trên bảng gốc **là slug `vi`** (không đổi, không migrate dữ liệu).
   - Thêm cột `slug` **nullable** vào `style_translations`, `category_translations`, `collection_translations`; unique `(locale, slug)`. Bản dịch `vi` không dùng cột này.
   - `brands`: một slug cho mọi ngôn ngữ (tên thương hiệu không dịch).
   - Tra `/en/products/{slug}`: tìm slug `en` trước, rồi slug gốc. Nếu khớp slug gốc trong khi đối tượng đã có slug `en` → **301** sang URL có slug `en`.
   - Admin: form bản dịch `en` có ô slug, tự gợi ý từ tên tiếng Anh (bỏ dấu, kebab-case); trống = dùng slug gốc.
   - Plugin có nội dung dịch (`vani.cms`) theo cùng quy ước trên bảng `plg_*` của mình.
5. **Trang chưa có bản dịch `en`**: vẫn trả 200 (link nội bộ không gãy), nội dung `vi` trong khung giao diện `en`, `<meta name="robots" content="noindex,follow">`, canonical trỏ về URL `vi`, không khai báo `hreflang` cho bản `en`. "Có bản dịch" = có bản ghi translation `en` với tên không rỗng.
6. **SEO**
   - Trang index được có canonical tự trỏ theo locale và `<link rel="alternate" hreflang="vi">`, `hreflang="en"` (nếu có bản dịch), `hreflang="x-default"` → bản `vi`.
   - `sitemap.xml` liệt kê URL cả hai ngôn ngữ, kèm `xhtml:link` alternate; bản `en` chưa dịch không vào sitemap.
   - JSON-LD (`Product`, `BreadcrumbList`) dùng URL và tên theo locale của trang. Giá trong JSON-LD tuân theo `storefront.price_visibility`.
   - Trang giao dịch (giỏ, checkout, đơn, tài khoản, tra cứu đơn) ở cả hai ngôn ngữ đều `noindex`.
7. **Chuyển ngôn ngữ**: controller trang công khai trả **URL tương ứng** của cùng đối tượng ở mọi locale (`AlternateUrls`), dùng chung cho `hreflang` và nút chuyển ngôn ngữ; trang không có bản tương ứng → trang chủ của locale đích. Không lưu lựa chọn vào cookie.
8. **Phiên, giỏ, đơn, thông báo**
   - Giỏ, đăng nhập, checkout dùng chung phiên giữa hai ngôn ngữ (cùng domain).
   - `orders.locale` (snapshot lúc đặt, mặc định `vi`) và `customers.preferred_locale` (cập nhật khi khách đặt hàng/sửa hồ sơ). Thông báo giao dịch chọn mẫu theo `orders.locale`, rơi về `vi` khi thiếu mẫu `en`.
   - Đơn tạo từ Admin hoặc Storefront API: locale từ context của request (header với API), mặc định `vi`.
9. **Plugin**: `storefrontPages(prefix:)` nhận thêm dạng mảng theo locale (`['vi' => 'tin-tuc', 'en' => 'news']`). Plugin chỉ khai báo chuỗi → trang chỉ có bản `vi`; `LocalizedUrls` trả URL `vi` khi được hỏi bản `en`. Slot và view plugin nhận locale từ context như hiện nay.
10. **Cache trang công khai**: khoá cache theo URL đầy đủ (đã khác nhau giữa hai locale). Xoá cache khi đổi slug hoặc bản dịch phải xoá cả hai URL.

## Alternatives
- **Tiền tố cho cả hai ngôn ngữ** (`/vi/...`, `/en/...`): đổi toàn bộ URL đang được index, phải 301 hàng loạt. Loại.
- **Subdomain `en.`**: thêm DNS, chứng chỉ, cookie phiên khác domain (giỏ không dùng chung), Search Console riêng. Loại.
- **Giữ đoạn đường dẫn tiếng Việt cho bản `en`** (`/en/san-pham/...`): đơn giản hơn nhưng URL khó đọc với khách nước ngoài, mất từ khoá trong URL. Loại.
- **Ngôn ngữ theo cookie/`Accept-Language` trên cùng URL**: search engine chỉ thấy một bản. Loại.
- **Chuyển slug `vi` vào bảng translation** (bảng gốc không còn slug): mô hình gọn hơn nhưng phải migrate dữ liệu và sửa mọi truy vấn hiện có. Loại; có thể làm sau bằng ADR riêng.

## Consequences
- (+) Bản tiếng Anh được index riêng, URL `vi` hiện tại không đổi.
- (+) Trang công khai vẫn cache CDN được (khoá theo URL), bỏ được `Vary` theo header trên web.
- (+) Đơn và khách có locale → email đúng ngôn ngữ.
- (−) Thêm cột slug cho ba bảng translation, `orders.locale`, `customers.preferred_locale`; Storefront API bổ sung slug theo locale trong payload (đổi public API, ghi CHANGELOG).
- (−) Theme phải sinh URL qua `LocalizedUrls`/`vani_link`; view nào gọi `route('storefront.*')` trực tiếp sẽ ra URL `vi` trên trang `en`.
- (−) Đổi slug `en` của đối tượng đã được index làm gãy URL cũ: cần cơ chế lưu lịch sử slug + 301 (chưa có cho cả `vi`) — tách thành hạng mục riêng.

## Trade-offs
Thêm một lớp sinh URL theo locale và ít cột dữ liệu, đổi lấy bản tiếng Anh có giá trị SEO mà không làm xáo trộn URL tiếng Việt đang chạy.
