# Storefront

> Trạng thái: native storefront **Partially Implemented** (slice 12b, 2026-10-02); tầng ghép + Storefront API: Implemented.
>
> | Phần | Trạng thái |
> |---|---|
> | Theme `vani-base` (`custom/theme/vani-base`), theme đang hoạt động `core.theme` + theme con (`parent`), token `theme.tokens` → CSS variables | Implemented |
> | Trang SSR: `/`, `/danh-muc/{slug}`, `/thuong-hieu`, `/thuong-hieu/{slug}`, `/tim-kiem`, `/san-pham/{slug}` (JSON-LD `Product`), `/gio-hang`, `/thanh-toan`, `/don-hang/{id}` — mua được hoàn toàn bằng form khi JS tắt | Implemented |
> | Slot storefront (`modules/Storefront/hooks.php`) + `<x-vani::hook-slot>` | Implemented (trừ `vani.storefront.account.menu` — chưa có trang tài khoản native) |
> | Đảo tương tác | JS thuần, không thư viện (đổi ảnh theo màu, tự gửi form số lượng). Alpine chờ duyệt dependency |
> | Tài khoản `/tai-khoan` (đăng nhập OTP, tổng quan, đơn hàng + huỷ, địa chỉ; giỏ vãng lai gộp khi đăng nhập), tra cứu đơn `/tra-cuu-don`, `robots.txt`, `sitemap.xml` | Implemented (2026-10-02) |
> | Đổi/trả trên native (2026-10-08): partial `order-returns` trên `/tai-khoan/don-hang/{id}` và `/don-hang/{id}` (token đơn trong phiên) — danh sách yêu cầu + huỷ khi chờ duyệt, form chọn số lượng, trả hoàn tiền hoặc đổi size/màu cùng mẫu (size hết hàng bị khoá), lý do, ghi chú; `POST /don-hang/{id}/doi-tra`, `POST /don-hang/{id}/doi-tra/{return}/huy`; không cần JS. Đổi mẫu khác: qua CSKH | Implemented |
> | Page builder cho trang khác ngoài trang chủ; hreflang; header cache CDN (cần tách phiên khỏi trang công khai); giỏ/giá thành viên tải qua API | Designed |
> | Địa chỉ checkout | Có `vani.provinces-vn`: chọn tỉnh/phường (JS tải phường theo tỉnh; không JS: nút tải lại). Không có danh mục: nhập tự do | Quyết định: [ADR-009](../19-adr/ADR-009-storefront-architecture.md), [ADR-021](../19-adr/ADR-021-storefront-composition-module.md), [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md), [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md) (một website, một giao diện).

## 1. Nguyên tắc

- Storefront **độc lập với business domain**. Business logic **không** nằm trong Blade, Vue/React component, theme hay CSS (rule R10).
- Storefront chỉ dùng **Storefront Application layer**: các query/command giống hệt những gì Storefront API công bố.

```text
Commerce Core (modules/*)
      ↓
Storefront Application (Queries/Commands + DTO) ──► /api/storefront/v1 (HTTP, JSON)  ──► Headless / Mobile / Zalo Mini App
      ↓ (gọi in-process, cùng DTO)
Native Storefront (Blade SSR + Alpine, theme custom/theme/*)
```

| Client | Cách dùng |
|---|---|
| **Tầng ghép** | Module `modules/Storefront` (**Implemented** cho API, [ADR-021](../19-adr/ADR-021-storefront-composition-module.md)): `ProductViews` gọi `CatalogReader` + `PriceResolver` + `AvailabilityReader` (chỉ công bố còn/hết/sắp hết, ngưỡng `VANI_LOW_STOCK_THRESHOLD`); `CartPresenter` định dạng giỏ từ contract `Carts` |
| **Native storefront** | Controller storefront gọi **cùng Query/Command** mà API dùng, trong cùng process (không tự gọi HTTP tới chính mình, rule R19). Blade chỉ render DTO |
| **Headless** (Next.js, mobile, Zalo Mini App) | Gọi `/api/storefront/v1` ([api](../06-api/api.md)) |

Nhờ vậy native và headless có cùng hành vi: giá, ATS, khuyến mãi, validation đều tính ở một nơi.

## 2. Một giao diện, tuỳ biến không fork

Cửa hàng có **một theme đang hoạt động** (cấu hình `theme`, mặc định `vani-base`). Brand **không** có theme riêng; brand hiện diện qua trang brand, logo, bộ lọc ([store-and-brand §2](../12-store/store-and-brand.md)).

| Lớp tuỳ biến | Lưu ở | Ai chỉnh |
|---|---|---|
| **Theme tokens** (màu, font, radius, spacing, shadow) | Setting cửa hàng `theme.tokens` (JSON) → CSS variables → Tailwind 4 `@theme` | Quản trị |
| **Thông tin cửa hàng** (logo, hotline, chính sách, footer pháp lý của pháp nhân vận hành) | Settings cửa hàng | Quản trị |
| **Brand** (logo, mô tả, banner trang brand, thứ tự hiển thị) | Catalog `brands` | Merchandiser |
| **Collection config** (bộ sưu tập, sắp xếp, ghim) | Catalog | Merchandiser |
| **Content** (trang, banner, menu — menu có thể có mục "Thương hiệu") | Content | Merchandiser |
| **Layout / Blocks** (page builder, gồm block "lưới thương hiệu") | `page_blocks` JSON | Merchandiser |
| **Components** (override view có chọn lọc) | Theme con `custom/theme/<store-theme>/` (`parent: vani-base`) | Dev |

View fallback: theme đang hoạt động → `custom/theme/vani-base` → view mặc định của module. Theme con chỉ override **ít file**.

```
custom/theme/vani-base/
├── theme.json            # tên, parent, blocks hỗ trợ, token mặc định
├── views/                # layouts, components, pages (pdp, plp, cart, checkout, account)
├── css/app.css           # @theme dùng CSS variables từ token
└── js/app.js             # Alpine components (chỉ UI: gallery, chọn size, mini-cart)
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

## 5. Hiệu năng và SEO

- SSR cho trang public, cache CDN 60–300s + `stale-while-revalidate`; phần cá nhân hoá (giỏ, giá thành viên) tải qua API sau khi trang hiện.
- Cache ứng dụng: `style:{id}:v{version}`, `brand:{id}:v{version}`; invalidate theo event (`ProductUpdated`, `PriceChanged`, `AvailabilityChanged`).
- URL: một website ([store-and-brand §4](../12-store/store-and-brand.md)): `/san-pham/{slug}`, `/danh-muc/{slug}`, `/thuong-hieu/{slug}`…
- SEO: một canonical cho mỗi sản phẩm (không lặp theo brand), một sitemap (chia file khi lớn, gồm trang brand), schema.org `Product`/`Offer` có `brand`, schema.org `Product`/`Offer`, hreflang khi đa ngôn ngữ.
- Mục tiêu: LCP mobile < 2,5s, CLS < 0,1.

## 6. Kiểm thử

- Arch test: namespace controller storefront không dùng `Persistence`/`Domain` (chỉ dùng Application).
- Test snapshot HTML tối thiểu cho các trang chính; E2E luồng mua ([testing](../17-testing/testing.md)).
- Test theme fallback: theme con thiếu view thì dùng view của `vani-base`.
- Test trang brand: chỉ hiện sản phẩm của brand; brand ẩn → 404.
- Test trang chính với JS tắt: nội dung, giá, nút thêm giỏ (form POST dự phòng) vẫn hiển thị.
- Test slot: listener plugin ném lỗi → trang vẫn render, phần tử khác vẫn có; plugin tắt → không hiện.
