# vani.search-meilisearch

`SearchProvider` mã `meilisearch` cho danh sách/tìm kiếm sản phẩm storefront (tìm không dấu, facet màu + thuộc tính).
Trước 2026-10-14 nằm trong Core (Catalog); tách thành plugin theo tiêu chí [commerce-kernel](../../../docs/02-architecture/commerce-kernel.md) — tích hợp dịch vụ ngoài là plugin.

## Bật

```bash
php artisan vani:plugin:install vani.search-meilisearch
php artisan vani:plugin:enable vani.search-meilisearch --scope=owner   # search là hạ tầng: chỉ scope owner
# .env: VANI_SEARCH_PROVIDER=meilisearch, MEILISEARCH_HOST, MEILISEARCH_KEY, MEILISEARCH_INDEX
php artisan vani:search:reindex --setup                                  # cấu hình chỉ mục (ConfigurableSearchIndex) + index lại
```

Chưa bật plugin mà `VANI_SEARCH_PROVIDER=meilisearch` → Core tự dùng provider `database` (có cảnh báo trong log), storefront vẫn chạy.
Meilisearch lỗi lúc tìm → Core rơi về `database` cho lần tìm đó (`Extensions::call`, circuit breaker theo plugin).
