# Storefront

> Vòng 1 · Mức domain: **Điều phối (không có bảng)** · Tài liệu: [storefront.md](../../docs/14-storefront/storefront.md)

## Trách nhiệm

Tầng ghép storefront: Storefront API v1 + Blade, theme, menu, block trang chủ, link, SEO (ADR-021, ADR-044).

## Bất biến

- API-first: mọi tính năng storefront có endpoint `/api/storefront/v1` (R37); hành vi ở use-case, không ở controller/view.
- Không sở hữu bảng; đọc/ghi qua contract các module khác.
- Theme/menu/block/link là khung mở rộng của Core; trang và bài viết do `vani.cms` sở hữu (C3).

## Bảng sở hữu

Không có bảng.

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `LinkResolver` | interface | plugin |
| `LinkType` | interface | tag `vani.storefront.link_types`, plugin |
| `PageSeo` | interface | plugin |
| `ProductCards` | interface | plugin |
| `SitemapProvider` | interface | tag `vani.storefront.sitemap`, plugin |
| `StorefrontBlock` | interface | tag `vani.storefront.blocks`, plugin |
| `StorefrontEnricher` | interface | tag `vani.storefront.enrichers`, plugin |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
