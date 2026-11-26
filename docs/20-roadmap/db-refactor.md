# Kế hoạch refactor DB: đổi Style → Product, read model cho listing, chuẩn bị scale

> Trạng thái: **In Progress** (2026-11-26). Owner đã duyệt: đổi `style_code` → `product_code`, đổi toàn bộ tiền tố `style` → `product`, thêm read model `product_listing` / `product_facet_values`. **G1 xong** (Core 0.5.89, [ADR-050](../19-adr/ADR-050-product-replaces-style.md)); G0 đo sau G1 vì đổi tên không ảnh hưởng hiệu năng (seeder quy mô viết một lần với tên bảng mới). Khi làm xong từng giai đoạn thì cập nhật [status](../00-overview/status.md) và tài liệu liên quan.
>
> Liên quan: [database](../07-database/database.md) (quy ước giữ nguyên), [catalog-pricing](../03-domains/catalog-pricing.md), [ADR-045](../19-adr/ADR-045-flexible-variant-options.md) (biến thể linh hoạt — lý do chữ "style" không còn đúng), [ADR-048](../19-adr/ADR-048-extension-api-version.md) (version API cho plugin).

## 0. Bối cảnh và nguyên tắc

- Tên `styles` có từ mô hình thời trang Style → Màu → Size. Từ ADR-045, sản phẩm có 1–3 trục tuỳ chọn bất kỳ, nên "style" không còn đúng với mọi loại hàng. Code đã dùng lẫn hai tên: DB/model là `Style`, tầng ứng dụng và UI là `Product`.
- **Chưa lên production** ([status](../00-overview/status.md#production-readiness)). Vì vậy **sửa thẳng migration gốc** rồi `migrate:fresh --seed`; không viết migration đổi tên, không cần expand → migrate → contract. Nếu trước khi bắt đầu đã có môi trường giữ dữ liệu thật thì dừng lại hỏi Owner.
- **Giữ nguyên** các quy ước đã tốt ở [database §1](../07-database/database.md#1-quy-ước): PK `BIGINT`, ULID `public_id`, tiền `BIGINT` đơn vị nhỏ nhất, ledger append-only, `lock_version`, CHECK constraint, enum là `VARCHAR`.
- **Tìm kiếm chỉ bằng SQL** (Owner, 2026-11-12). Kế hoạch này không thêm máy chủ tìm kiếm.
- Quy mô mục tiêu dùng để đo: **100.000 sản phẩm, 1.000.000 SKU, 5.000.000 đơn**, vài chục triệu dòng ledger/log.

### Không làm

| Đề xuất | Lý do không làm |
|---|---|
| UUID làm PK mọi bảng | Phân mảnh clustered index InnoDB; đã có ULID `public_id` cho thực thể lộ ra ngoài |
| EAV tổng quát, lọc trên cột JSON | Không index được; dùng cột thật hoặc generated column |
| Tách DB theo module, sharding | Một cửa hàng, chưa cần |
| Máy chủ tìm kiếm ngoài | Ngược quyết định Owner; chỉ mở lại nếu G0/G2 chứng minh FULLTEXT không đủ |

## 1. Thứ tự thực hiện

| Giai đoạn | Nội dung | Phụ thuộc |
|---|---|---|
| **G0** | Môi trường Mac + đo hiện trạng | — |
| **G1** ✅ | Đổi tên Style → Product (một nhánh, không trộn logic) | G0 |
| **G2** | Sửa điểm nghẽn: FULLTEXT, `filter_token`, read model, index | G1 |
| **G3** | Bảng lớn: retention/archive, đếm lượt xem qua Redis, keyset, read replica | G2 |

Mỗi giai đoạn là một hoặc vài PR riêng, mỗi PR có test xanh.

## 2. G0 — Môi trường Mac và đo hiện trạng

### 2.1 Môi trường

- PHP 8.4, Composer, Node theo `package.json`. Site chạy qua **Laravel Herd** (`https://vanishop.test`).
- **MySQL 8.4 LTS** (Herd Pro hoặc DBngin/Homebrew). Bắt buộc cho G2–G3 vì FULLTEXT `ngram`, generated column, partition không chạy trên SQLite.
- Kiểm tra `SHOW VARIABLES LIKE 'ngram_token_size';` = `2` (mặc định).
- Redis (Herd service hoặc Homebrew) cho G3.
- Test mặc định chạy SQLite `:memory:` ([phpunit.xml](../../phpunit.xml)). Các test chạm FULLTEXT/partition cần chạy thêm trên MySQL (xem §6).

### 2.2 Đo

1. Viết seeder quy mô (không commit dữ liệu, chỉ seeder/command) tạo 100k sản phẩm / 1M SKU / 5M đơn trên DB MySQL tạm.
2. Ghi `EXPLAIN ANALYZE` và thời gian cho các truy vấn nóng:
   - listing danh mục + facet, lọc theo tuỳ chọn, sắp xếp giá ([ProductSearch](../../modules/Catalog/Application/Search/ProductSearch.php));
   - tìm kiếm chữ;
   - PDP; thêm vào giỏ; checkout có reserve tồn;
   - danh sách đơn Admin theo trạng thái / khách; sync API `(updated_at, id)`;
   - sản phẩm bán chạy ([EloquentOrderStatistics](../../modules/Ordering/Application/EloquentOrderStatistics.php)).
3. Mở rộng [AdminWorkflowBenchmarkTest](../../tests/Performance/AdminWorkflowBenchmarkTest.php) làm mốc so sánh sau G2, G3.
4. Ghi kết quả vào mục §7 của tài liệu này.

## 3. G1 — Đổi tên Style → Product

### 3.1 Bảng và cột

| Hiện tại | Mới |
|---|---|
| `styles` | `products` |
| `styles.style_code` | `products.product_code` (unique) |
| `style_translations` | `product_translations` |
| `style_options`, `style_option_values` | `product_options`, `product_option_values` |
| `style_attribute_values` | `product_attribute_values` |
| `style_link_groups`, `style_link_group_items` | `product_link_groups`, `product_link_group_items` |
| `style_video` | `product_video` |
| `style_view_stats` | `product_view_stats` |
| `category_style`, `collection_style` | `category_product`, `collection_product` |
| cột `style_id` ở mọi bảng Core và plugin | `product_id` |
| `order_lines.meta->style_id` | cột `order_lines.product_id` (xem G2 §4.5) |
| setting `catalog.style_code_prefix`, `catalog.style_code_digits` | `catalog.product_code_prefix`, `catalog.product_code_digits` |

- **Giữ nguyên `variants`.**
- Morph type (nếu có `catalog.style`) → `catalog.product`. Morph map nằm ở [CatalogServiceProvider.php:106](../../modules/Catalog/CatalogServiceProvider.php#L106). Morph `catalog.style_color` chỉ còn trong migration chuyển đổi ADR-045 — xem §3.4.

### 3.2 Code

- Model: `Style` → `Product`, `StyleTranslation` → `ProductTranslation`, `StyleOption`, `StyleOptionValue`, `StyleAttributeValue`, `StyleLinkGroup`, `StyleLinkGroupItem` → tiền tố `Product`. `ProductCollection` đã có tên, giữ nguyên. Factory tương ứng (`StyleFactory` → `ProductFactory`).
- Service: `StyleLinkService` → `ProductLinkService` (kiểm tra trùng tên với class đã có trước khi đổi).
- Biến, thuộc tính, request field, Vue prop, TS type: `style_code` → `product_code`, `styleId` → `productId`, `suggestedStyleCode` → `suggestedProductCode`, `data-style-code` → `data-product-code`.
- Chuỗi dịch: khoá `products_form.product_code_style_code` → `products_form.product_code`, nhãn "Mã sản phẩm" / "Product code". Rà các khoá còn chữ `style` trong `modules/*/resources/lang`.
- Phạm vi đo được (2026-11-26): khoảng 190 file PHP nhắc tới model `Style`; `style_id` xuất hiện trong ~36 file Catalog, 34 file plugin, 9 Storefront, 8 Pricing, 5 Ordering, 3 Inventory, 2 Promotion và các test.
- Migration plugin có `style_id`: Bundles, Cms (`plg_cms_extra_content`), HelloWorld, Wishlist. Rà thêm bằng `grep -rn "style" custom/plugin`.

### 3.3 Hợp đồng ra ngoài

- Storefront API: `style_code` → `product_code` trong [product.v1.json](../api/schemas/storefront/product.v1.json), [product-card.v1.json](../api/schemas/storefront/product-card.v1.json); cập nhật `tests/Architecture/storefront-api.snapshot`. Chưa phát hành nên giữ `v1`, ghi rõ trong CHANGELOG.
- Payload tích hợp/ERP và file import/export: cột/field `style_code` → `product_code`. Cập nhật [erp-integration](../11-integration/erp-integration.md).
- Extension API: đổi tên trong contract cho plugin là thay đổi khối `stable` → **tăng major** theo ADR-048 (chạy `VANI_UPDATE_API_SNAPSHOT=1`), sửa `requires.extension_api` của mọi plugin trong repo, ghi `docs/04-extension/CHANGELOG-extension.md`.
- Structured data SEO ([StructuredData.php](../../modules/Storefront/View/StructuredData.php)): giữ đúng thuộc tính schema.org, chỉ đổi nguồn dữ liệu.

### 3.4 Migration

- Sửa tại chỗ các migration gốc (`create_styles_tables`, `create_variants_table`, `create_option_tables`, `create_style_links_and_videos_tables`, `create_style_view_stats`, `add_list_price_min_to_styles`, `add_track_inventory_to_styles`, `add_localized_slugs_to_catalog_translations`, migration plugin ở §3.2). Đổi tên file migration nếu tên chứa `style`.
- Phần `migrateData()` chuyển màu × size sang tuỳ chọn trong `create_option_tables` chỉ phục vụ DB cũ. Vì chạy `migrate:fresh`, **gộp kết quả cuối vào migration gốc**: `variants` tạo thẳng với `option_key`, bỏ `style_color_id`/`size_id`, bỏ tạo rồi xoá `colors`/`sizes`/`style_colors`. Tương tự gộp `add_variant_options_to_order_lines` vào `create_order_tables` (`variant_options` json, không có `color_name`/`size_code`).
- Seeder demo ([database/seeders/Demo](../../database/seeders/Demo)) đổi theo.

### 3.5 Tài liệu

- ADR mới **ADR-050** — "Product thay Style": ghi lý do, bảng đổi tên, quyết định `product_code`.
- Cập nhật: [database](../07-database/database.md), [catalog-pricing](../03-domains/catalog-pricing.md) (bảng thực thể: Style → Product), [vision](../00-overview/vision.md) (dòng "Style → Màu → Size"), README plugin có nhắc `style_*`.
- Sinh lại [schema.generated.md](../07-database/schema.generated.md) bằng `php artisan vani:docs:schema` trên MySQL tạm vừa `migrate:fresh`.

### 3.6 Xong khi

- `grep -rniE "\bstyle(s|_id|_code)?\b" modules custom database tests resources` chỉ còn các chỗ không thuộc catalog (CSS, `@admin/styles`, chuỗi giao diện).
- Toàn bộ suite xanh, browser test xanh, storefront và Admin mở được trên `https://vanishop.test`.

## 4. G2 — Sửa điểm nghẽn khi scale

### 4.1 Tìm kiếm chữ: FULLTEXT `ngram`

- Hiện tại: `search_text LIKE '%token%'` ([ProductSearch.php:73](../../modules/Catalog/Application/Search/ProductSearch.php#L73)) — quét toàn bảng.
- Đổi: `FULLTEXT INDEX ft_products_search (search_text) WITH PARSER ngram` (chỉ trên MySQL, bọc `DB::getDriverName() === 'mysql'` như CHECK constraint hiện có). Truy vấn `MATCH(search_text) AGAINST(? IN BOOLEAN MODE)` với mỗi token là cụm `"..."`.
- Dùng `ngram` vì parser mặc định bỏ từ dưới 3 ký tự (`innodb_ft_min_token_size`), trong khi văn bản không dấu có nhiều từ 2 ký tự ("ao", "mi", "so").
- SQLite: giữ nhánh `LIKE` để test chạy được.
- Test: kết quả tìm "ao so mi" và mã sản phẩm một phần trùng với hành vi cũ (chạy trên MySQL).

### 4.2 Lọc theo tuỳ chọn: cột `filter_token`

- Hiện tại: `CASE WHEN ot.display_type = 'swatch' THEN ov.family ELSE ov.code END` trong WHERE và GROUP BY ([OptionFilterToken.php:19](../../modules/Catalog/Application/Search/OptionFilterToken.php#L19)) — không dùng index.
- Đổi: cột `option_values.filter_token VARCHAR(32)` do ứng dụng ghi khi lưu giá trị (không dùng generated column vì phụ thuộc `option_types.display_type` ở bảng khác). Đổi `display_type` của một loại → ghi lại `filter_token` cho mọi giá trị của loại đó. Index `(option_type_id, filter_token)`.
- Sau khi có §4.3, truy vấn lọc đọc từ `product_facet_values`, nên cột này chủ yếu phục vụ việc dựng read model.

### 4.3 Read model cho listing và facet (Owner đã duyệt)

```text
product_listing
  product_id        BIGINT PK  (FK products, cascade)
  status            VARCHAR(16)
  published_from    DATETIME NULL
  published_to      DATETIME NULL
  brand_id          BIGINT NULL
  list_price_min    BIGINT NULL        -- chuyển từ styles.list_price_min
  in_stock          BOOLEAN            -- còn ít nhất một SKU bán được
  created_at        DATETIME
  INDEX (status, published_from)
  INDEX (status, list_price_min, product_id)
  INDEX (status, created_at, product_id)
  INDEX (brand_id, status)

product_facet_values
  facet       VARCHAR(48)   -- 'option:color', 'option:size', 'attribute:material', 'category', 'collection'
  token       VARCHAR(64)   -- filter_token / attribute_value_id / category_id
  product_id  BIGINT
  PRIMARY KEY (facet, token, product_id)
  INDEX (product_id)
```

- **Ghi:** service `ProductListingProjector` trong Catalog cập nhật lại một sản phẩm khi: lưu sản phẩm/biến thể/tuỳ chọn/thuộc tính/danh mục/bộ sưu tập; đổi giá bảng `base` (qua contract `ListPrices` sẵn có); tồn kho chuyển qua lại giữa 0 và > 0. Ghi trong cùng transaction khi thay đổi nằm trong Catalog; thay đổi từ Pricing/Inventory đi qua event + queue (chấp nhận trễ vài giây).
- **Danh mục:** `facet = 'category'` lưu cả danh mục cha (theo `categories.path`) để lọc "gồm danh mục con" không cần `LIKE` trên path.
- **Đọc:** `ProductSearch` lọc bằng `whereExists` / `join` vào `product_facet_values` theo `(facet, token)`; facet đếm bằng `GROUP BY facet, token` trên tập `product_id` đã lọc. Sắp xếp dùng index của `product_listing`.
- **Dựng lại:** command `vani:catalog:rebuild-listing {--product=}` dựng toàn bộ hoặc một sản phẩm; chạy sau `migrate:fresh --seed` và trong runbook deploy khi đổi logic projector.
- **Thứ tự merchandising trong danh mục** vẫn đọc `category_product.position`.
- Test: mỗi nguồn thay đổi ở trên làm read model đúng; kết quả `ProductSearch` (ids, total, facet) giống bản cũ trên cùng dữ liệu seed — viết test so sánh trước khi bỏ truy vấn cũ.

### 4.4 Index đơn hàng

- Thêm `orders (customer_id, placed_at)` (tài liệu ghi đã có nhưng migration chỉ có `index(customer_id)`). Bỏ `index(customer_id)` đơn lẻ vì index ghép đã bao.

### 4.5 Sản phẩm bán chạy

- Hiện tại group by `order_lines.meta->style_id` ([EloquentOrderStatistics.php:191](../../modules/Ordering/Application/EloquentOrderStatistics.php#L191)) — JSON path, không index.
- Đổi: cột `order_lines.product_id BIGINT NULL` (snapshot, không FK, giống `brand_id`), ghi khi tạo dòng đơn; index `(product_id)`. Bỏ `style_id` khỏi `meta`.

### 4.6 Dọn chỗ lệch

- Soft delete: [database §1](../07-database/database.md) ghi Catalog dùng soft delete cho `styles`, nhưng không có `deleted_at`. Quyết định: **không soft delete**, dùng trạng thái `archived`; sửa tài liệu.
- `product_option_values`: thay `style_id` bằng `product_option_id` (FK `product_options`, cascade) để DB đảm bảo giá trị thuộc đúng trục của sản phẩm. Unique `(product_option_id, option_value_id)`. Kiểm tra `option_values.option_type_id = product_options.option_type_id` ở service + test.

## 5. G3 — Chuẩn bị cho bảng lớn

| Việc | Chi tiết |
|---|---|
| Retention / archive | Command theo lịch cho `integration_logs`, `notification_logs`, `audit_logs`, `order_events`, `pulse_*`: chuyển dòng cũ hơn N tháng (cấu hình qua settings) sang bảng `*_archive` hoặc xoá theo chính sách. Xoá theo lô `id` tăng dần, không `DELETE` một lần cả bảng |
| Partition | Chỉ chuẩn bị (runbook + migration mẫu), chưa bật: `PARTITION BY RANGE (TO_DAYS(created_at))` theo tháng cho `stock_movements`, `price_history` khi vượt ~50 triệu dòng. Bảng partition không có FK — hai bảng này vốn đã không có FK; PK phải gồm cột partition (`PRIMARY KEY (id, created_at)`) |
| Đếm lượt xem | Hiện `upsert` mỗi lượt xem ([ProductViewCounter.php:33](../../modules/Catalog/Application/Products/ProductViewCounter.php#L33)) — dòng nóng bị tranh khoá. Đổi: `INCR` vào cache Redis theo `(product_id, giờ)`, job mỗi phút ghi dồn xuống `product_view_stats`. Gộp dữ liệu giờ cũ hơn 30 ngày thành theo ngày |
| Keyset pagination | Danh sách Admin lớn (đơn, tồn, biến động tồn) và sync API dùng `(sort_col, id)` thay `OFFSET` khi vượt trang N; giữ phân trang số trang cho trang đầu |
| Read replica | `config/database.php`: kết nối `mysql` có `read`/`write` + `sticky => true`. Báo cáo (plugin Reports, `EloquentOrderStatistics`) dùng kết nối replica riêng. Chỉ bật bằng env, mặc định tắt |

## 6. Kiểm tra

- Sau mỗi PR: test của module bị ảnh hưởng (`php artisan test --compact <path>`), rồi toàn bộ suite.
- Test chạm MySQL-only (FULLTEXT, partition): nhóm `@group mysql`, chạy với `DB_CONNECTION=mysql DB_DATABASE=vanishop_test php artisan test --group=mysql`. Bỏ qua (`markTestSkipped`) khi driver là SQLite.
- `vendor/bin/pint --dirty --format agent` cho file PHP đã sửa.
- `npm run build` sau khi đổi Vue/TS.
- Sinh lại `schema.generated.md`, snapshot API, snapshot plugin API.
- So benchmark với mốc G0, ghi vào §7.

## 7. Kết quả đo

| Truy vấn | G0 | Sau G2 | Sau G3 |
|---|---|---|---|
| _(điền khi làm G0)_ | | | |
