# Kế hoạch refactor DB: đổi Style → Product, read model cho listing, chuẩn bị scale

> Trạng thái: **Implemented** (2026-11-26). Owner đã duyệt: đổi `style_code` → `product_code`, đổi toàn bộ tiền tố `style` → `product`, thêm read model `product_listing` / `product_facet_values`. **G1 xong** (Core 0.5.89, [ADR-050](../19-adr/ADR-050-product-replaces-style.md)); **G0 xong** (mốc §7), **G2 xong** (Core 0.5.90, §7); **G3 xong** (Core 0.5.91: lưu giữ dữ liệu, đếm lượt xem qua cache, read replica, runbook partition, read model lưới tồn; keyset → deferred join ở 0.5.94, §6b L54); G0 đo sau G1 vì đổi tên không ảnh hưởng hiệu năng (seeder quy mô viết một lần với tên bảng mới). Khi làm xong từng giai đoạn thì cập nhật [status](../00-overview/status.md) và tài liệu liên quan.
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
| **G2** ✅ | Sửa điểm nghẽn: FULLTEXT, `filter_token`, read model, index | G1 |
| **G3** ✅ | Bảng lớn: retention/archive, đếm lượt xem qua Redis, keyset (để sau — §6b), read replica | G2 |

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
3. Mốc so sánh sau G2, G3: lệnh `vani:bench:measure` (thay cho việc mở rộng [AdminWorkflowBenchmarkTest](../../tests/Performance/AdminWorkflowBenchmarkTest.php) — test đó tự dựng 1.000 sản phẩm bằng `RefreshDatabase`, không chạy được trên DB đã sinh sẵn 5 triệu đơn; giữ nguyên làm mốc quy mô nhỏ).
4. Ghi kết quả vào mục §7 của tài liệu này.

**Cách chạy** (✅ G0, 2026-11-26):

```bash
mysql -uroot -e "CREATE DATABASE vanishop_bench; SET GLOBAL innodb_buffer_pool_size = 4294967296;"
DB_DATABASE=vanishop_bench php artisan vani:bench:seed --fresh          # ~10 phút, ~10 GB; --continue sinh tiếp đơn nếu bị dừng
DB_DATABASE=vanishop_bench php -d memory_limit=2G artisan vani:bench:measure [--only="listing|admin"]
```

`vani:bench:seed` chỉ chạy trên MySQL với DB tên `*_bench`; sinh bằng `INSERT … SELECT` (không qua service). `vani:bench:measure` gửi request qua HTTP kernel trong cùng process, 1 lần làm nóng + 5 lần đo, ghi bảng và `EXPLAIN ANALYZE` của truy vấn chậm nhất mỗi kịch bản vào `storage/logs/bench-scale.md`.

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

### 4.7 Lựa chọn bộ lọc "phương thức thanh toán" ở danh sách đơn (bổ sung sau G0, Owner 2026-11-26)

- Hiện tại: mỗi lần mở danh sách đơn Admin chạy `select distinct payment_method from orders` — quét toàn bảng (~1,5 s ở 5 triệu đơn).
- Đổi: lấy lựa chọn từ danh sách phương thức thanh toán đã đăng ký (registry của Payment/plugin) thay vì từ dữ liệu đơn; đơn cũ có mã không còn đăng ký vẫn lọc được bằng URL.

### 4.8 Sync API đơn: bỏ N+1 (bổ sung sau G0, Owner 2026-11-26)

- Hiện tại: `OrderFeed::page()` tìm trang bằng index `(updated_at, id)` (0,07 ms) nhưng dựng payload từng đơn — 251 truy vấn cho 50 đơn.
- Đổi: nạp dòng đơn, thanh toán, vận đơn… của cả trang bằng `whereIn` (eager load); số truy vấn không tăng theo số đơn. Test chặn số truy vấn.

## 5. G3 — Chuẩn bị cho bảng lớn

| Việc | Chi tiết |
|---|---|
| Retention / archive | Command theo lịch cho `integration_logs`, `notification_logs`, `audit_logs`, `order_events`, `pulse_*`: chuyển dòng cũ hơn N tháng (cấu hình qua settings) sang bảng `*_archive` hoặc xoá theo chính sách. Xoá theo lô `id` tăng dần, không `DELETE` một lần cả bảng |
| Partition | Chỉ chuẩn bị (runbook + migration mẫu), chưa bật: `PARTITION BY RANGE (TO_DAYS(created_at))` theo tháng cho `stock_movements`, `price_history` khi vượt ~50 triệu dòng. Bảng partition không có FK — hai bảng này vốn đã không có FK; PK phải gồm cột partition (`PRIMARY KEY (id, created_at)`) |
| Đếm lượt xem | Hiện `upsert` mỗi lượt xem ([ProductViewCounter.php:33](../../modules/Catalog/Application/Products/ProductViewCounter.php#L33)) — dòng nóng bị tranh khoá. Đổi: `INCR` vào cache Redis theo `(product_id, giờ)`, job mỗi phút ghi dồn xuống `product_view_stats`. Gộp dữ liệu giờ cũ hơn 30 ngày thành theo ngày |
| Keyset pagination | Danh sách Admin lớn (đơn, tồn, biến động tồn) và sync API dùng `(sort_col, id)` thay `OFFSET` khi vượt trang N; giữ phân trang số trang cho trang đầu |
| Lưới tồn và Tổng quan Admin (bổ sung sau G0, Owner 2026-11-26) | `EloquentVariantDirectory::searchIndex()` nạp mọi SKU và giá trị tuỳ chọn vào PHP để sắp xếp/phân trang — vượt 2 GB ở 1 triệu SKU. Đổi: lọc, sắp xếp, phân trang bằng SQL (keyset theo khoá sắp xếp), đếm "sắp hết hàng" của Tổng quan bằng truy vấn đếm |
| Read replica | `config/database.php`: kết nối `mysql` có `read`/`write` + `sticky => true`. Báo cáo (plugin Reports, `EloquentOrderStatistics`) dùng kết nối replica riêng. Chỉ bật bằng env, mặc định tắt |

## 6. Kiểm tra

- Sau mỗi PR: test của module bị ảnh hưởng (`php artisan test --compact <path>`), rồi toàn bộ suite.
- Test chạm MySQL-only (FULLTEXT, partition): nhóm `@group mysql`, chạy với `DB_CONNECTION=mysql DB_DATABASE=vanishop_test php artisan test --group=mysql`. Bỏ qua (`markTestSkipped`) khi driver là SQLite.
- `vendor/bin/pint --dirty --format agent` cho file PHP đã sửa.
- `npm run build` sau khi đổi Vue/TS.
- Sinh lại `schema.generated.md`, snapshot API, snapshot plugin API.
- So benchmark với mốc G0, ghi vào §7.

## 7. Kết quả đo

Dữ liệu `vani:bench:seed`: 100.000 sản phẩm (2% nháp, 2% lưu trữ), 1.000.000 SKU (2 màu × 5 size, 10% hết hàng), 220 danh mục 2 cấp, 50 thương hiệu, 500.000 khách, 5.000.000 đơn / 10.000.000 dòng đơn trong 2 năm (~9,8 GB). MySQL 9.7.2 trên Mac (16 GB RAM), buffer pool 4 GB. ms = trung vị toàn request (SQL ms trong ngoặc); "OOM" = vượt `memory_limit` 2 GB.

| Kịch bản | G0 (2026-11-26) | Sau G2 | Sau G3 |
|---|---:|---:|---:|
| Listing: danh mục cha (gồm con), mới nhất | 550 (493) | 283 (220) | |
| Listing: danh mục cha + lọc màu | 936 (871) | 289 (230) | |
| Listing: danh mục cha + lọc màu + size | 1.353 (1.329) | 605 (593) | |
| Listing: toàn bộ, giá tăng dần | **88.750** (88.681) | **1.132** (1.066) | |
| Listing: danh mục con, giá giảm dần | 125 (82) | 49 (18) | |
| Listing: thuộc tính + thương hiệu | 130 (87) | 236 (174) | |
| Listing: danh mục cha, trang 40 | 1.479 (1.418) | 283 (219) | |
| Tìm chữ: "áo sơ mi lụa" | 455 (393) | 319 (258) | |
| Tìm chữ: mã một phần "BP0500" | 356 (294) | 213 (152) | |
| PDP: API chi tiết sản phẩm | 12 (5) | 12 (4) | |
| Giỏ: tạo giỏ + thêm 1 dòng | 14 (7) | 14 (6) | |
| Checkout: đặt đơn COD (giữ tồn) | 52 (26) | 49 (22) | |
| Admin: danh sách đơn | 2.420 (2.345) | 916 (843) | 930 (857) |
| Admin: đơn theo bước "chờ xác nhận" | 2.191 (2.120) | 683 (614) | |
| Admin: đơn theo trạng thái "đã huỷ" | 1.997 (1.927) | 430 (364) | |
| Admin: tìm đơn theo SĐT khách | 1.615 (1.563) | 151 (107) | |
| Admin: danh sách sản phẩm | 177 (70) | 183 (72) | |
| Admin: lưới tồn | **OOM** | OOM (G3) | 214 (123) |
| Admin: tổng quan | **OOM** | OOM (G3) | 648 (592) |
| Sync API: đơn đổi trong 1 ngày, 50 đơn | 55 (20), 251 truy vấn | 13 (2), 6 truy vấn | |
| Báo cáo: sản phẩm bán chạy 30 ngày | 1.165 (1.165) | 617 (616) | |

Nguyên nhân (EXPLAIN ANALYZE):

- **Facet tuỳ chọn** (`ProductSearch::optionFacets`) là truy vấn chậm nhất của mọi listing: nối `variants × variant_option_values × option_values` cho cả tập sản phẩm rồi sắp xếp theo biểu thức `CASE` của `OptionFilterToken` để `GROUP BY`. Không lọc danh mục: 1,85 triệu dòng, sắp xếp 67 s → §4.2, §4.3.
- **Trang sâu** (trang 40): `ORDER BY created_at DESC, id DESC … OFFSET` trên kết quả `whereExists` danh mục, không có index phù hợp → §4.3 (index `product_listing`).
- **Tìm chữ**: `LIKE '%…%'` quét `products.search_text` → §4.1.
- **Danh sách đơn Admin**: truy vấn chậm nhất là `select distinct payment_method from orders` (lấy lựa chọn cho bộ lọc, quét 5 triệu dòng, ~1,5 s) — không thuộc truy vấn danh sách; truy vấn danh sách và đếm theo bước dùng index. *Ngoài kế hoạch ban đầu.*
- **Lưới tồn và Tổng quan**: `EloquentVariantDirectory::searchIndex()` nạp mọi SKU (1 triệu) và mọi giá trị tuỳ chọn (2 triệu) vào PHP để sắp xếp/phân trang trong bộ nhớ; Tổng quan gọi qua việc cần làm "sắp hết hàng". *Ngoài kế hoạch ban đầu* (gần nhất: keyset ở G3).
- **Sync API**: truy vấn `(updated_at, id)` dùng index (0,07 ms) nhưng dựng payload N+1 — 251 truy vấn cho 50 đơn. *Ngoài kế hoạch ban đầu.*
- **Bán chạy**: `GROUP BY json_extract(meta, '$.product_id')` trên 94.963 nhóm qua bảng tạm → §4.5.
- PDP, giỏ, checkout không đổi theo quy mô.

Sau G2 (2026-11-26, cùng dữ liệu `vani:bench:seed`): `ProductSearch` trên read model cho **kết quả giống hệt** bản G1 ở 170 tổ hợp lọc/sắp xếp/trang (ids, total, mọi facet — so trên cùng dữ liệu trước khi bỏ truy vấn cũ). Còn lại:

- Listing toàn catalog (1,1 s): đếm facet tuỳ chọn trên ~665.000 dòng `product_facet_values` của 95.000 sản phẩm. Trang danh mục cụ thể 50–600 ms.
- Danh sách đơn Admin: truy vấn chậm nhất là `select stage, count(*) from orders group by stage` (thẻ đếm theo bước, quét index ~0,5 s ở 5 triệu đơn) — cần bảng đếm hoặc cache nếu thành vấn đề.
- Bán chạy 30 ngày (0,6 s): group ~400.000 dòng đơn theo `product_id` — báo cáo, có thể cache theo ngày.

Sau G3 (2026-11-26): lưới tồn và Tổng quan đọc read model `stock_grid_index` / `stock_grid_options` của Inventory (bản sao dữ liệu Catalog cần cho lưới, đồng bộ qua `ProductListingProjected`; `sellable` cập nhật khi `AvailabilityChanged`) — lọc, sắp xếp, phân trang, đếm bằng SQL. Tổng quan còn chậm vì đếm đơn theo bước (cùng truy vấn với danh sách đơn); "đã giao, chưa thu COD" dùng index mới `orders (order_status, payment_status)` (8,2 s → vài ms). Phần còn lại ghi [reference-parity §6b](reference-parity-refactor.md) L54–L56.
