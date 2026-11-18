# Ổn định trước go-live — kế hoạch theo ưu tiên (2026-11)

> Trạng thái: **In progress** (Owner giao 2026-11-12, Core 0.4.15). Thứ tự làm đúng thứ tự ưu tiên; mỗi đợt một commit, cập nhật cột "Trạng thái" khi xong.
>
> Liên quan: [ADR-044](../19-adr/ADR-044-storefront-api-first.md) (A0–A5 đã xong), [roadmap §4–§5](roadmap.md) (hardening, go-live gate), [reference-parity-refactor §6b](reference-parity-refactor.md) (backlog giới hạn), [production](../18-operations/production.md), [observability](../16-observability/observability.md).

| Ưu tiên | Mảng | Mục tiêu |
|---|---|---|
| **P0** | Hoàn thiện API-first | Hợp nhất API và Blade đến hết, bảo vệ hợp đồng OpenAPI khỏi thay đổi phá vỡ âm thầm, kiểm soát API storefront của plugin |
| **P1** | Ổn định Commerce Core | Checkout, Cart, Inventory, Ordering, Payment, Returns: concurrency và ranh giới transaction |
| **P1** | Production readiness | CI chạy được thật, Redis, backup/restore, logging, monitoring, security, load test |
| **P1** | Vận hành có người | Ba chốt có người: xác nhận đơn COD, đẩy đơn lên hãng, duyệt đổi trả ([ADR-046](../19-adr/ADR-046-staffed-operations-vietnam.md)) — ✅ 0.4.53–0.4.54 |
| **P2** | Trải nghiệm Admin | Catalog, sản phẩm, giá, tồn, đơn, vận hành, báo cáo |
| **P2** | Storefront | Theme, mobile-first, SEO, tốc độ tải, checkout |

Giữ nguyên các ràng buộc đang có: kết nối ngoài đóng băng (GHTK, OAuth, OTP thật, dịch máy, Admin API), chỉ tiếng Việt nhưng giữ cơ chế i18n, SME trước, môi trường dev không cần tương thích ngược, thêm dependency phải được duyệt.

## P0 — Hoàn thiện API-first

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **S0.1 — Một định dạng dữ liệu** | Sửa backlog L29: mọi thời điểm trong Storefront API là ISO-8601 theo múi giờ cửa hàng; Blade tự định dạng hiển thị. Sửa L30: map rỗng ra `{}` (không còn `[]`), schema bỏ `type: [object, array]` | `StorefrontApiContractTest` với `format: date-time` + kiểm múi giờ cửa hàng; Blade/Admin định dạng bằng `StoreClock::format()` | ✅ 0.4.16 — thêm `StoreClock::iso()`; DTO của Ordering/Returns/Fulfillment/Payment cũng theo ISO giờ cửa hàng |
| **S0.2 — Phủ hết hợp đồng** | Contract test gọi thật mọi thao tác trong OpenAPI (đổi trả, đã nhận hàng, thanh toán lại, đăng nhập mật khẩu, xoá tài khoản…); test tự đếm thao tác chưa được gọi | Thao tác có trong OpenAPI mà không có response thật nào được kiểm → đỏ (ngoại lệ ghi lý do: đăng nhập mạng xã hội đang đóng băng) | ✅ 0.4.16 — 55/55 thao tác; social start/complete chỉ kiểm response lỗi |
| **S0.3 — Khoá thay đổi phá vỡ** | Snapshot bề mặt hợp đồng (thao tác, trường bắt buộc, kiểu) cạnh `public-api.snapshot`; đổi snapshot phải tăng version Core và ghi CHANGELOG | Xoá/đổi kiểu một trường trong schema mà không cập nhật snapshot → đỏ | ✅ 0.4.16 — `tests/Architecture/storefront-api.snapshot` (`StorefrontApiSnapshotTest`): ghi snapshot mới khi hợp đồng đổi mà chưa tăng version → bị từ chối; version trong snapshot phải có mục CHANGELOG |
| **S0.4 — API của plugin** | Plugin có `storefrontRoutes()` khai báo OpenAPI riêng (`custom/plugin/<Name>/openapi.json`); test chung đối chiếu route `x/<slug>` với file đó và kiểm phong bì `{data}` / `error.v1` | Plugin thêm route API không khai báo → đỏ | ✅ 0.4.16 — `Extension\Testing\StorefrontApiContract`; `openapi.json` + `schemas/` cho `vani.cms`, `vani.lookbook`, `vani.wishlist`, `vani.hello-world`; arch test bắt buộc; `vani.cms` 1.5.2 trả thời điểm ISO giờ cửa hàng |

## P1 — Ổn định Commerce Core

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **C1 — Nhóm concurrency xanh** | Sửa L28 (`ReturnConcurrencyTest` mất danh mục lý do khi truncate); chạy cả nhóm `--group=concurrency` trên MySQL | Nhóm concurrency xanh trên MySQL 8.x | ✅ — L28: `DatabaseTruncation` xoá bảng tham chiếu do migration chèn; trait `Tests\Support\KeepsReferenceData` giữ `return_reasons`, `notification_templates`. Lưu ý: nhóm `concurrency` bị loại khỏi lần chạy mặc định (`phpunit.xml`) — phải chạy `--group=concurrency` |
| **C2 — Phủ các cuộc đua còn thiếu** | Rà từng luồng ghi của Cart, Checkout, Inventory, Ordering, Payment, Returns; viết test nhiều tiến trình cho cuộc đua chưa có (vd. lượt dùng mã giảm giá, huỷ đơn ↔ callback thanh toán, nhận hàng trả ↔ hoàn tiền, điều chỉnh tồn ↔ đặt hàng) | Mỗi cuộc đua có test; lỗi tìm ra được sửa trong cùng đợt | ✅ — `CancelPaymentRaceConcurrencyTest` (huỷ đơn ↔ IPN: **lỗi thật** — IPN trả lỗi khi khách vừa huỷ, huỷ đơn lỗi khi tiền vừa được hoàn vì thanh toán muộn; đã sửa ở `PaymentService`), `OrderLifecycleConcurrencyTest` (huỷ trùng, webhook "đã giao" trùng, nhận hàng trả/hoàn tiền trùng, kiểm kê ↔ đặt hàng: đều đúng). Lượt dùng mã giảm giá, oversell, IPN trùng, đổi trả trùng đã có từ trước |
| **C3 — Ranh giới transaction** | Rà: event/job/HTTP ra ngoài không chạy trong transaction chưa commit; khoá theo thứ tự cố định để tránh deadlock | Arch/feature test cho các quy tắc rà được | ✅ 0.4.17 — `Shared\Support\ExternalCalls::guard()` trước mọi lời gọi cổng thanh toán/hãng vận chuyển/kênh OTP: ném lỗi ở dev/test khi đang trong transaction chưa commit (cả bộ test thành máy dò — không luồng nào vi phạm), production chỉ cảnh báo; `TransactionBoundaryTest`: domain event đều after-commit, lời gọi ra ngoài đều có guard. Ngoại lệ có lý do: đặt vận đơn giữ khoá khi gọi hãng (backlog L31). Thứ tự khoá chống deadlock đã có test (`ReservationConcurrencyTest`) |

## P1 — Production readiness

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **R1 — CI chạy được thật** | Rà `ci.yml` với code hiện tại (plugin test, contract test, concurrency, type coverage); bước nào chỉ chạy được ở máy dev thì sửa | Chạy đúng các lệnh của CI ở máy dev bằng MySQL riêng | ✅ 2026-11-12 — xanh: clean-room, license, Pint (2 file cũ sai thứ tự import), **Larastan** (32 lỗi thật đã sửa: docblock lệch code — `CatalogReader::searchProducts`, `ShipmentView::$lines`, `LinkType::resolve`, thuộc tính model thiếu `@property`; lỗi thật `(string) $x['q'] ?? ''` ở `vani.cms`/`vani.hello-world`; baseline 81 → 67, không thêm mục), vue-tsc, dnd, build, test SQLite, type coverage 97,5 %, **test MySQL** (ca seeder plugin lỗi bỏ qua trên MySQL: DDL tự commit transaction bọc test — có từ 0.3.53, nghĩa là bước này của CI chưa từng xanh), concurrency, từ điển dữ liệu. `composer audit`: 9 advisory đã xử lý bằng bản vá (Owner duyệt `composer update` symfony/* 8.1.x, livewire 4.4.8). Còn `ScopeTest` đỏ vì README đang sửa |
| **R2 — Redis** | Cấu hình production mẫu dùng Redis cho cache/queue/session/lock; test lock và rate limit chạy với Redis khi có | Tài liệu + `vani:security:check` cảnh báo cấu hình không an toàn cho nhiều node | ✅ 0.4.18 — đã có từ trước: Redis bắt buộc ở production, cảnh báo driver. Thêm: `security:check` **lỗi** khi Redis của queue/session không `noeviction` hoặc không kết nối được; cache tách được sang instance riêng (`REDIS_CACHE_HOST/PORT/PASSWORD`); CI thêm Redis service và chạy cả bộ test với cache + session trên Redis (`TestCase` flush cache Redis giữa test). Máy dev không có Redis nên lượt này chỉ chạy được trên CI |
| **R3 — Backup/restore** | Diễn tập khôi phục đầy đủ (DB + `storage/app`) thành quy trình kiểm được | Test cho `vani:backup:drill`; runbook | ✅ 0.4.18 — phần DB đã có (nạp vào DB tạm, đối chiếu, bằng chứng `backup_drills`, cảnh báo quá hạn). Thêm `--with-files`: diễn tập cả `storage/app` (giải nén + đối chiếu số file); bản chỉ DB → cảnh báo. Runbook production §9 |
| **R4 — Logging & monitoring** | Log JSON có correlation id cho production; metric nghiệp vụ còn thiếu (checkout lỗi, tỷ lệ thanh toán) và cảnh báo tương ứng | Test cảnh báo mới | ✅ 0.4.18 — kênh log `json` (correlation id) cho production; metric `checkout.attempts`/`checkout.errors` (lỗi hệ thống, tách khỏi từ chối nghiệp vụ) + cảnh báo khẩn `checkout.error_rate` (> 2% trong 5 phút) đọc từ bộ đếm theo phút trên cache. Còn mục tiêu: tracing, Prometheus, slow query/deadlock |
| **R5 — Security** | Rà lại [security §5.1](../15-security/security.md) với code hiện tại (phiên cùng nguồn của API, CSRF, rate limit, upload) | `vani:security:check` + test | ✅ 0.4.18 — xem [security §5.1](../15-security/security.md) dòng 2026-11-12: CSRF phiên cùng nguồn có test thật, giới hạn dò mã giảm giá, `npm audit` trong CI, `security:check` bắt Redis eviction. Chưa: CSP (Report-Only trước), pentest bên thứ ba |
| **R6 — Load test** | Kịch bản tải cho duyệt sản phẩm → giỏ → checkout (công cụ ngoài, không thêm dependency vào app) + số liệu mục tiêu | Báo cáo một lần chạy trên máy dev | 🟡 2026-11-12 — `scripts/load/storefront.js` (k6) cập nhật theo hợp đồng sau API-first (bỏ `voucher_codes` khi đặt đơn; thêm gợi ý tìm kiếm, `/store`, beacon lượt xem). Đo đọc trên dev bằng ApacheBench: mọi endpoint trong NFR, 0 lỗi ([operations §9](../18-operations/operations.md)). **Chưa**: chạy k6 đủ kịch bản (gồm đặt đơn) trên staging — máy dev chưa cài k6 và không chạy luồng ghi vào DB dev |

## P1 — Kiểm chứng phát hành Core 0.5.x

> Owner giao 2026-11-17. Chạy trên **macOS** (máy dev Windows thiếu `phpredis`, Playwright, `gh`). Làm lần lượt V1 → V6; đợt nào phải sửa code thì một commit riêng rồi mới sang đợt sau. Mọi bước dùng DB riêng (`vanishop_testing`, `vanishop_upgrade`), không đụng DB dev.

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **V1 — CI GitHub xanh** | Chạy đủ `ci.yml` trên GitHub Actions (quality + tests: SQLite, type coverage, MySQL 8.4, Redis, concurrency, trình duyệt, từ điển dữ liệu) | Lần chạy Actions của commit mới nhất trên `main` xanh cả hai job | ✅ 2026-11-17 — Actions xanh cả hai job ở `9e643ba2` ([run #13](https://github.com/ptdat97/vanishop/actions/runs/44096493053)). Lần chạy đầu (#12, `268521d0`) đỏ ở bước SQLite: `FirstPartySessionTest` ghi cứng host `vanishop.test` trong khi CI dùng `APP_URL` của `.env.example` — tái hiện bằng container Linux (Docker), sửa test lấy host từ `config('app.url')`. Tại máy (macOS): chạy y hệt `ci.yml` tại máy, MySQL 8.4.7 riêng (cổng 3307), Redis 7: clean-room, license, Pint, Larastan 0 lỗi, `npm audit`, vue-tsc, dnd/admin, build, SQLite 1629 ✓ / 3 skip, type coverage 97,1 %, MySQL 8.4 1631 ✓ / 1 skip, Redis 1629 ✓, concurrency 14/14, trình duyệt 25/25, từ điển dữ liệu — xanh sau khi sửa ở **0.5.10**: **lỗi thật trên Redis** (`/health` luôn 503 vì Redis trả số nguyên dạng chuỗi; circuit breaker Integration không bao giờ mở — bước Redis của CI chưa từng chạy nên chưa ai thấy), từ điển dữ liệu thiếu 3 bảng ngày 11-16, `VariantOptionsSnapshotTest` so thứ tự khoá JSON (MySQL sắp lại), 3 test `travel()` vượt TTL Redis → `TestCase::useArrayCache()`. `composer audit`: CVE-2026-128083 → Owner duyệt nâng `laravel/framework` 13.34.0 → 13.42.0 (chỉ gói này), chạy lại cả bộ SQLite/MySQL/Redis/concurrency/trình duyệt/rollback đều xanh. |
| **V2 — Regression tiền & hàng** | Payment, Refund, Returns, Fulfillment: chạy riêng nhóm test của 4 module + invariant + concurrency liên quan, trên SQLite và MySQL | Không đỏ; số test không giảm so với lần trước | ✅ 2026-11-17 — Payment, Returns, Fulfillment, Invariants, `NativeReturnsTest`: 147/147 trên SQLite và MySQL 8.4; `tests/Concurrency` 14/14 trên MySQL 8.4. Mốc số test cho lần sau: 147 |
| **V3 — MySQL 8.4 thực tế** | Cả bộ test + nhóm concurrency trên MySQL 8.4 (không phải SQLite), `migrate:fresh` + seed demo, `vani:docs:schema --check` | Xanh; từ điển dữ liệu khớp schema | ✅ 2026-11-17 — MySQL **8.4.7** (không dùng 9.7 của máy dev): cả bộ 1631 ✓ / 1 skip, concurrency 14/14, `migrate:fresh` + `DemoSeeder` sạch, `vani:docs:schema --check` khớp (sau khi sinh lại ở 0.5.10) |
| **V4 — Nâng cấp 0.4.x → 0.5.x** | Dựng DB có dữ liệu ở bản 0.4.69 (`a738839f`, commit cuối trước 0.5.0), rồi nâng code lên `main` và migrate | `migrate` không lỗi, không còn migration treo; `vani:plugin:doctor` sạch; đối soát tồn/thanh toán không chênh; Admin + storefront mở được, đặt được đơn | ✅ 2026-11-17 — DB 0.4.69 (`vani:install` + `DemoSeeder` + cài/bật thêm 15 plugin → đủ 29) → code `main`: không có migration Core mới từ 0.4.69 (`Nothing to migrate`, 0 treo); doctor không lỗi tương thích; số đơn/tiền/tồn trước–sau khớp hoàn toàn, `inventory:verify`/`payment:verify` 0 chênh. Admin + storefront 200; qua HTTP thật: đặt đơn COD qua Storefront API → xác nhận → đặt vận đơn → lấy hàng → giao (thu COD) → yêu cầu trả → duyệt → nhận hàng (tồn +1) → hoàn 990.000đ; đối soát lại 0 chênh |
| **V5 — 29 plugin sau khi nâng Core** | Với DB của V4: `vani:plugin:upgrade` từng plugin, `vani:plugin:doctor`, `vani:plugin:health`, test suite `Plugins` | Mọi plugin tương thích Extension API hiện hành (`requires.extension_api`), không lỗi migration, test plugin xanh | ✅ 2026-11-17 — DB của V4: 29/29 plugin cài và bật; `plugin:upgrade` nâng `vani.provinces-vn` 1.0.0→1.1.0, `vani.reports` 1.1.0→1.2.0, 27 plugin còn lại đã đúng version (lệnh trả mã 1 — backlog L38, sửa ở 0.5.22: nay mã 0); manifest chuyển `requires.vanishop` → `requires.extension_api: ^1.0`, không plugin nào không tương thích. Doctor/health chỉ còn `health_error` của `vani.sms-brandname`, `vani.zalo-zns` (chưa có khoá — đang đóng băng kết nối ngoài) và cảnh báo `data_undeclared` của `vani.cost-price`. Suite `Plugins` 339/339 trên SQLite và MySQL 8.4 |
| **V6 — Migration, backup, restore** | Mọi migration có `down()` chạy được (`migrate:rollback` toàn bộ rồi `migrate` lại trên DB trống); `vani:backup:drill --fresh --with-files` | Rollback/migrate lại không lỗi; drill ghi bằng chứng `backup_drills` khớp số dòng và số file | ✅ 2026-11-17 — **sửa ở 0.5.11**: `migrate:rollback` toàn bộ dừng ở `create_variants_table` vì `create_option_tables` (ADR-045) có `down()` rỗng → nay dựng lại cấu trúc màu × size (đã có biến thể thì dừng, quay lại bằng backup); `add_variant_options_to_order_lines` có `down()` thật. Rollback toàn bộ → migrate lại và lùi 6 bước → migrate lại đều sạch, từ điển khớp; CI chạy thêm vòng này. `vani:backup:drill --fresh --with-files` trên DB của V4 (không đụng DB dev): 14 bảng khớp số dòng, file 62/62, bằng chứng `backup_drills` #1 `ok`; `backup:list` healthy |

### Chạy trên macOS

Chuẩn bị một lần (Homebrew):

```bash
brew install php@8.4 composer node@20 mysql@8.4 redis gh
pecl install redis                      # phpredis cho bước test Redis
brew services start mysql@8.4 && brew services start redis
gh auth login
git clone https://github.com/ptdat97/vanishop.git && cd vanishop
composer install && npm ci && npx playwright install chromium
cp .env.example .env && php artisan key:generate && npm run build
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS vanishop_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Biến môi trường dùng lại (cùng giá trị với CI):

```bash
export MYSQL_ENV="DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=vanishop_testing DB_USERNAME=root DB_PASSWORD="
```

#### V1 — CI GitHub

```bash
gh run list --branch main --limit 5            # lần chạy của commit mới nhất
gh run watch                                   # theo dõi lần đang chạy
gh run view --log-failed                       # log bước đỏ
gh workflow run CI --ref main                  # chạy lại khi cần (workflow cần `workflow_dispatch`; không có thì push commit mới)
```

Chạy lại y hệt CI tại máy (khi Actions đỏ, để sửa nhanh):

```bash
./scripts/ci/clean-room-check.sh
composer licenses --no-dev --format=json | php scripts/ci/license-check.php
vendor/bin/pint --test
php -d memory_limit=2G vendor/bin/phpstan analyse --no-progress
composer audit --no-dev && npm audit --omit=dev
npx vue-tsc --noEmit -p tsconfig.json && npm run test:dnd && npm run test:admin && npm run build
php -d memory_limit=-1 vendor/bin/pest --compact            # SQLite (cả bộ cần > 512 MB)
vendor/bin/pest --type-coverage --min=97
env $MYSQL_ENV vendor/bin/pest --compact                    # MySQL 8.4
CACHE_STORE=redis SESSION_DRIVER=redis REDIS_HOST=127.0.0.1 REDIS_CLIENT=phpredis vendor/bin/pest --compact
env $MYSQL_ENV vendor/bin/pest --compact --group=concurrency
vendor/bin/pest --compact --group=browser
env $MYSQL_ENV sh -c 'php artisan migrate:fresh --force --no-interaction && php artisan vani:docs:schema --check'
```

#### V2 — Regression Payment, Refund, Returns, Fulfillment

Refund nằm trong Payment + Returns: `PaymentFlowTest`, `AuthorizeCaptureTest`, `PaymentVerifyTest`, `ReturnFlowTest`, `ExchangeTest`…

```bash
for db in sqlite mysql; do
  [ $db = mysql ] && E="$MYSQL_ENV" || E=""
  env $E vendor/bin/pest --compact modules/Payment/Tests modules/Returns/Tests modules/Fulfillment/Tests \
    tests/Feature/Invariants modules/Storefront/Tests/Feature/NativeReturnsTest.php
done
env $MYSQL_ENV vendor/bin/pest --compact --group=concurrency tests/Concurrency   # huỷ ↔ IPN, hoàn tiền trùng, đổi trả trùng
```

#### V3 — MySQL 8.4 thực tế

Các lệnh MySQL ở V1 (cả bộ, concurrency, từ điển dữ liệu), thêm dữ liệu demo:

```bash
env $MYSQL_ENV sh -c 'php artisan migrate:fresh --force && php artisan db:seed --class=DemoSeeder --force'
```

#### V4 — Nâng cấp 0.4.69 → `main`

Worktree riêng, DB riêng:

```bash
mysql -uroot -e "DROP DATABASE IF EXISTS vanishop_upgrade; CREATE DATABASE vanishop_upgrade CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
export UPG_ENV="DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_DATABASE=vanishop_upgrade DB_USERNAME=root DB_PASSWORD="
git worktree add ../vanishop-0.4 a738839f && cd ../vanishop-0.4
composer install && cp ../vanishop/.env .env
env $UPG_ENV php artisan vani:install --no-interaction
env $UPG_ENV php artisan db:seed --class=DemoSeeder --force            # dữ liệu như cửa hàng đang chạy
cd ../vanishop                                                         # code main
env $UPG_ENV php artisan migrate --force && env $UPG_ENV php artisan migrate:status | grep -i pending
env $UPG_ENV php artisan vani:plugin:doctor
env $UPG_ENV php artisan vani:inventory:verify && env $UPG_ENV php artisan vani:payment:verify
git worktree remove ../vanishop-0.4
```

Sau đó mở Admin + storefront với `$UPG_ENV` (`env $UPG_ENV php artisan serve`), đặt một đơn COD, xác nhận, giao, trả hàng.

#### V5 — 29 plugin

Tiếp DB của V4:

```bash
for id in $(php -r 'foreach (glob("custom/plugin/*/vanishop.json") as $f) echo json_decode(file_get_contents($f), true)["id"], PHP_EOL;'); do
  env $UPG_ENV php artisan vani:plugin:upgrade "$id" || echo "LỖI: $id"
done
env $UPG_ENV php artisan vani:plugin:doctor && env $UPG_ENV php artisan vani:plugin:health
vendor/bin/pest --compact --testsuite=Plugins && env $MYSQL_ENV vendor/bin/pest --compact --testsuite=Plugins
```

#### V6 — Migration, backup, restore

```bash
env $MYSQL_ENV sh -c 'php artisan migrate:fresh --force && php artisan migrate:rollback --force --step=1000 && php artisan migrate --force'
php artisan vani:backup:drill --fresh --with-files            # DB dev; xem bằng chứng ở bảng backup_drills
php artisan backup:list
```

Ghi kết quả từng đợt vào cột "Trạng thái" ở bảng trên (✅ + ngày + ghi chú lỗi đã sửa).

## P2 — Trải nghiệm Admin

Khảo sát 2026-11-12: sửa nhanh giá/tồn ngay trong danh sách sản phẩm, thao tác hàng loạt cho đơn/vận đơn/đổi trả/tồn/giá và báo cáo doanh thu theo ngày/sản phẩm/thương hiệu/kênh/cổng **đã có**. Việc P2 Admin ưu tiên khoảng trống khi cửa hàng lớn lên và thao tác hằng ngày còn chậm.

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **A1 — Admin không cắt dữ liệu âm thầm** | Lưới tồn, Tổng quan "hết/sắp hết", "chọn mọi kết quả lọc", xuất CSV giới hạn 5.000 biến thể mà không báo (backlog L20/L26) — shop 300 mẫu × 16 màu/size đã chạm | Test 6.000 biến thể: mọi màn hình thấy đủ, số truy vấn không tăng theo số biến thể | ✅ 0.4.19 — `VariantDirectory::searchIndex()` + `StockQueries::sellableByVariant()`; sửa hàng loạt vượt mức → báo thu hẹp bộ lọc |
| **A2 — Danh sách đầy đủ** | Vận đơn, Thanh toán, Đổi trả chỉ hiện 100 bản ghi mới nhất, không phân trang (shop 30 đơn/ngày: sau 4 ngày vận đơn cũ không mở được từ danh sách); tra số đơn từng dòng (N+1) | Test 31 bản ghi: 30 + 1, đổi số dòng, lọc giữ khi chuyển trang; số truy vấn không tăng theo số dòng | ✅ 0.4.20 — phân trang server 30/50/100 như danh sách đơn; `OrderReader::numbers()`. Còn: mã giảm giá của một khuyến mãi hiện tối đa 200 (backlog L32) |
| **A3 — Bảng giá thao tác gọn** | Đánh giá 2026-11-15: đưa 40 mẫu vào đợt sale ≈ 200 thao tác (nhập giá từng mã sản phẩm, giá cố định, không chọn nhiều, không xem trước). Đợt 1: trang làm việc của bảng giá + áp giá hàng loạt (giảm %, số tiền, đồng giá) có xem trước, cảnh báo không chặn (giá sale được về 0đ). Đợt 2: form tạo còn 3 ô (Sale/Thành viên, tên, khung giờ; mã + priority tự sinh), chặn tạo thêm bảng `base`, bảng niêm yết chỉ xem, "Đưa vào bảng giá sale…" từ danh sách sản phẩm (lọc danh mục/thương hiệu/bộ sưu tập). Đợt 3: nhập/xuất file; xử lý bảng `base` thứ hai đang có | Ví dụ trên còn ≈ 8 thao tác; test tính giá, xem trước/áp/gỡ, quyền, trình duyệt luồng thêm sản phẩm → giảm 30% → áp | ✅ đợt 1 ✅ 0.4.55 — `PriceListWorkspace`, `BulkPriceRule`, `tests/Browser/PriceListPricesTest`; đợt 2 ✅ 0.4.56 — form tạo 3 ô, một bảng niêm yết, niêm yết chỉ xem, "Đưa vào bảng giá…" từ danh sách sản phẩm (lọc danh mục/thương hiệu dùng bộ lọc sẵn có của danh sách sản phẩm); đợt 3 ✅ 0.4.57 — xuất/nhập file (`PriceListFile`, `SpreadsheetRows` dùng chung với nhập mã vận đơn), cảnh báo + sửa bảng niêm yết thừa |
| **A4 — Catalog** | Owner chọn 2026-11-17: (1) danh sách sản phẩm lọc theo danh mục/thương hiệu/bộ sưu tập + gán hàng loạt (danh mục, thương hiệu, bộ sưu tập, xoá); (2) nhân bản sản phẩm thành bản nháp; (3) nhập/xuất sản phẩm bằng file có xem trước; (4) báo sản phẩm thiếu dữ liệu (ảnh, giá, khối lượng, mô tả, danh mục) | Test lọc + hàng loạt (quyền, chọn mọi kết quả lọc), nhân bản (SKU/slug mới, không copy tồn), nhập file (xem trước → ghi, dòng lỗi), bộ lọc thiếu dữ liệu | ✅ 0.5.13 — `ProductBulkEditor` (lọc danh mục gồm danh mục con/thương hiệu/bộ sưu tập; thêm/gỡ danh mục, đổi thương hiệu, thêm/gỡ bộ sưu tập, xoá bản nháp), `ProductDuplicator` (+ event `ProductDuplicated` → Pricing chép giá niêm yết), nhãn + bộ lọc thiếu dữ liệu, `ProductFile` (xuất/nhập .xlsx/.csv có xem trước, cột giá qua `VariantFieldProvider`). Giá thiếu báo ở A5 |
| **A5 — Pricing** | Sửa giá niêm yết hàng loạt theo bộ lọc (không giới hạn 200 mẫu đã chọn) có xem trước; lịch sử giá theo SKU (`price_history` đã ghi, chưa có màn hình); tổng quan giá đang chạy (đợt sale đang chạy/sắp bắt đầu/sắp hết, SKU thiếu giá, bán dưới giá vốn khi có `vani.cost-price`). Không làm giá sỉ — ngoài phạm vi ([ADR-033](../19-adr/ADR-033-out-of-scope.md)) | Test theo bộ lọc, xem trước = ghi thật, lịch sử đúng nguồn/người đổi | ✅ 0.5.14 — "Chọn tất cả N sản phẩm khớp bộ lọc" (tới 2000) cho mọi thao tác hàng loạt + "Đổi giá" có xem trước (`preview_url`, cảnh báo qua hook); Lịch sử giá theo SKU (`StaffNames` cho tên người đổi); tổng quan giá (`PriceOverview`) — cảnh báo giá vốn chỉ kiểm 500 giá của đợt đang chạy |
| **A6 — Nhập hàng (thao tác SME)** | Phiếu nhập nhanh trong Core: chọn/quét SKU + số lượng (+ nhà cung cấp, ghi chú dạng chữ), tạo dòng từ file → xác nhận là cộng tồn, lịch sử biến động ghi số phiếu. Nghiệp vụ nâng cao (đơn mua nhiều bước, nhận một phần, giá vốn bình quân, gợi ý nhập) để plugin — backlog L41 | Test phiếu nháp/xác nhận một lần, idempotent, tồn + biến động đúng, dòng file sai mã bị báo | ✅ 0.5.15 — `StockReceiptService` + trang Nhập hàng (quét/dán/file, nháp có khoá lạc quan, nhập kho một lần với biến động `receive`, trừ hàng đang về); test trình duyệt luồng quét liên tục (tìm ra và sửa 2 lỗi: ô quét xoá mất mã kế tiếp, Enter bị chặn khi nút Thêm disable). Chưa đọc barcode (chỉ SKU) — `VariantDirectory` chưa tra theo barcode |
| **A7 — Tồn kho: kiểm kê nhanh** | Phiên kiểm kê theo kho/danh mục: nhập hoặc quét số đếm, xem chênh lệch, duyệt mới ghi tồn; hàng bán trong lúc đếm được trừ đúng (ghi theo chênh lệch, không ghi đè). Thẻ kho, hàng chậm bán để plugin — backlog L41 | Test chênh lệch, duyệt, bán trong lúc đếm, quyền | ✅ 0.5.16 — `StockCountService` + trang Kiểm kê (quét lưu ngay theo hàng đợi, xem chênh, áp dụng phần chênh một lần); test bán trong lúc đếm và trình duyệt quét liên tiếp. Chưa khoanh vùng theo danh mục (phiên theo kho + ghi chú khu vực; `VariantDirectory` chưa lọc theo danh mục) |
| **A8 — Đơn hàng** | Tạo đơn trong Admin (đơn qua điện thoại/Zalo/Facebook: khách theo SĐT, SKU, phí giao, giảm tay, COD/chuyển khoản; cùng luật giữ hàng/giá như checkout, nguồn `admin`); xuất đơn ra Excel theo bộ lọc (theo đơn hoặc theo dòng SKU) | Test tạo đơn (giữ hàng, giá, tổng, quyền), xuất theo bộ lọc | ✅ 0.5.17 — Tạo đơn trong Admin (`AdminOrders`, đi qua `Checkout::quote/placeOrder` nguồn `admin`, chống trùng bằng khoá idempotency của form; test trình duyệt), xuất Excel theo bộ lọc (theo đơn / theo dòng SKU, audit). Chưa: giảm giá tay, tra khách theo SĐT để điền sẵn địa chỉ (đơn vẫn tự gắn khách theo SĐT) |
| **A9 — Đối soát COD** | Nhập bảng kê COD của hãng (mã vận đơn, tiền thu hộ, phí): xem trước → khớp thì ghi "đã thu" (idempotent theo tham chiếu), lệch tiền/không tìm thấy vào "Đối soát lệch" (`Reconciliations`, EP v3 X5) | Test khớp, lệch, trùng file, idempotent | ✅ 0.5.18 — `Giao hàng → Vận đơn → Nhập bảng kê COD` (module Fulfillment, `CodStatement`): nhận diện cột theo tên, xem trước từng dòng, áp dụng ghi sổ đối soát nguồn `cod_statement` + `Payments::collectCod` khi chưa thu; lệch vào "Đối soát lệch"; nhập lại không ghi lần hai. `Reconciliations::recorded` (Extension API 1.2.0). Giới hạn: backlog L42 |
| **A10 — Báo cáo** | Thêm vào `vani.reports`: lợi nhuận gộp theo ngày/sản phẩm (giá vốn từ `vani.cost-price`, SKU thiếu giá vốn báo rõ); đổi trả/hoàn hàng (tỷ lệ trả theo sản phẩm, lý do; giao thất bại COD) | Test số liệu trên dữ liệu mẫu, xuất file | ✅ 0.5.19 — `vani.reports` 1.3.0: lợi nhuận gộp theo ngày / theo sản phẩm (giá vốn qua hook mới `vani.pricing.unit_costs`, `vani.cost-price` 1.1.0 cấp; SKU thiếu giá vốn ghi rõ, tách doanh thu, không coi = 0), đổi trả theo sản phẩm (tỷ lệ so với số bán cùng kỳ), lý do trả & COD giao thất bại; CSV qua Core. Contract mới `OrderStatistics::variantSales`, `ReturnStatistics`, `DeliveryStatistics` (Extension API 1.3.0). Giới hạn: backlog L43 |

## P2 — Storefront

Đề xuất 2026-11-18, Owner duyệt làm S1 trước: đo và khoá mốc trước khi sửa giao diện ở S2–S4.

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **S1 — Rào chắn mobile & tốc độ** | Test trình duyệt ở 375px cho các trang chính: không cuộn ngang, ảnh có kích thước, ngân sách JS/CSS lúc mở trang, ảnh màn hình đầu không lazy, hiệu ứng không ẩn màn hình đầu | Theme/plugin làm vượt → đỏ | ✅ 0.5.23 — `StorefrontMobileBudgetTest`. Đo lần đầu tìm ra hai lỗi thật: thư viện Motion (176 KB, ~60 KB gzip) tải ở trang chủ/danh mục/tìm kiếm chỉ để làm hiệu ứng hiện dần và ẩn cả nội dung màn hình đầu (gồm ảnh hero) tới khi tải xong → thay bằng CSS + `IntersectionObserver`, JS trang danh sách 210 → 34 KB; hàng ảnh đầu của trang danh sách bị `lazy` → `$eager` cho lưới sản phẩm. Không cuộn ngang, không ảnh thiếu kích thước. Chưa đo LCP/CLS thật (cần máy cấu hình như production) |
| **S2 — Checkout trên điện thoại** | Khách đăng nhập điền sẵn địa chỉ đã lưu; lỗi hiện cạnh ô, giữ dữ liệu đã gõ; nút đặt hàng + tổng tiền luôn thấy; tìm phường/xã gõ không dấu | Test feature + trình duyệt luồng đặt COD trên mobile | ✅ 0.5.24 — `CheckoutForm` (điền sẵn từ hồ sơ + địa chỉ mặc định, ô "Địa chỉ đã lưu", tóm tắt lỗi đầu trang có link tới ô, `aria-invalid`), thanh tổng + "Đặt hàng" cố định đáy trên điện thoại, đổi phường/phương thức → tính lại và thay khung tóm tắt (bản server render), lọc phường/xã gõ không dấu (cả sổ địa chỉ). Rà ra hai lỗi thật: lỗi chung (tổng đã đổi, hết hàng…) chỉ hiện nhờ JS — tắt JS bấm Đặt hàng không thấy gì; đổi địa chỉ/phương thức không tính lại phí giao (plugin hãng tính theo địa chỉ → tổng cũ). Test trình duyệt bắt thêm lỗi trong lúc làm: `form.action` bị nút `name="action"` che. `CheckoutFormTest`, `CheckoutMobileTest`. Chưa: tóm tắt đơn thu gọn đầu trang trên điện thoại |
| **S3 — SEO** | Dữ liệu cấu trúc trang danh sách (breadcrumb, danh sách sản phẩm), thông tin cửa hàng ở trang chủ; sản phẩm đã xoá/đổi slug (404/410/301); mô tả mặc định khi trống | Test từng trường hợp | Chưa làm |
| **S4 — Ảnh (L23)** | `srcset` cho ảnh bìa bài viết CMS, logo thương hiệu, banner danh mục | Test render | Chưa làm |
