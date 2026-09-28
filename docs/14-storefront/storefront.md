# Storefront

> Trạng thái: **Designed**. Quyết định: [ADR-009](../19-adr/ADR-009-storefront-architecture.md).

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

## 2. Tuỳ biến theo brand, không fork

| Lớp tuỳ biến | Lưu ở | Ai chỉnh |
|---|---|---|
| **Theme tokens** (màu, font, radius, spacing, shadow) | `brands.theme_tokens` (JSON) → CSS variables → Tailwind 4 `@theme` | Brand Manager |
| **Brand config** (logo, hotline, chính sách, footer pháp lý) | Settings scope brand | Brand Manager |
| **Collection config** (bộ sưu tập, sắp xếp, ghim) | Catalog | Merchandiser |
| **Content** (trang, banner, menu) | Content | Merchandiser |
| **Layout / Blocks** (page builder) | `page_blocks` JSON theo channel | Merchandiser |
| **Components** (override view có chọn lọc) | `custom/theme/<brand-theme>/` | Dev |

View fallback: `custom/theme/<channel-theme>` → `custom/theme/vani-base` → view mặc định của module. Một theme brand chỉ override **ít file**; phần còn lại kế thừa `vani-base`.

```
custom/theme/vani-base/
├── theme.json            # tên, parent, blocks hỗ trợ, token mặc định
├── views/                # layouts, components, pages (pdp, plp, cart, checkout, account)
├── css/app.css           # @theme dùng CSS variables từ token
└── js/app.js             # Alpine components (chỉ UI: gallery, chọn size, mini-cart)
```

## 3. Block (page builder)

```php
interface StorefrontBlock
{
    public function type(): string;               // 'hero', 'product_grid', 'collection_carousel', 'rich_text'
    public function schema(): array;              // JSON Schema cho Admin editor
    public function resolve(array $config, ChannelData $channel): BlockViewData;  // lấy dữ liệu qua Query
    public function view(): string;               // Blade view (theme có thể override)
}
```

Plugin thêm block qua tag `vani.content.blocks` (ví dụ lookbook, recommendation).

## 4. Hiệu năng và SEO

- SSR cho trang public, cache CDN 60–300s + `stale-while-revalidate`; phần cá nhân hoá (giỏ, giá thành viên) tải qua API sau khi trang hiện.
- Cache ứng dụng có khoá theo channel: `ch:{channel}:style:{id}:v{version}`; invalidate theo event (`ProductUpdated`, `PriceChanged`, `AvailabilityChanged`).
- URL: một domain chung, mỗi brand một đường dẫn (`vani.vn/lumiere/…`), [multi-brand §5](../12-multi-brand/multi-brand.md). Mọi link và route storefront sinh kèm `path_prefix` của channel hiện tại.
- SEO: canonical theo đường dẫn brand, sitemap mỗi brand + sitemap index ở gốc, schema.org `Product`/`Offer`, hreflang khi đa ngôn ngữ.
- Mục tiêu: LCP mobile < 2,5s, CLS < 0,1.

## 5. Kiểm thử

- Arch test: namespace controller storefront không dùng `Persistence`/`Domain` (chỉ dùng Application).
- Test snapshot HTML tối thiểu cho các trang chính; E2E luồng mua ([testing](../17-testing/testing.md)).
- Test theme fallback: theme brand thiếu view thì dùng view của `vani-base`.
