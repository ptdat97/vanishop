# Catalog

> Vòng 1 · Mức domain: **Rich (biến thể) + CRUD (thuộc tính, thương hiệu)** · Tài liệu: [catalog-pricing.md](../../docs/03-domains/catalog-pricing.md)

## Trách nhiệm

Sản phẩm (style), SKU (variant) theo 1–3 trục tuỳ chọn, danh mục, bộ sưu tập, thương hiệu, thuộc tính, media, tìm kiếm SQL.

## Bất biến

- SKU duy nhất toàn hệ thống; SKU đã phát sinh dữ liệu (đơn, tồn, giá…) không xoá được (`VariantUsage`).
- Chuyển sản phẩm đơn ⇄ có biến thể giữ id SKU và lịch sử thương mại.
- Tìm kiếm chỉ bằng SQL (provider `database`).

## Bảng sở hữu

- `brands`
- `categories`
- `category_translations`
- `attributes`
- `attribute_translations`
- `attribute_values`
- `attribute_value_translations`
- `colors`
- `color_translations`
- `sizes`
- `media`
- `mediables`
- `styles`
- `style_translations`
- `style_colors`
- `category_style`
- `style_attribute_values`
- `collections`
- `collection_translations`
- `collection_style`
- `variants`
- `media_folders`
- `link_types`
- `link_type_translations`
- `style_link_groups`
- `style_link_group_items`
- `videos`
- `style_video`
- `style_view_stats`
- `option_types`
- `option_type_translations`
- `option_values`
- `option_value_translations`
- `style_options`
- `style_option_values`
- `variant_option_values`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `CatalogImporter` | interface | giữa module |
| `CatalogLookup` | interface | plugin |
| `CatalogReader` | interface | plugin |
| `CollectionDirectory` | interface | plugin |
| `ConfigurableSearchIndex` | interface | plugin |
| `ImageFormat` | interface | tag `catalog.image-format`, plugin |
| `ImageSizeProvider` | interface | tag `vani.catalog.image_sizes`, plugin |
| `ListPrices` | interface | giữa module |
| `MediaDirectory` | interface | plugin |
| `MediaUrls` | interface | plugin |
| `ProductViewStatistics` | interface | giữa module |
| `SearchProvider` | interface | tag `vani.search.providers`, plugin |
| `VariantDirectory` | interface | plugin |
| `VariantFieldProvider` | interface | tag `vani.catalog.variant_fields`, plugin |
| `VariantUsage` | interface | tag `vani.catalog.variant_usage`, plugin |
| `VideoDriver` | interface | tag `catalog.video-driver`, plugin |

## Event

`ProductArchived`, `ProductCreated`, `ProductUpdated`, `ProductViewed`, `VariantCreated`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
