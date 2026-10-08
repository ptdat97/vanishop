# Vận hành production

> Trạng thái: **Implemented** cho những gì có trong mã nguồn hiện tại (Core 0.3.28). Kiến trúc mục tiêu (nhiều web node, CDN, Meilisearch…) và chính sách RPO/RTO: [operations](operations.md). Giám sát: [observability](../16-observability/observability.md).
>
> Tài liệu này dành cho người cài đặt, deploy và trực vận hành website. Mỗi lệnh ở đây đều có trong mã nguồn; mục nào chưa làm được ghi rõ ở §12.

## 1. Thành phần cần chạy

| Thành phần | Bắt buộc | Ghi chú |
|---|---|---|
| Nginx + PHP-FPM 8.4 | ✅ | Ext: `pdo_mysql`, `redis` (phpredis), `mbstring`, `intl`, `bcmath`, `zip`, `exif`, và `imagick` **hoặc** `gd` có WebP |
| MySQL 8.4 | ✅ | Managed tại VN nếu có ([ADR-018](../19-adr/ADR-018-infrastructure-vietnam.md)); `utf8mb4` |
| Redis | ✅ | Cache, session, queue, lock của scheduler (`onOneServer`) |
| Horizon (queue worker) | ✅ | `php artisan horizon` dưới supervisor — §6 |
| Scheduler | ✅ | Cron mỗi phút `php artisan schedule:run` — §6 |
| Object storage S3-compatible | Khuyến nghị | Ảnh catalog (`VANI_MEDIA_DISK=s3`) + bucket backup riêng |
| Node.js | Chỉ lúc build | `npm ci && npm run build` trên CI, không cần trên server |

## 2. Biến môi trường production

Bắt đầu từ `.env.example`, đổi/thêm các giá trị sau. **Sau khi `config:cache`, `env()` ngoài thư mục `config/` trả null** — mọi cấu hình phải đi qua file config.

| Biến | Giá trị production | Vì sao |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | Debug bật = lộ stack trace, biến môi trường |
| `APP_KEY` | `php artisan key:generate --show` một lần, lưu trong kho bí mật | Đổi key = mất session, dữ liệu mã hoá (secret plugin trong Cấu hình) không giải mã được |
| `APP_URL` | `https://ten-mien` | URL ảnh, email, callback cổng thanh toán |
| `LOG_LEVEL` | `info` (hoặc `warning`) | |
| `DB_*` | User riêng, chỉ quyền trên DB của app | |
| `CACHE_STORE` / `SESSION_DRIVER` / `QUEUE_CONNECTION` | `redis` / `redis` / `redis` | Nhiều web node dùng chung; Horizon cần queue Redis |
| `SESSION_SECURE_COOKIE` | `true` | Cookie chỉ gửi qua HTTPS |
| `VANI_TRUSTED_PROXIES` | IP/CIDR của load balancer/CDN, hoặc `*` nếu app chỉ nhận traffic qua LB | Thiếu → IP khách là IP của LB (allowlist Admin, giới hạn OTP sai) và app không biết request là HTTPS |
| `VANI_ADMIN_PATH` | Chuỗi khó đoán, vd. `quan-tri-8f3k` | [ADR-020](../19-adr/ADR-020-admin-path-no-2fa.md) |
| `VANI_ADMIN_IP_ALLOWLIST` | IP văn phòng/VPN, phân tách dấu phẩy | Trống = mọi IP vào được trang đăng nhập Admin |
| `VANI_HEALTH_TOKEN` | Chuỗi ngẫu nhiên | `GET /health` chỉ trả chi tiết khi header `X-Health-Token` khớp |
| `VANI_MEDIA_DISK` + `AWS_*`, `AWS_ENDPOINT` | `s3` + endpoint nhà cung cấp VN | Web node stateless |
| `VANI_BACKUP_DISKS` / `VANI_BACKUP_S3_BUCKET` | `s3_backup` / bucket riêng, khác vùng | Backup không nằm cùng máy với DB |
| `BACKUP_ARCHIVE_PASSWORD` | Chuỗi mạnh | File backup chứa dữ liệu khách |
| `MAIL_*` | SMTP/dịch vụ email thật | Email đơn hàng, cảnh báo backup, cảnh báo vận hành |
| `VANI_ALERT_EMAILS` | Email người trực, phân tách dấu phẩy | Nhận cảnh báo tự động (`vani:alerts:check`); trống = chỉ ghi log và hiện trên Tổng quan Admin |
| `VNPAY_SANDBOX` | `false` (khi đã có hợp đồng) | Cùng `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET` thật |
| `VANI_PLUGINS_SAFE_MODE` | `false` | Chỉ bật khi xử lý sự cố — §10 |
| `APP_SCHEDULE_TIMEZONE` | Giữ mặc định `Asia/Ho_Chi_Minh` | Lịch hằng đêm (backup 02:00, đối soát 03:30/04:00) theo giờ VN; dữ liệu vẫn lưu UTC |

Không đặt `VANI_OTP_LOG_SENDER=true` ở production (ghi OTP ra log).

## 3. Bố cục thư mục trên server

Deploy theo thư mục release + symlink (Deployer/Envoyer/script tương đương) để không downtime:

```text
/var/www/vanishop/
├── current -> releases/20261008-1030      # Nginx trỏ vào current/public
├── releases/20261008-1030/                # mã nguồn + vendor + public/build của bản này
└── shared/
    ├── .env                               # symlink vào mỗi release
    ├── storage/                           # symlink: release/storage → shared/storage
    └── public-cache/                      # symlink: release/public/cache → shared/public-cache
```

- `storage/` dùng chung giữa các release: log, file tạm, ảnh khi `VANI_MEDIA_DISK=public`, backup local.
- `public/cache` (ảnh thu nhỏ) nên dùng chung; nếu để riêng từng release thì mỗi lần deploy cache trống và ảnh được resize lại dần (chạy `vani:media:cache --warm` để tạo trước).
- `bootstrap/cache/` **không** dùng chung: mỗi release tự sinh bằng `php artisan optimize` (§5).
- Chạy container: mount `storage/` và `public/cache` thành volume; image build sẵn `vendor/` và `public/build`.

## 4. Cài đặt lần đầu

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate                # chỉ lần đầu, rồi cất APP_KEY vào kho bí mật
php artisan vani:install                # migrate + cài/bật plugin hệ thống (COD, chuyển khoản, phí giao, thuế, tỉnh thành)
php artisan vani:staff:create-owner     # tài khoản Owner đầu tiên
php artisan storage:link                # chỉ khi VANI_MEDIA_DISK=public
php artisan optimize                    # config/route/view/event cache + cache trạng thái plugin
php artisan vani:plugin:doctor          # phải sạch lỗi (mã thoát 0)
```

Bật thêm plugin nghiệp vụ (cổng thanh toán, hãng vận chuyển, `vani.media-webp`…) bằng `vani:plugin:install <id>` rồi `vani:plugin:enable <id>`, cấu hình ở Admin → Cấu hình.

**Không chạy ở production:** `migrate:fresh`, `migrate:refresh`, `db:seed`, `vani:demo:catalog`, và không cài plugin `vani.demo-catalog`.

## 5. Quy trình mỗi lần deploy

Thứ tự quan trọng. CI đã chạy test, pint, phpstan và build asset ([testing §8](../17-testing/testing.md)).

```bash
# Trong thư mục release mới (chưa trỏ current vào)
composer install --no-dev --optimize-autoloader --no-interaction
# public/build lấy từ artifact CI (npm ci && npm run build)
ln -sfn ../../shared/.env .env
rm -rf storage && ln -s ../../shared/storage storage
rm -rf public/cache && ln -s ../../../shared/public-cache public/cache

php artisan migrate --force             # migration Core: chỉ expand/contract (§8)
php artisan vani:plugin:doctor          # báo plugin cần nâng version / lỗi tương thích
php artisan vani:plugin:upgrade <id>    # cho từng plugin doctor báo cần nâng (chạy migration của plugin)
php artisan optimize                    # BẮT BUỘC — gồm vani:plugin:cache (xem lưu ý 1)
php artisan vani:security:check         # mã thoát 1 = cấu hình production sai (APP_DEBUG, cookie secure, …) → dừng deploy

ln -sfn releases/<mới> current          # chuyển traffic
sudo systemctl reload php8.4-fpm        # xoá OPcache của bản cũ
php artisan horizon:terminate           # supervisor khởi động lại Horizon với mã mới
php artisan vani:plugin:doctor && curl -fsS -H "X-Health-Token: $TOKEN" https://ten-mien/health
```

### Lưu ý khi deploy

1. **Plugin chỉ được nạp từ `bootstrap/cache/vanishop-plugins.php`.** File này ghi khi install/enable/disable, nên một release mới (thư mục hoặc container mới) chưa có nó → **không plugin nào được nạp**: mất COD, cổng thanh toán, phí giao, thuế… mà không báo lỗi lúc boot. `php artisan optimize` đã gồm `vani:plugin:cache` (dựng lại từ DB); có thể chạy riêng `php artisan vani:plugin:cache`. `optimize:clear` **không** xoá file này. Không xoá thủ công `bootstrap/cache/vanishop-plugins.php` trên server đang chạy.
2. **Đổi `.env` phải chạy lại `php artisan optimize`** (config đã cache); sau đó reload PHP-FPM và `horizon:terminate`.
3. **Queue worker giữ mã cũ trong bộ nhớ**: luôn `php artisan horizon:terminate` sau khi chuyển `current`, nếu không job sẽ chạy code cũ với schema mới.
4. **Migration chạy TRƯỚC khi chuyển traffic** nên bản cũ phải chạy được với schema mới: thêm cột nullable/bảng mới trước, xoá cột ở release sau (expand/contract). Không đổi tên cột trong một bước.
5. **Plugin có migration riêng**: `migrate` không chạy migration plugin. Nâng code plugin xong phải `vani:plugin:upgrade <id>`; `vani:plugin:doctor` liệt kê plugin cần nâng và trả mã thoát 1 khi có lỗi — dùng làm cổng chặn deploy.
6. **Scheduler chỉ chạy trên một server** (`onOneServer`, cần `CACHE_STORE=redis` dùng chung). Cron có thể đặt trên mọi node nhưng cache phải chung, không thì lệnh chạy trùng.
7. **Nginx phải chuyển request `/cache/` chưa có file về Laravel** (§7). Cấu hình kiểu `location ~* \.(jpg|png|webp)$ { try_files $uri =404; }` áp lên `/cache/` làm ảnh thu nhỏ không bao giờ được tạo (storefront mất ảnh). Sau khi bật/tắt `vani.media-webp` hoặc đổi chất lượng: `php artisan vani:media:cache --clear`.
8. **Callback/webhook phải vào được từ Internet**, không bị allowlist Admin hay WAF chặn: `/api/payments/{gateway}/callback` (IPN cổng thanh toán, GET+POST), `/api/shipping/{carrier}/webhook`, `/api/integration/v1/*` (ERP/POS, xác thực HMAC). URL đăng ký với VNPay/GHN phải là `https://` theo `APP_URL`.
9. **Đổi `VANI_ADMIN_PATH`** làm mất đường dẫn cũ (kể cả Horizon/Pulse dưới `/{admin}/system/...`); báo nhân viên trước.
10. **Không đổi `APP_KEY`** trên hệ thống đang chạy: secret plugin lưu mã hoá trong Cấu hình sẽ không đọc được.
11. **Upload ảnh tối đa 10 MB/ảnh**: đặt `client_max_body_size` (Nginx) và `upload_max_filesize`/`post_max_size` (PHP) ≥ 12M nếu upload nhiều ảnh một lần thì lớn hơn.
12. **Đóng băng deploy** 48 giờ trước và trong các đợt sale lớn ([operations §9](operations.md)).

## 6. Tiến trình nền

**Horizon** (supervisor):

```ini
[program:vanishop-horizon]
command=php /var/www/vanishop/current/artisan horizon
user=www-data
autostart=true
autorestart=true
stopwaitsecs=120
stdout_logfile=/var/www/vanishop/shared/storage/logs/horizon.log
```

Queue đang dùng: `notifications`, `fulfillment`, `default`, `search` (`config/horizon.php`, production tối đa 10 process). Dashboard: `/{VANI_ADMIN_PATH}/system/horizon` (quyền `system.monitor`).

**Scheduler** (crontab của user chạy PHP):

```cron
* * * * * cd /var/www/vanishop/current && php artisan schedule:run >> /dev/null 2>&1
```

Lịch (giờ VN; xem đầy đủ: `php artisan schedule:list`):

| Tần suất | Lệnh |
|---|---|
| Mỗi phút | `vani:inventory:release-expired`, `vani:payment:expire`, `vani:payment:reconcile`, `vani:integration:dispatch`, `vani:integration:process-inbox`, `vani:metrics:snapshot` (kèm nhịp tim scheduler cho `/health`), `vani:alerts:check` (cảnh báo tự động) |
| 5 phút | `vani:cart:detect-abandoned`, `vani:plugin:finish-draining`, `horizon:snapshot` |
| 15 phút | `vani:plugin:health` |
| Hằng giờ | `vani:integration:reconcile-orders`, `vani:orders:complete-delivered`, `vani:idempotency:prune` |
| Hằng ngày | 01:30 `backup:clean` · 02:00 `backup:run` · 03:30 `vani:inventory:verify`, `vani:cart:prune` · 04:00 `vani:payment:verify` · 09:00 `backup:monitor` |

## 7. Nginx mẫu

```nginx
server {
    listen 443 ssl http2;
    server_name ten-mien;
    root /var/www/vanishop/current/public;
    index index.php;
    client_max_body_size 50M;

    # Asset Vite có hash trong tên file
    location /build/ { expires 1y; add_header Cache-Control "public, immutable"; try_files $uri =404; }

    # Ảnh thu nhỏ: có file → trả tĩnh; chưa có → Laravel tạo (KHÔNG dùng =404 ở đây)
    location /cache/ { expires 1y; add_header Cache-Control "public, immutable"; try_files $uri /index.php?$query_string; }

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

`$realpath_root` (không phải `$document_root`) để PHP-FPM theo đúng release mới sau khi đổi symlink.

### CDN cho trang công khai (0.3.34)

- Trang chủ, danh mục, thương hiệu, tìm kiếm, sản phẩm, CMS trả `Cache-Control: public, max-age=0, s-maxage=300, stale-while-revalidate=600` và không `Set-Cookie`. Cấu hình CDN **tôn trọng header gốc** (không ép cache mọi thứ), khoá cache theo URL đầy đủ (gồm query cho tìm kiếm/lọc), không cache response có `Set-Cookie` hoặc `private`.
- **Không** cache: `/_vani/*`, `/gio-hang`, `/thanh-toan`, `/don-hang/*`, `/tai-khoan/*`, `/api/*`, đường dẫn Admin. Các route này đã trả `private`/`no-store`, nhưng nên có rule loại trừ ở CDN cho chắc.
- TTL đổi bằng `VANI_PAGE_CACHE_TTL` (giây); tắt bằng `VANI_PAGE_CACHE=false` (trang vẫn chạy không phiên, chỉ thành `private, no-store`). Giá/tồn mới hiện trên CDN sau tối đa TTL — khi đổi giá đồng loạt (sale lớn), purge ở CDN hoặc hạ TTL trước đó.

## 8. Rollback

- Giữ 5 release gần nhất. Rollback = trỏ `current` về release trước, `php artisan optimize` trong release đó, reload PHP-FPM, `horizon:terminate`.
- Không rollback DB: migration theo expand/contract nên mã cũ chạy được với schema mới. Chỉ khôi phục DB (§9) khi dữ liệu hỏng.
- Plugin vừa bật gây lỗi: `vani:plugin:disable <id>`. Còn giao dịch dở (thanh toán chờ, vận đơn đang giao) thì lệnh từ chối: dùng `--drain` (ngừng nhận giao dịch mới, tự tắt khi xong) hoặc `--force --yes` (tắt ngay, có audit).

## 9. Sao lưu & khôi phục

- `backup:run` (02:00) sao lưu **DB + `storage/app`** (mã nguồn không backup — triển khai lại từ git). Ảnh trên S3 dùng versioning của nhà cung cấp. Giữ: mọi bản 7 ngày, hằng ngày 16 ngày, hằng tuần 8 tuần, hằng tháng 4 tháng.
- `backup:monitor` (09:00) gửi email cảnh báo nếu backup cũ/thiếu (`MAIL_*` phải đúng).
- Khôi phục: tải file zip từ bucket backup → giải nén (mật khẩu `BACKUP_ARCHIVE_PASSWORD`) → import dump SQL vào DB mới → chép `storage/app` → `php artisan optimize` → `vani:plugin:doctor`.
- Diễn tập khôi phục lên môi trường staging mỗi quý; mục tiêu RPO/RTO ở [operations §8](operations.md).

## 10. Kiểm tra sau deploy & khi có sự cố

| Kiểm tra | Cách |
|---|---|
| Tổng quát | `curl -H "X-Health-Token: …" /health` → `database`, `cache`, `required_extensions`, `integration_outbox`, `scheduler`. `fail` → HTTP 503; `degraded` → 200 nhưng cần xem. LB dùng `/up` (chỉ kiểm tra app boot) cho liveness, `/health` cho readiness |
| Plugin | `php artisan vani:plugin:doctor`, `vani:plugin:list`; Admin → Plugin hiển thị lỗi nạp |
| Queue | Horizon dashboard: job lỗi, thời gian chờ |
| Hiệu năng | Pulse `/{admin}/system/pulse`, thẻ "Thương mại" |
| Ảnh | Mở một PDP; URL ảnh `/cache/media/...` trả 200 |
| Mua hàng | Đặt một đơn COD với sản phẩm thử, huỷ sau khi kiểm tra |

Sự cố thường gặp:

| Triệu chứng | Nguyên nhân hay gặp | Xử lý |
|---|---|---|
| Checkout mất phương thức thanh toán/giao hàng; `/health` báo `required_extensions` | Release mới thiếu cache plugin | `php artisan vani:plugin:cache` (hoặc `optimize`) |
| `/health` scheduler `degraded` | Cron không chạy / chạy sai thư mục | Kiểm tra crontab, `php artisan schedule:list` |
| Đơn VNPay treo "chờ thanh toán" | IPN bị chặn (WAF, allowlist), sai `APP_URL` | Mở `/api/payments/vnpay/callback`; `vani:payment:reconcile` tự hỏi cổng mỗi phút |
| Ảnh storefront 404 | Nginx chặn fallback `/cache/`; thư mục `public/cache` không ghi được | Sửa location §7; quyền ghi cho user PHP-FPM |
| Một plugin làm sập site | Lỗi trong plugin | Tạm đặt `VANI_PLUGINS_SAFE_MODE=true` + `optimize` (không nạp plugin nào — site chạy Core, **không có thanh toán/giao hàng**), xác định plugin, `vani:plugin:disable <id>`, tắt safe mode |
| `integration_outbox` degraded | Đối tác/ERP lỗi, message dead | Admin → Tích hợp; `vani:integration:replay` sau khi đối tác ổn |
| IP khách trong log/allowlist toàn là IP LB | Thiếu `VANI_TRUSTED_PROXIES` | Đặt biến, `optimize` |

## 11. Bảo mật tối thiểu

- Chỉ mở 80/443 ra Internet; MySQL/Redis trong mạng riêng.
- HTTPS bắt buộc (HSTS ở CDN/Nginx), `SESSION_SECURE_COOKIE=true`.
- Admin: đường dẫn riêng + allowlist IP + tài khoản nhân viên theo vai trò; mật khẩu bị lộ bị chặn (`VANI_ADMIN_CHECK_BREACHED_PASSWORDS`, mặc định bật ở production).
- Bí mật (`APP_KEY`, secret cổng thanh toán, key S3, mật khẩu backup) không commit, không gửi qua chat; secret plugin nhập ở Admin → Cấu hình được lưu mã hoá.
- Không copy DB production xuống môi trường khác khi chưa ẩn danh hoá.

## 12. Chưa có (cần biết khi vận hành)

- **Cảnh báo tự động** đã có (0.3.30, [observability §7.1](../16-observability/observability.md)): email tới `VANI_ALERT_EMAILS`, thêm Telegram/Slack/Zalo bằng plugin `AlertChannel`. Vẫn cần dịch vụ giám sát ngoài gọi `/health` mỗi 1–5 phút, vì khi scheduler/server chết thì không còn gì tự gửi cảnh báo. Chưa có gọi điện on-call.
- Tracing, Prometheus/Grafana chưa có ([observability](../16-observability/observability.md)).
- Một web node là cấu hình đã kiểm chứng; nhiều node cần Redis dùng chung, media trên S3 và cân nhắc CDN trước `/cache/`.
