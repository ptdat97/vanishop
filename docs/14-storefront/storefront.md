# Storefront

> Trạng thái: native storefront **Partially Implemented** (slice 12b, 2026-10-02); tầng ghép + Storefront API: Implemented.
>
> | Phần | Trạng thái |
> |---|---|
> | Theme `vani-base` (`custom/theme/vani-base`), theme đang hoạt động `core.theme` + theme con (`parent`), token `theme.tokens` → CSS variables | Implemented |
> | Trang SSR: `/`, `/danh-muc/{slug}`, `/thuong-hieu`, `/thuong-hieu/{slug}`, `/tim-kiem`, `/san-pham/{slug}` (JSON-LD `Product`; breadcrumb + JSON-LD `BreadcrumbList` ở PDP, danh mục, thương hiệu — 0.3.66), `/gio-hang`, `/thanh-toan`, `/don-hang/{id}` — mua được hoàn toàn bằng form khi JS tắt | Implemented |
> | Slot storefront (`modules/Storefront/hooks.php`) + `<x-vani::hook-slot>` | Implemented; `vani.storefront.account.menu` bổ sung mục menu ngoài các trang tài khoản do plugin đăng ký qua `accountPage()` |
> | Đảo tương tác | JS thuần, không thư viện (đổi ảnh theo màu, tự gửi form số lượng). Alpine chờ duyệt dependency |
> | Tài khoản `/tai-khoan` (đăng nhập email + mật khẩu — OTP theo SĐT là plugin `vani.phone-otp-login`, tổng quan, đơn hàng + huỷ, địa chỉ; giỏ vãng lai gộp khi đăng nhập), tra cứu đơn `/tra-cuu-don`, `robots.txt`, `sitemap.xml` | Implemented (2026-10-02) |
> | Đổi/trả trên native (2026-10-08): partial `order-returns` trên `/tai-khoan/don-hang/{id}` và `/don-hang/{id}` (token đơn trong phiên) — danh sách yêu cầu + huỷ khi chờ duyệt, form chọn số lượng, trả hoàn tiền hoặc đổi size/màu cùng mẫu (size hết hàng bị khoá), lý do, ghi chú; `POST /don-hang/{id}/doi-tra`, `POST /don-hang/{id}/doi-tra/{return}/huy`; không cần JS. Đổi mẫu khác: qua CSKH | Implemented |
> | Cache CDN trang công khai — **tuỳ chọn nâng cao**, mặc định tắt (0.3.34, 0.3.68, §5): không CDN thì trang có phiên, render sẵn theo khách; bật thì không phiên, `Cache-Control: public, s-maxage`, phần riêng của khách qua `/_vani/phien` — hiển thị như nhau | Implemented |
> | Chọn dòng giỏ (ô chọn + "Chọn tất cả", `POST /gio-hang/chon` — chạy khi tắt JS), nút "Mua ngay" ở PDP (`buy_now` → `/thanh-toan`), giỏ mini ở header (0.3.64) | Implemented |
> | Page builder cho trang khác ngoài trang chủ; purge CDN theo event | Designed |
> | Địa chỉ checkout | Có `vani.provinces-vn`: chọn tỉnh/phường (JS tải phường theo tỉnh; không JS: nút tải lại). Không có danh mục: nhập tự do | Quyết định: [ADR-009](../19-adr/ADR-009-storefront-architecture.md), [ADR-021](../19-adr/ADR-021-storefront-composition-module.md), [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md), [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md) (một website, một giao diện).

## 1. Nguyên tắc

> Đánh giá 2026-11-11: luồng đọc đã dùng chung tầng ghép; luồng ghi, "vỏ" trang và JSON của theme còn lệch API — kế hoạch ở [ADR-044](../19-adr/ADR-044-storefront-api-first.md).

- Storefront **độc lập với business domain**. Business logic **không** nằm trong Blade, Vue/React component, theme hay CSS (rule R10).
- Storefront chỉ dùng **Storefront Application layer**: các query/command giống hệt những gì Storefront API công bố.

```text
Commerce Core (modules/*)
      ↓
Storefront Application (Queries/Commands + DTO) ──► /api/storefront/v1 (HTTP, JSON)  ──► Headless / Mobile / Zalo Mini App
      ↓ (gọi in-process, cùng DTO)
Native Storefront (Blade SSR + JavaScript thuần, theme `custom/theme/vani-base`)
```

| Client | Cách dùng |
|---|---|
| **Tầng ghép** | Module `modules/Storefront` (**Implemented** cho API, [ADR-021](../19-adr/ADR-021-storefront-composition-module.md)): `ProductViews` gọi `CatalogReader` + `PriceResolver` + `AvailabilityReader` (chỉ công bố còn/hết/sắp hết, ngưỡng `VANI_LOW_STOCK_THRESHOLD`); `CartPresenter` định dạng giỏ từ contract `Carts` |
| **Native storefront** | Controller storefront gọi **cùng Query/Command** mà API dùng, trong cùng process (không tự gọi HTTP tới chính mình, rule R19). Blade chỉ render DTO |
| **Headless** (Next.js, mobile, Zalo Mini App) | Gọi `/api/storefront/v1` ([api](../06-api/api.md)) |

Nhờ vậy native và headless có cùng hành vi: giá, ATS, khuyến mãi, validation đều tính ở một nơi.

## 2. Một giao diện, tuỳ biến không fork

Cửa hàng có **một theme đang hoạt động** (cấu hình `theme`, mặc định `vani-base`). Brand **không** có theme riêng; brand hiện diện qua trang brand, logo, bộ lọc ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)).

| Lớp tuỳ biến | Lưu ở | Ai chỉnh |
|---|---|---|
| **Theme tokens** (màu, font, radius, spacing, shadow) | Setting cửa hàng `theme.tokens` (JSON) → CSS variables → Tailwind 4 `@theme` | Quản trị |
| **Thông tin cửa hàng** (logo, hotline, chính sách, footer pháp lý của pháp nhân vận hành) | Settings cửa hàng | Quản trị |
| **Brand** (logo, mô tả, banner trang brand, thứ tự hiển thị) | Catalog `brands` | Merchandiser |
| **Collection config** (bộ sưu tập, sắp xếp, ghim) | Catalog | Merchandiser |
| **Content** (trang, bài viết, banner danh mục, nội dung thêm) | Plugin `vani.cms` | Merchandiser |
| **Menu** (vị trí theo theme, có thể thêm link "Thương hiệu") | Setting `core.storefront.menu.<vị trí>` | Quản trị |
| **Layout / Blocks** (page builder trang chủ, gồm block "lưới thương hiệu") | Setting `core.storefront.home_blocks` | Merchandiser |
| **Components** (override view có chọn lọc) | Theme con `custom/theme/<store-theme>/` (`parent: vani-base`) | Dev |

View fallback: theme đang hoạt động → `custom/theme/vani-base` → view mặc định của module. Theme con chỉ override **ít file**.

```
custom/theme/vani-base/
├── theme.json            # tên, parent, blocks hỗ trợ, token mặc định, menus, settings (đầu/chân trang)
├── views/                # layouts, components, pages (pdp, plp, cart, checkout, account)
├── css/app.css           # @theme dùng CSS variables từ token
└── js/app.js             # JS tăng cường (gallery Swiper/PhotoSwipe, giỏ mini, gợi ý tìm kiếm…)
```

## 3. Render và điểm chèn UI (ADR-025)

| Quy tắc | Chi tiết |
|---|---|
| SSR là nguồn nội dung | PDP, PLP, danh mục, trang render đủ ở server; trang đọc được khi JS tắt/lỗi |
| JS = đảo tương tác | Alpine gắn vào gallery, chọn màu/size, mini-cart, form checkout. Không app JS toàn trang, không router phía client |
| JSON bridge | Dữ liệu cho Alpine render sẵn bằng `@js`/`data-*` từ DTO của Presenter; không gọi API lấy lại thứ server đã có. Phần cá nhân hoá (giỏ, giá thành viên) tải qua Storefront API sau khi trang hiện |
| Không ẩn nội dung SSR chờ JS | Chống nhảy layout bằng kích thước cố định/skeleton CSS, không `display:none` rồi chờ JS bật lại |
| Slot UI | `<x-vani::hook-slot name="vani.storefront.pdp.after_price" :args="[$product]" />` render các phần tử plugin trả về (`Storefront\Contracts\Data\SlotView` hoặc `Htmlable`; chuỗi thô bị bỏ) theo priority; chỉ nối thêm; lỗi một listener bị bỏ qua. Danh mục: [extension-point-catalog §4.1](../04-extension/extension-point-catalog.md) |
| Thay khối | Override view trong theme con của cửa hàng (fallback `vani-base` → module). Không có cơ chế viết lại HTML lúc render |
| Không logic trong view | Blade chỉ render DTO; không query, không tính giá/tồn/khuyến mãi (R10) |

Luồng một trang PDP:

```text
GET /san-pham/{slug}
  → web → controller Storefront
  → ProductViews (CatalogReader + PriceResolver + AvailabilityReader) → DTO (gồm brand: tên, slug, logo)
  → Blade (theme đang hoạt động → vani-base) + slot UI (Hook::slot) + JSON bridge cho Alpine
  → HTML (cache CDN; giỏ/giá thành viên tải sau qua /api/storefront/v1)

GET /thuong-hieu/{slug}
  → ProductViews::search(brand = slug, lọc tiếp danh mục/màu/size/giá) → trang brand (logo, mô tả, lưới sản phẩm)
```

## 4. Block (page builder)

> **Implemented** (2026-10-02) cho trang chủ: `Storefront\Contracts\StorefrontBlock` (`type`, `label`, `fields(): list<FieldDefinition>`, `resolve(config, locale)`, `view`), cấu hình lưu ở `core.storefront.home_blocks`, Admin → Giao diện → Trang chủ. Interface dưới đây là thiết kế ban đầu.

```php
interface StorefrontBlock
{
    public function type(): string;               // 'hero', 'product_grid', 'collection_carousel', 'rich_text'
    public function schema(): array;              // JSON Schema cho Admin editor
    public function resolve(array $config): BlockViewData;  // lấy dữ liệu qua Query
    public function view(): string;               // Blade view (theme có thể override)
}
```

Core có block `brand_grid` (lưới logo brand dẫn tới trang brand). Plugin thêm block qua tag `vani.content.blocks` (ví dụ lookbook, recommendation).

## 3a. URL theo ngôn ngữ

> **Implemented** (Core 0.3.59–0.3.62, [ADR-038](../19-adr/ADR-038-localized-storefront-urls.md)).

- Đơn lưu `locale` lúc đặt (trang web theo URL, Storefront API theo header); thông báo giao dịch chọn mẫu theo ngôn ngữ của đơn, thiếu mẫu thì dùng ngôn ngữ mặc định. Khách có `preferred_locale` (cập nhật khi đặt hàng/sửa hồ sơ).

- Tiếng Việt giữ URL hiện tại; tiếng Anh dưới `/en` với đường dẫn tiếng Anh (`/en/products/{slug}`, `/en/category/{slug}`, `/en/brands`, `/en/cart`, `/en/account/…`). Bảng đường dẫn: `config/vanishop.php` → `locale.paths`. Tên route: `storefront.*` (mặc định), `storefront.en.*`.
- Ngôn ngữ của trang web lấy **từ URL** (`vani.storefront-context:<locale>` trên nhóm route), bỏ qua header `X-Vani-Locale` (Storefront API vẫn dùng header). Trang có cache chỉ `Vary: Accept-Encoding`.
- **Theme và plugin sinh URL bằng `vani_route('storefront.…', $params)`** (ngôn ngữ hiện hành; route chưa có bản ngôn ngữ → URL mặc định); class trong `modules/` dùng `Shared\Support\LocalizedUrls::route()`. Gọi `route('storefront.…')` trực tiếp sẽ ra URL tiếng Việt trên trang tiếng Anh.
- Plugin: `storefrontPages($file, prefix: ['vi' => 'tin-tuc', 'en' => 'news'])`; `vani.cms`: `/en/pages/{slug}`, `/en/news`.
- `/_vani/phien?locale=en` (trang có cache gọi bằng JS) trả link tài khoản theo ngôn ngữ của trang.
- **Gọi API từ JS của theme** (ADR-044, 0.4.14): dữ liệu (giá thành viên `GET /me/prices`, lượt xem `POST /products/views`, wishlist `x/vani-wishlist/*`, gợi ý tìm kiếm, sản phẩm) lấy từ Storefront API bằng **phiên web cùng nguồn** (`Shared\Http\Middleware\FirstPartySession`: `Sec-Fetch-Site: same-origin` hoặc `Origin` trùng host, không `Authorization`; request ghi kèm CSRF) — không có endpoint JSON riêng cho theme. Ngoại lệ: `/_vani/phien` (CSRF, flash, lỗi form của Blade khi trang cache CDN).
- **Giỏ mini offcanvas** (0.3.93): JS của theme gửi chính form của storefront với `Accept: application/json` để nhận partial giỏ mini (cùng FormRequest với API, ADR-044) — form không JS vẫn chạy; POST gửi `X-CSRF-TOKEN` lấy từ trạng thái phiên (`csrf_token`, trang cache CDN chờ `/_vani/phien`). `window.vani` (`cart`, `auth`, `search`, `request`) cho theme con/plugin; URL theo ngôn ngữ trang ở `script[data-vani-urls]`. Giỏ mini là `<dialog>` (showModal: khoá nền, giữ focus, Esc), nội dung do partial `theme::partials.mini-cart` render ở server. Mã giảm giá nhập ở giỏ giữ trong phiên (checkout không trạng thái — ADR-023: đặt hàng vẫn tính lại). Trang giỏ đầy đủ (0.3.94) dùng cùng API: JSON kèm `page_html` (partial `theme::partials.cart-page`) khi gửi `X-Vani-Cart-View: page`. Chi tiết: [CHANGELOG 0.3.93–0.3.94](../04-extension/CHANGELOG-extension.md).
- **Font** (0.3.92): khai báo trong `vite.config.js` bằng provider `local()` của laravel-vite-plugin (file gốc ở `public/fonts/*`, build băm tên vào `public/build`, sinh `@font-face` + preload). Storefront `vani-base`: Anivele (400, 600) qua `@fonts('anivele')`, chỉ nạp khi token `font-sans` còn chứa "Anivele" — cửa hàng đổi font bằng token. Admin: Inter (SIL OFL 1.1, 400–700, có tiếng Việt) qua provider `bunny()` — tải lúc build, phục vụ từ `public/build` — và `@fonts('inter')` trong `resources/views/admin.blade.php`. Token theme in thô trong `<style>` (giá trị nhập ở Admin đã lọc `;{}<>`).
- **Carousel & ảnh phóng to** (0.3.92): Swiper 14 và PhotoSwipe 5 (MIT) cài qua npm (`package.json`), Vite tách chunk riêng; `app.js` chỉ tải (import động) khi trang có `.vani-carousel` / `[data-vani-lightbox]` / gallery sản phẩm. Theme con, plugin gọi `window.vaniLoadVendor('swiper' | 'photoswipe')`. Không JS: carousel là dải cuộn ngang scroll-snap, ảnh phóng to là liên kết (ADR-025 quyết định 4). Cú pháp HTML: [CHANGELOG 0.3.92](../04-extension/CHANGELOG-extension.md). Nâng cấp thư viện: `npm update swiper photoswipe` rồi build.
- **Chuỗi giao diện** (0.3.63): view theme/plugin bọc chữ hiển thị bằng `__('câu tiếng Việt')` (tham số `:name`), bản dịch ở `custom/theme/<theme>/lang/<locale>.json` (theme cha nạp trước, theme con ghi đè) và `custom/plugin/<P>/Resources/lang/<locale>.json` (plugin nạp bằng `loadJsonTranslationsFrom`). Tiếng Việt không cần file. Chuỗi cho `app.js` qua khối JSON `data-vani-i18n` trong layout (khoá id ổn định). Arch test `StorefrontI18nTest`: không còn chữ tiếng Việt ngoài `__()`, mọi khoá có bản `en`. ⚠️ Sẽ thay bằng khoá có namespace (`theme::nhóm.khoá`, file `lang/{vi,en}/*.php`, bỏ JSON) theo [ADR-049](../19-adr/ADR-049-keyed-translations.md) (Accepted, chưa làm) — [kế hoạch](../20-roadmap/i18n-keyed-translations.md).
- **SEO** (0.3.61): layout sinh canonical, `hreflang` (vi, en, x-default — khi có ≥ 2 bản đã dịch), `noindex,follow` + canonical về bản tiếng Việt cho trang chưa dịch ("đã dịch" = bản dịch có tên), `noindex` cho trang giao dịch (route không qua page cache), và nút chuyển ngôn ngữ trỏ URL tương ứng. Trang khai báo qua contract `PageSeo::alternates($urls, $translated)` (sản phẩm, danh mục, `vani.cms`); không khai báo → suy ra từ route hiện tại. `sitemap.xml` có URL mọi bản đã dịch kèm `xhtml:link`.
- **Slug theo ngôn ngữ** (0.3.60): bản dịch `en` của sản phẩm/danh mục/bộ sưu tập có slug riêng tuỳ chọn (Admin: ô "Slug riêng", nút "Tạo từ tên"); trống = slug gốc. `/en/products/{slug}` tra slug `en` trước rồi slug gốc; khớp slug gốc khi đã có slug `en` → 301. Trang tiếng Việt chỉ nhận slug gốc. Thương hiệu một slug cho mọi ngôn ngữ. Dữ liệu catalog trả `slug` theo ngôn ngữ được yêu cầu, chi tiết sản phẩm/danh mục kèm `slugs` (mọi ngôn ngữ).

## 4a. Link có kiểu và menu

> **Implemented** (Core 0.3.58, [ADR-040](../19-adr/ADR-040-typed-links-menus.md)).

- **Link có kiểu** `LinkTarget {type, value, label?}` phân giải lúc render bằng `LinkResolver` (URL + nhãn theo ngôn ngữ; đối tượng đã ẩn/xoá → bỏ). Loại Core: `category`, `collection` (→ `/tim-kiem?collection=`), `brand`, `product`, `route` (trang công khai cho phép), `url` (`/…`, `http(s)://`, `mailto:`, `tel:`). Plugin thêm loại qua `LinkType` (vd. `vani.cms`: `page`, `article`).
- **Vị trí menu** do `theme.json` khai báo (`menus`); `vani-base`: `header` 3 cấp (cấp 2–3 mở khi rê chuột/focus), `mobile` 2 cấp (mặc định dùng menu `header`), `footer_1..4` 1 cấp. Vị trí chưa xuất bản → link mặc định của theme.
- **Admin → Giao diện → Menu** (quyền `storefront.manage`): cây theo vị trí, sắp bằng nút ↑ ↓ ← →; mỗi mục có link, nhãn theo ngôn ngữ, huy hiệu Mới/Sale, ảnh (cấp 1), bật/tắt, khung giờ hiển thị. Lưu nháp → Xuất bản; bỏ nháp; khôi phục 10 bản xuất bản trước. Lưu ở `core.storefront.menu.<vị trí>`.
- Theme render: `$menus->items('header', app()->getLocale())` (layout) → `list<{label, url|null, badge, image_url, children}>` hoặc `null` (chưa xuất bản). Partial `theme::partials.menu` (ngang, nhiều cấp), `theme::partials.menu-list` (dọc).
- Phân giải theo lô (một truy vấn mỗi loại), không cache qua request; trang có cache CDN đổi theo `s-maxage`.

## 4c. Breadcrumb

> **Implemented** (Core 0.3.66, [reference-parity §3.16-6](../20-roadmap/reference-parity-refactor.md)).

- Partial `theme::partials.breadcrumb` nhận `$crumbs = list<{label, url?, link?}>` (`link` = `LinkTarget` hoặc `{type, value}`; link không còn → chữ thường). Từ 0.5.20 Core dựng sẵn qua view composer: `$breadcrumb = {items: list<{label, url|null, current}>, json_ld}` (`Storefront\View\Breadcrumbs` — thêm "Trang chủ" ở đầu, mục cuối là trang hiện tại không link, JSON-LD `BreadcrumbList` mục cuối không có `item`); partial chỉ hiển thị. Trang danh mục/thương hiệu nhận `$crumbs` dựng sẵn từ controller.
- `vani-base` dùng ở PDP (danh mục chính → sản phẩm), danh mục (`$category['breadcrumb']` — đường từ gốc, `CatalogReader::category`), thương hiệu (Thương hiệu → tên).

## 4c-1. Dữ liệu hiển thị trang sản phẩm

> **Implemented** (Core 0.5.20).

- `theme::pages.product` nhận `$product` (chi tiết sản phẩm) và `$page` do Core dựng (`Storefront\View\ProductPage`) — theme chỉ in ra, không gọi lớp Core hay tự tính:
  - `meta`: `title` (`meta_title` → tên), `description` (`meta_description` → đoạn đầu mô tả ≤ 160 ký tự, bỏ thẻ → `null`), `json_ld` (list chuỗi JSON in trong `<script type="application/ld+json">`; `Product` dùng ảnh gallery đầu).
  - `crumbs`: đường danh mục chính → tên sản phẩm, truyền cho partial breadcrumb.
  - `gallery`: `aspect` (tỉ lệ khung ảnh sản phẩm), `label`, `slides[]` (`url`, `srcset`, `sizes`, `alt`, `width`/`height`, `main` — ảnh LCP, `zoom {url, width, height}` = bản lớn nhất trong srcset, không có thì ảnh chính 800px, `zoom_label`), `thumbs[]` (chỉ khi > 1 ảnh), `navigation`. Không có ảnh gallery → ảnh đại diện.
  - `selector`: kết quả `VariantSelector` kèm `choose_message`, `rest_label`, trục đầu có `input_name`, mỗi giá trị `input_value`/`disabled`/`checked`/`images`/`price_html`/`low_stock`, SKU trong nhóm có `price_html`.
  - `variant_css`: CSS `:has()` ẩn/hiện nhóm SKU theo trục đầu (mã giá trị đã thoát, in thô trong `<style>`); `can_buy`.
- Plugin đổi được cả `product` lẫn `page` qua hook `vani.storefront.view.pages.product`.

## 4c-2. Dữ liệu dựng sẵn cho layout và partial

> **Implemented** (Core 0.5.21). Template `vani-base` không gọi lớp Core, không đọc `request()`/`session()` (test `ThemeViewDataTest`); dữ liệu đến từ view composer của module Storefront (`Storefront\View\Composers`):

| View | Biến | Nội dung |
|---|---|---|
| `layouts.app` (và partial nó include) | `$shell` | `page_cache`, `session_url`, `announcement {text, url}`, `menus {header, mobile (rơi về header), footer (vị trí đã xuất bản)}`, `search_query`, `account {url, label}` theo khách, `mobile_topics_url`, `contact {phone, phone_href, email, address, socials[]}` hoặc `null`, `copyright` (trống → © năm + tên cửa hàng), `payment_methods[]`, `cart_change` (flash giỏ của lần gửi trước), `urls` (cho `window.vani`). Vẫn có `$menus`, `$seo`, `$themeSettings`, `$popularSearches`, `$customerState`. |
| `partials.listing` | `$listingView` | `context`, `action`, `filters[]` (lựa chọn có `url` bật/tắt + `active`; khoảng giá có `hidden_fields`, `min_value`/`max_value`, `min_placeholder`/`max_placeholder`, `clear_url`; bộ lọc thương hiệu ẩn ở trang thương hiệu), `sort {hidden_fields, current, by_price}`, `pages[] {number, url, current}` (rỗng khi một trang). |
| `partials.image` | `$sizes` | Từ `$layout` khi có `srcset` (`ResponsiveImage::sizes`). |
| `partials.product-card`, `partials.mini-cart` | `$productFrame` | `aspect` (tỉ lệ ảnh sản phẩm), `card {width, height}`. |
| `partials.notification` | `$notifications` | `[{id, type, role, message, dismissible}]` từ `messages` hoặc flash `status`/`error` (trừ trang cache CDN). |
| `partials.account-menu` | `$accountLinks`, `$accountMenu` | Trang tài khoản Core `{url, label, active}`; trang của plugin. |

- Ngày giờ đơn đã định dạng theo giờ cửa hàng: `placed_at_label` (danh sách đơn, chi tiết đơn tài khoản), `returnForm.deadline_label`.

## 4d. Ảnh responsive

> **Implemented** (Core 0.3.67, [reference-parity §3.16-5](../20-roadmap/reference-parity-refactor.md)).

- Thẻ sản phẩm (danh sách, Storefront API, khối, sản phẩm liên kết) có `image_srcset`: các bản thu nhỏ Core tạo (bề rộng ở Admin → Thư viện ảnh → Kích thước ảnh, định dạng theo `ImageFormat`), tính từ kích thước đã lưu trong DB — không đọc file lúc render. `null` khi ảnh không có bản thu nhỏ. Từ 0.3.87 ảnh sản phẩm **cắt theo tỉ lệ cấu hình (mặc định 2:3)** và `srcset` không kèm ảnh gốc khác tỉ lệ; theme dựng khung bằng `vani_product_ratio()`. PDP: `images[].srcset`, `images[].thumb_url` (160px), ảnh chính `fetchpriority="high"`. Ảnh khác (logo, banner, bài viết) giữ tỉ lệ gốc; khối trang chủ chỉ chọn ảnh Thư viện ảnh qua trường `media` (srcset; 0.3.90 bỏ ô URL ảnh — cấu hình cũ tự chuyển sang id ảnh khi URL thuộc Thư viện ảnh); logo thương hiệu cũng chọn từ Thư viện ảnh. Plugin cần kích thước riêng khai báo `ImageSizeProvider` rồi dùng `vani_image($id, 'tên')`.
- `Storefront\View\ResponsiveImage::sizes(vùng chứa px|null, cột desktop, cột mobile, breakpoint = 768)` sinh `sizes`; partial `theme::partials.image` (`src`, `srcset`, `layout = [vùng chứa, cột, cột mobile]`, `width`/`height`). `vani-base`: lưới 4/2 trong khung 1152px (trang chủ, khối sản phẩm, sản phẩm liên kết), 3/2 cạnh cột lọc (≈ 896px).
- Khối "Banner chính" vẫn nhận ảnh theo URL nên chưa có `srcset` (cần kiểu trường chọn ảnh từ thư viện cho `FieldDefinition`).

## 4b. Đầu & chân trang theo schema theme

> **Implemented** (Core 0.3.65, [reference-parity §3.16-3](../20-roadmap/reference-parity-refactor.md)).

- Theme khai báo trường trong `theme.json` → `settings`: `{"<nhóm>": {"label": "…", "fields": [{"key", "label", "type", "required?", "max?", "help?", "options?", "translatable?"}]}}`. `type` là kiểu của `FieldDefinition` (`string`, `text`, `int`, `bool`, `select`, `date`) hoặc `url` (chỉ `http(s)`), `email`. `translatable: true` (chỉ `string`/`text`) → một ô mỗi ngôn ngữ, bản trống rơi về ngôn ngữ mặc định. Trường khai báo sai bị bỏ (ghi log). Core không cứng trường nào.
- Theme con không khai báo `settings` → dùng schema **và giá trị** của theme cha gần nhất có khai báo; tự khai báo → thay hẳn. Giá trị lưu ở `core.storefront.theme_settings.<theme khai báo>`.
- **Admin → Giao diện → Đầu & chân trang** (quyền `storefront.manage`): form sinh từ schema, lỗi theo `settings.<nhóm>.<trường>[.<ngôn ngữ>]`, ghi audit `storefront.theme_settings_updated`.
- Layout nhận `$themeSettings[<nhóm>][<trường>]` theo ngôn ngữ trang (mọi trường đều có khoá: chưa nhập = `null`, `bool` = `false`). `vani-base`: `header` (thanh thông báo + link), `footer` (điện thoại, email, địa chỉ, Facebook/Instagram/TikTok/YouTube/Zalo, phương thức thanh toán — mỗi dòng một nhãn, dòng bản quyền — trống = `© năm + tên cửa hàng`).

## 5. Hiệu năng và SEO

- **Trang công khai và cache CDN (Implemented, 0.3.34; 0.3.68: CDN là tuỳ chọn nâng cao):** trang chủ, danh mục, thương hiệu, tìm kiếm, sản phẩm và trang CMS (`/trang/…`, `/tin-tuc/…`) dùng middleware `vani.page-cache` (`PublicPageCache`). Hai chế độ, **hiển thị như nhau**:
  - **Mặc định — chưa có CDN** (`VANI_PAGE_CACHE=false`): trang chạy **có phiên** như mọi trang (middleware tự chạy `StartSession`, `ShareErrorsFromSession`, nhận diện khách), `Cache-Control: private, no-store`. Layout render sẵn phần riêng của khách: nhãn Đăng nhập/Tài khoản, số món, thông báo flash, lỗi form, giá cho khách đã đăng nhập khi ẩn giá; nhúng `script[data-vani-session-state]` (cùng dữ liệu `/_vani/phien` trừ flash — `CustomerSessionState`) để JS dựng giỏ mini, giá thành viên. Không gọi `/_vani/phien`.
  - **Có CDN** (`VANI_PAGE_CACHE=true`, tuỳ chọn khi deploy — [production §7](../18-operations/production.md)): trang chạy **không phiên** (bỏ CSRF, nhận diện khách — `Shared\Http\SessionlessRoutes`), HTML giống nhau với mọi khách, không `Set-Cookie`, không ghi phiên mỗi lượt xem. Response: `Cache-Control: public, max-age=0, s-maxage=300, stale-while-revalidate=600`, `Vary: Accept-Encoding` (`VANI_PAGE_CACHE_TTL`). Không cache: link có chữ ký (xem trước CMS), phản hồi khác 200. **Phần riêng của khách** lấy bằng JS từ `GET /_vani/phien` (`private, no-store`, flash đọc xong là hết) và điền vào `[data-vani-account]`, `[data-vani-cart-count]`, giỏ mini `[data-vani-mini-cart]` (`cart.lines[]` 3 dòng gần nhất + `cart.subtotal`, không tính khuyến mãi; dựng bằng DOM, không chèn HTML), `[data-vani-flash]`, `[data-vani-error-for]`. Không JS: trang vẫn mua được, header hiện như khách vãng lai.
  - SEO (canonical, hreflang, `noindex`) theo cờ "trang công khai" (`PublicPageCache::PUBLIC_PAGE`), không phụ thuộc chế độ cache.
  - **Dữ liệu phiên cho JS** (0.3.76): sau khi có dữ liệu phiên (nhúng sẵn hoặc `/_vani/phien`), theme đặt `window.vaniSession` và phát `vani:session` (`{signed_in, account, cart, prices_url, csrf_token, …}`). Plugin dùng `csrf_token` cho POST (header `X-CSRF-TOKEN`) vì trang cache CDN không có token trong HTML; theme tự viết phải giữ hành vi này.
  - **Sự kiện giỏ** (0.3.43): sau thêm/sửa/xoá dòng giỏ, trang kế tiếp phát `document` event `vani:cart-updated` với `detail = {action: add|remove, variant_id, sku, name, quantity, unit_amount, currency}` (một lần; trang không cache đọc từ thẻ `script[data-vani-cart-change]`, trang có cache từ `cart_change` của `/_vani/phien`). Plugin đo quảng cáo/khuyến nghị nghe sự kiện này; theme tự viết phải giữ hành vi này.
  - Form "thêm vào giỏ" trên trang sản phẩm không có token CSRF (HTML như nhau ở cả hai chế độ); `POST` giỏ (`/gio-hang`, `/en/cart`… mọi ngôn ngữ) được miễn CSRF — an toàn vì cookie phiên `SameSite=Lax` (form chéo trang không mang phiên theo).
  - **Quy tắc cho theme/plugin:** để trang chạy đúng khi bật CDN, view và slot trên trang công khai không được đọc phiên, `old()`, `@csrf`, khách đăng nhập hay dữ liệu riêng của khách — trừ khi chỉ dùng để render sẵn khi chế độ CDN tắt (`$customerState !== null`, `$errors` của phiên), với cùng kết quả hiển thị như phần JS điền ở chế độ CDN. Cá nhân hoá (giá thành viên ở Phase 8, gợi ý theo khách) tải qua API sau khi trang hiện. Plugin bật cache cho trang của mình bằng `storefrontPages($file, prefix: …, cacheable: true)`.
  - Livewire chỉ dùng cho Pulse, tắt tự chèn script (`config/livewire.php`), vì script đó mang token CSRF theo phiên.
  - Có CDN: cập nhật nội dung (giá, tồn, sản phẩm) hiện ra sau tối đa `s-maxage` (mặc định 5 phút); purge chủ động theo event: chưa có. Không CDN: thấy ngay.
- **Bộ lọc và gợi ý (Implemented, 0.3.71):** Admin → Catalog → Bộ lọc (bật/tắt, thứ tự, kiểu danh sách/ô màu) → `$listing['filters']` (cũng `meta.filters` của API); theme render chung, link thường (`ListingUrl::toggle($request, $filter['param'], $value)`), facet không có giá trị thì ẩn. Ô tìm kiếm gợi ý sản phẩm/danh mục/thương hiệu qua `GET /api/storefront/v1/search/suggestions` (JS, gửi `X-Vani-Locale`).
- **Cấu hình danh sách (Implemented, 0.3.70):** `storefront.per_page` (12–60, mặc định 24), `storefront.popular_searches` (`$popularSearches` ở layout — `datalist` của ô tìm kiếm — và trang tìm kiếm khi chưa gõ), `storefront.placeholder_image` (ID ảnh; thẻ sản phẩm thiếu ảnh dùng ảnh này).
- **Cấu hình vận hành (Implemented, 0.3.49 — [reference-parity §3.2](../20-roadmap/reference-parity-refactor.md)):** Admin → Cấu hình, namespace `core`.
  - `storefront.maintenance` (bool, mặc định tắt) + `storefront.maintenance_message`: middleware `vani.maintenance` (gắn vào route storefront web, trang và Storefront API của plugin, `/api/storefront/v1`) trả **503** + `Retry-After: 600` + `private, no-store`; web render `theme::pages.maintenance` (độc lập với layout, không đọc phiên), API trả lỗi `storefront.maintenance`. **Không** chặn: IPN/webhook (`/api/payments/*`, `/api/integrations/*`), Integration API, `/health`, `/up`, Admin, `robots.txt`, `sitemap.xml`.
  - Nhân viên xem trước: Admin → Giao diện → "Xem trang" (khi đang bảo trì đi qua `admin.storefront.maintenance.preview`, quyền `storefront.manage`, có audit) phát cookie `vani_maintenance_bypass` (HMAC theo `APP_KEY`, path `/`, HttpOnly, 12 giờ). Cookie phiên Admin bị giới hạn path Admin (ADR-020) nên storefront không dùng nó được. Phản hồi khi có cookie là `private, no-store` — không lọt bản "đang mở" vào CDN.
  - `storefront.guest_checkout` (bool, mặc định bật): tắt thì `Checkout::placeOrder` từ chối khách chưa đăng nhập bằng `checkout.sign_in_required` (403) cho mọi kênh; trang `/thanh-toan` chuyển sang đăng nhập rồi quay lại. Xem giá, thêm giỏ, báo giá vẫn không cần đăng nhập.
  - `storefront.price_visibility` (`public` | `members`, 0.3.50): một chỗ quyết định — `Storefront\Application\PriceVisibility` (ẩn khi `members` và actor của request không phải khách). `ProductViews` (web, Storefront API, `ProductCards`, khối trang chủ, sản phẩm liên kết) trả `price: null` + `price_hidden: true`; JSON-LD không có `offers` vì giá null. Trang cache được chạy không phiên nên HTML **luôn** là bản ẩn giá ("Đăng nhập để xem giá", `[data-vani-price-for]`); khách đã đăng nhập nhận giá sau qua Storefront API `GET /me/prices?style_ids[]=` gọi bằng phiên cùng nguồn (`private, no-store`, ADR-044). Giỏ và checkout (web + API) qua middleware `vani.prices-visible`: API 403 `storefront.sign_in_to_view_prices`, web chuyển sang đăng nhập rồi quay lại trang sản phẩm. Sitemap không chứa giá; chưa có feed sản phẩm.
- Cache ứng dụng: `style:{id}:v{version}`, `brand:{id}:v{version}`; invalidate theo event (`ProductUpdated`, `PriceChanged`, `AvailabilityChanged`).
- URL: một website ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md)): `/san-pham/{slug}`, `/danh-muc/{slug}`, `/thuong-hieu/{slug}`…
- SEO: một canonical cho mỗi sản phẩm (không lặp theo brand), một sitemap (chia file khi lớn, gồm trang brand), schema.org `Product`/`Offer` có `brand`, schema.org `Product`/`Offer`, hreflang khi đa ngôn ngữ.
- **SEO & chia sẻ link (0.5.25, stabilization S3 — `SeoShareTest`):**
  - Tiêu đề/mô tả: `meta_title`/`meta_description` nhập ở Admin (sản phẩm, danh mục, thương hiệu) → tên / đoạn đầu mô tả (bỏ HTML, ≤ 160 ký tự) → mô tả cửa hàng (`core`/`storefront.meta_description`). `Storefront\View\PageMeta` dựng sẵn `$meta` cho trang danh mục/thương hiệu (`$page['meta']` ở trang sản phẩm). Danh mục: Catalog nay trả `meta_title`, `meta_description` (trước 0.5.25 nhập ở Admin nhưng storefront bỏ qua).
  - Thẻ chia sẻ (Open Graph + `twitter:card`) do layout in cho trang công khai được index: `og:title` = tiêu đề trang, `og:description`, `og:url` = canonical, `og:image` = ảnh của trang (ảnh đầu gallery, ảnh danh mục, logo thương hiệu, ảnh bìa bài viết CMS/lookbook) → ảnh chia sẻ của cửa hàng (`core`/`storefront.share_image`, Admin → Cấu hình), `og:type` (`product`, `article`, mặc định `website`), `og:locale` theo `vanishop.locale.og_locales`. Trang/plugin đặt bằng section `title`, `description`, `og_image`, `og_type` — không tự in thẻ `og:*` (bị trùng). URL ảnh luôn tuyệt đối (cả `image` trong JSON-LD `Product`).
  - Trang chủ: JSON-LD `Organization` (tên, logo = ảnh chia sẻ, SĐT + mạng xã hội ở chân trang) + `WebSite` có `SearchAction` (ô tìm kiếm trên Google).
  - Canonical/hreflang chỉ giữ `page` (> 1): bộ lọc, sắp xếp, `utm_*` không tạo trang trùng; trang 2 trở đi tự canonical. Nút đổi ngôn ngữ vẫn giữ bộ lọc. Trang tìm kiếm `noindex` (link trong trang vẫn được đi theo).
  - Đổi slug ở Admin (sản phẩm, danh mục, thương hiệu): slug cũ lưu ở `slug_redirects` (Catalog), link cũ **301** sang slug mới, giữ query (`?sku=`, `?page=`); bản ghi khác lấy lại slug cũ thì slug thuộc bản ghi đó. Slug riêng của bản dịch cũng được ghi (0.5.34, `slug_redirects.locale`). Contract `CatalogReader::slugRedirect()`.
  - Sản phẩm ngừng bán (lưu trữ, kể cả mở bằng slug cũ) → **410** `theme::pages.product-gone` (`$name`, `$categoryUrl`, `$suggestedProducts` cùng danh mục), `noindex` (0.5.34, `CatalogReader::discontinuedProduct()`). Slug chưa từng có hoặc bản ghi ẩn → 404.
- Mục tiêu: LCP mobile < 2,5s, CLS < 0,1.
- **Form checkout (0.5.24, stabilization S2 — `Storefront\View\CheckoutForm` dựng `$form` cho `theme::pages.checkout`):**
  - Giá trị từng ô: lần gửi trước (`old`) nếu có; chưa gửi và khách đã đăng nhập → điền sẵn họ tên/SĐT/email từ hồ sơ và người nhận + địa chỉ mặc định trong sổ địa chỉ (tỉnh/phường chọn sẵn, danh sách phường tải sẵn). Báo giá dựng theo đúng giá trị đang hiện (cả địa chỉ điền sẵn). Có từ 2 địa chỉ: ô "Địa chỉ đã lưu" (chỉ hiện khi có JS) điền lại các ô.
  - Tóm tắt lỗi đầu trang (`[data-checkout-errors]`, `role="alert"`): lỗi chung (`business` — tổng đã đổi, hết hàng…) và lỗi từng ô kèm link tới ô; ô lỗi có `aria-invalid` + `aria-describedby`. Hiện cả khi tắt JS (trước 0.5.24 lỗi chung chỉ hiện nhờ JS). Có JS: focus vào tóm tắt.
  - Điện thoại: thanh đáy cố định (`[data-checkout-bar]`) có tổng + "Đặt hàng"; nút trong khung tóm tắt chỉ hiện từ `md`. Thanh nằm cuối form để Enter trong ô nhập vẫn bấm "Áp dụng" (tính lại), không đặt hàng.
  - Đổi phường/xã, phương thức giao hoặc thanh toán (có JS) → POST `action=quote` như nút "Áp dụng", thay `[data-checkout-summary]`, `[data-checkout-shipping]`, `[data-checkout-payment]`, `[data-checkout-bar]` và `expected_total` bằng bản server vừa render, rồi phát `vani:content-updated` — phí giao theo địa chỉ của plugin hãng vận chuyển luôn khớp tổng khách thấy. Theme con giữ các thuộc tính `data-checkout-*` này.
  - Phường/xã: ô `[data-ward-filter]` lọc danh sách của tỉnh đang chọn, gõ không dấu (`đ` → `d`), hiện khi danh sách ≥ 15 mục; còn đúng một kết quả thì tự chọn. Dùng chung cho sổ địa chỉ.
  - Test: `CheckoutFormTest` (điền sẵn, sổ địa chỉ, lỗi giữ giá trị khách gõ, lỗi chung không cần JS), `tests/Browser/CheckoutMobileTest` (đặt COD qua thanh đáy, tìm phường không dấu, tính lại khi đổi phường, focus tóm tắt lỗi, chọn địa chỉ đã lưu).
- **Ngân sách mobile (0.5.23, `tests/Browser/StorefrontMobileBudgetTest`):** ở màn 375px, các trang chính (trang chủ, danh mục, sản phẩm, tìm kiếm, thương hiệu, giỏ, tra cứu đơn, đăng nhập) phải: không cuộn ngang (danh mục và sản phẩm kiểm thêm ở 320px); mọi `<img>` có `width`/`height`; JS tải lúc mở trang ≤ 64 KB, CSS ≤ 96 KB (chưa nén); trang danh sách và trang sản phẩm không có ảnh `lazy` trong màn hình đầu; không phần tử nào trong màn hình đầu bị ẩn chờ hiệu ứng. Số đo 2026-11-18: JS 34 KB (trang sản phẩm 48 KB, thêm PhotoSwipe), CSS 74–79 KB. Theme con hoặc plugin làm vượt ngân sách → test đỏ.
  - Lưới sản phẩm: `theme::partials.product-grid` nhận `$eager` (số ảnh đầu tải ngay, `fetchpriority="high"`); trang danh sách dùng 2 (hàng đầu trên điện thoại).
  - Hiệu ứng hiện dần (`data-vani-motion="rise|fade"`, nhóm `data-vani-motion-stagger` > `data-vani-motion-item`): CSS transition + `IntersectionObserver`, không tải thư viện Motion (trước 0.5.23 tải ~60 KB gzip ở mọi trang có lưới sản phẩm, và ẩn cả nội dung màn hình đầu — kể cả ảnh hero — tới khi tải xong). Phần tử trong màn hình đầu lúc tải trang hiện ngay; phần tử phía dưới hiện khi cuộn tới, các mục so le cùng hàng lệch 45 ms. Theme con/plugin vẫn gọi được `window.vaniLoadVendor('motion')` khi cần animation riêng.

## 6. Kiểm thử

- Arch test: namespace controller storefront không dùng `Persistence`/`Domain` (chỉ dùng Application).
- Test snapshot HTML tối thiểu cho các trang chính; E2E luồng mua ([testing](../17-testing/testing.md)).
- Test theme fallback: theme con thiếu view thì dùng view của `vani-base`.
- Test trang brand: chỉ hiện sản phẩm của brand; brand ẩn → 404.
- Test trang chính với JS tắt: nội dung, giá, nút thêm giỏ (form POST dự phòng) vẫn hiển thị.
- Test slot: listener plugin ném lỗi → trang vẫn render, phần tử khác vẫn có; plugin tắt → không hiện.
