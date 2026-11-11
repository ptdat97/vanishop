# VaniShop

**A Modular Commerce Platform Built with Laravel**

VaniShop là nền tảng thương mại điện tử được xây dựng trên Laravel, hướng đến các doanh nghiệp bán lẻ và thời trang cần vận hành một cửa hàng với nhiều thương hiệu trên cùng một hệ thống.

Dự án sử dụng kiến trúc **Modular Monolith, Domain-Driven Design (DDD) và Commerce Microkernel**. Commerce Core cung cấp các nghiệp vụ thương mại nền tảng, trong khi những chính sách kinh doanh và tích hợp đặc thù được triển khai thông qua hệ thống plugin.

Mục tiêu là xây dựng một nền tảng commerce có khả năng mở rộng, dễ bảo trì và nâng cấp mà không phải liên tục chỉnh sửa mã nguồn Core.

---

## Tổng quan

- **Framework:** Laravel 13
- **Backend:** PHP 8.3+
- **Database:** MySQL 8.4+
- **Admin:** Inertia.js 3, Vue 3, TypeScript
- **UI:** Tailwind CSS 4, Reka UI
- **Storefront:** Blade SSR
- **Build tool:** Vite 8
- **Testing:** Pest 5, Larastan, Laravel Pint

## Định hướng

VaniShop hiện tập trung vào mô hình:

> **Một cửa hàng — Một website — Một catalog — Nhiều thương hiệu — Một giỏ hàng — Một hệ thống vận hành.**

Thay vì xây dựng nhiều website riêng biệt cho từng thương hiệu, VaniShop cho phép quản lý các thương hiệu trong cùng một catalog.

Khách hàng có thể:

- Duyệt sản phẩm theo danh mục hoặc thương hiệu.
- Tìm kiếm và lọc sản phẩm.
- Thêm sản phẩm từ nhiều thương hiệu vào cùng một giỏ hàng.
- Thanh toán trong một đơn hàng.
- Sử dụng một tài khoản khách hàng thống nhất.

VaniShop ưu tiên mô hình SME trước: giao diện quản trị đơn giản, tối ưu các thao tác vận hành hằng ngày và giữ sẵn các điểm mở rộng khi doanh nghiệp phát triển.

## Tính năng

### 1. Catalog & Product Management

- Quản lý danh mục và thương hiệu.
- Mô hình sản phẩm thời trang Style → Color → Variant.
- Quản lý màu sắc, kích thước và thuộc tính.
- Quản lý hình ảnh và thư viện media.
- Bộ sưu tập sản phẩm.
- Quản lý giá niêm yết, giá khuyến mãi và bảng giá thành viên.
- Quản lý trạng thái kinh doanh của sản phẩm.
- Tìm kiếm, lọc và sắp xếp sản phẩm.

### 2. Inventory Management

- Quản lý tồn kho.
- Reservation và inventory ledger.
- Available-to-sell (ATS).
- Điều chỉnh tồn kho.
- Kiểm kê và đối soát nội bộ.
- Chống overselling bằng transaction và locking.
- Hỗ trợ mở rộng quản lý nhiều kho thông qua plugin `vani.warehouse`.

### 3. Cart & Checkout

- Giỏ hàng khách vãng lai và khách đã đăng nhập.
- Gộp giỏ hàng sau khi đăng nhập.
- Quản lý số lượng và sản phẩm trong giỏ.
- Tính toán giá, khuyến mãi, VAT và phí giao hàng.
- Áp dụng voucher.
- Kiểm tra tồn kho trước khi đặt hàng.
- Hỗ trợ đặt hàng idempotent.

### 4. Order Management

- Quản lý đơn hàng tập trung.
- Theo dõi trạng thái đơn hàng.
- Xác nhận, xử lý và hủy đơn.
- Tra cứu đơn hàng cho khách hàng.
- Quản lý thanh toán và hoàn tiền.
- Quản lý vận đơn và giao hàng.
- Hỗ trợ luồng đổi trả và hoàn tiền.

### 5. Payment & Fulfillment

Các capability thanh toán và giao nhận được thiết kế theo hướng plugin.

Một số plugin đã được triển khai:

| Plugin | Chức năng |
|---|---|
| `vani.cod` | Thanh toán khi nhận hàng |
| `vani.bank-transfer` | Chuyển khoản thủ công |
| `vani.vietqr` | Thanh toán QR động |
| `vani.vnpay` | Cổng thanh toán VNPay |
| `vani.shipping-flat-rate` | Phí giao hàng cố định |
| `vani.ghn` | Tích hợp hãng vận chuyển GHN |
| `vani.tax-vn-vat` | VAT Việt Nam |

Mức độ tích hợp thực tế phụ thuộc cấu hình và trạng thái triển khai của từng plugin. Không phải mọi luồng đều đã sẵn sàng cho production.

### 6. Customer Management

- Quản lý hồ sơ khách hàng thống nhất.
- Đăng nhập bằng OTP hoặc mật khẩu.
- Quản lý địa chỉ giao hàng.
- Lịch sử mua hàng.
- Gộp hồ sơ khách hàng.
- Quản lý consent.
- Xuất và ẩn danh hóa dữ liệu khách hàng.

### 7. Promotion & Pricing

- Bảng giá cấp cửa hàng.
- Giá theo nhóm khách hàng.
- Giá khuyến mãi.
- Promotion engine.
- Voucher và điều kiện áp dụng khuyến mãi.
- Mở rộng luật khuyến mãi bằng plugin.

### 8. CMS & Storefront

- Storefront render bằng Blade SSR.
- Theme `vani-base`.
- Quản lý nội dung thông qua plugin CMS.
- Trang chủ cấu thành từ các block.
- Menu và nội dung storefront.
- SEO metadata.
- Hỗ trợ mở rộng theme và storefront bằng extension points.

### 9. Integration Platform

VaniShop có module Integration phục vụ kết nối với hệ thống bên ngoài:

- Transactional Outbox / Inbox.
- Event feed.
- Webhook ký HMAC.
- Retry, backoff và dead-letter.
- Replay message.
- Integration API v1.
- API key, scope và IP allowlist.
- Idempotency và xử lý message theo thứ tự.

Nền tảng đã có cơ chế tích hợp, nhưng chưa đồng nghĩa với việc đã có connector hoàn chỉnh cho mọi ERP hoặc hệ thống bên thứ ba.

### 10. Admin & Operations

- Admin xây dựng bằng Inertia.js và Vue 3.
- Phân quyền nhân viên theo role và permission.
- Audit log.
- Quản lý plugin.
- Cấu hình hệ thống.
- Quản lý sản phẩm, khách hàng, đơn hàng và tồn kho.
- Các component UI dùng chung.
- Drag & Drop engine dùng chung cho các giao diện quản trị có sắp xếp.

---

## Kiến trúc hệ thống

VaniShop sử dụng mô hình:

**Modular Monolith + Pragmatic DDD + Commerce Microkernel + Plugin-first.**

Không triển khai microservices ở giai đoạn hiện tại. Các module chạy trong cùng một ứng dụng Laravel nhưng có ranh giới nghiệp vụ, trách nhiệm và quyền sở hữu dữ liệu riêng.

### Kiến trúc bốn vòng

```text
┌───────────────────────────────────────────────┐
│             Business Plugins                  │
│                                               │
│ Payment · Shipping · Promotion · CMS          │
│ Reports · Loyalty · ERP Connectors            │
├───────────────────────────────────────────────┤
│             System Plugins                    │
│                                               │
│ COD · Bank Transfer · VAT · Flat Shipping     │
│ Vietnam Phone Policy · Localization           │
├───────────────────────────────────────────────┤
│              Commerce Core                    │
│                                               │
│ Catalog · Pricing · Inventory · Customer      │
│ Cart · Checkout · Ordering · Payment          │
│ Fulfillment · Returns · Promotion             │
│ Notification · Integration · Storefront       │
├───────────────────────────────────────────────┤
│                Microkernel                    │
│                                               │
│ Shared · Tenancy · Identity · Extension       │
│                                               │
│ Plugin Lifecycle · Hook Registry · Event Bus  │
│ Settings · Permissions · Audit · Money        │
└───────────────────────────────────────────────┘
```

### Nguyên tắc kiến trúc

1. **Core-first invariants:** Các quy tắc đảm bảo tính nhất quán của thương mại được bảo vệ trong Core.
2. **Plugin-first capabilities:** Nghiệp vụ đặc thù được triển khai bằng plugin.
3. **Dependency direction:** Module và plugin chỉ phụ thuộc theo hướng được kiến trúc cho phép.
4. **No core patching:** Không chỉnh sửa Core chỉ để bổ sung một chính sách kinh doanh riêng.
5. **Explicit contracts:** Các module giao tiếp thông qua contract, application service và extension point.
6. **Transactional consistency:** Những nghiệp vụ cần tính nhất quán cao như checkout, tồn kho và đặt đơn được xử lý trong transaction.
7. **API-oriented:** Storefront API và native storefront hướng tới sử dụng chung Application layer.

### Cấu trúc thư mục

```text
vanishop/
├── app/
│   └── Providers/
│
├── modules/
│   ├── Shared/
│   ├── Tenancy/
│   ├── Identity/
│   ├── Extension/
│   ├── Catalog/
│   ├── Pricing/
│   ├── Inventory/
│   ├── Customer/
│   ├── Cart/
│   ├── Checkout/
│   ├── Ordering/
│   ├── Payment/
│   ├── Fulfillment/
│   ├── Returns/
│   ├── Promotion/
│   ├── Notification/
│   ├── Integration/
│   └── Storefront/
│
├── custom/
│   ├── plugin/
│   │   ├── vani-cod/
│   │   ├── vani-vietqr/
│   │   ├── vani-ghn/
│   │   └── ...
│   │
│   └── theme/
│       └── vani-base/
│
├── resources/
│   └── js/
│       └── admin/
│
├── routes/
├── database/
├── tests/
├── scripts/
├── docs/
│
├── composer.json
├── package.json
└── vite.config.js
```

Chi tiết cấu trúc module và plugin được mô tả trong thư mục `docs/`.

---

## Plugin System

Plugin là cơ chế mở rộng chính của VaniShop.

Một plugin có thể sở hữu capability, cấu hình, màn hình quản trị, route, migration, seeder và các thành phần storefront riêng.

Hệ thống hỗ trợ:

- Plugin manifest.
- Dependency resolution.
- Capability registration.
- Lifecycle management.
- Hook và extension points.
- Plugin enable / disable.
- Safe mode.
- CLI management.
- Compatibility checks.
- Plugin-specific migrations và seeders.
- Phạm vi bật plugin theo cửa hàng hoặc brand khi được hỗ trợ.

### Nguyên tắc phát triển plugin

- Không sửa mã nguồn Core để triển khai nghiệp vụ riêng.
- Không truy cập trực tiếp vào implementation nội bộ của module khác.
- Ưu tiên contract và extension point công khai.
- Plugin phải khai báo dependency và capability.
- Không được phá vỡ các bất biến thương mại của Core.

VaniShop hiện hướng tới plugin nội bộ được triển khai cùng codebase, chưa phải marketplace plugin bên thứ ba hoặc nền tảng cài plugin ZIP từ xa.

---

## Tech Stack

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Database | MySQL 8.4+ |
| ORM | Eloquent |
| Admin | Inertia.js 3 |
| Frontend | Vue 3 |
| Language | TypeScript 5.9 |
| CSS | Tailwind CSS 4 |
| UI Components | Reka UI |
| Build | Vite 8 |
| Animation | Motion |
| Drag & Drop | Vue Draggable / SortableJS |
| Image | Intervention Image |
| Gallery | PhotoSwipe |
| Slider | Swiper |
| Queue | Laravel Queue / Horizon |
| Monitoring | Laravel Pulse |
| Testing | Pest 5 |
| Static Analysis | Larastan |
| Code Style | Laravel Pint |

---

## Yêu cầu hệ thống

Để chạy môi trường phát triển:

- PHP 8.3 trở lên.
- Composer.
- Node.js và npm.
- MySQL 8.4 trở lên.
- Các PHP extensions theo yêu cầu của Laravel 13 và những package đang sử dụng.

Các thành phần như Redis, Horizon, S3-compatible storage và CDN có thể được cấu hình bổ sung tùy môi trường triển khai.

## Cài đặt

### 1. Clone repository

```bash
git clone https://github.com/ptdat97/vanishop.git

cd vanishop
```

### 2. Cài đặt dependencies

```bash
composer install
```

```bash
npm install
```

### 3. Cấu hình môi trường

```bash
cp .env.example .env
```

Tạo database MySQL:

```sql
CREATE DATABASE vanishop
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Cập nhật thông tin database trong `.env`:

```env
APP_NAME=VaniShop
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vanishop
DB_USERNAME=root
DB_PASSWORD=

VANI_LOCALE=vi
VANI_LOCALES=vi
VANI_CURRENCY=VND

QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 4. Khởi tạo ứng dụng

```bash
php artisan key:generate

php artisan vani:install
```

### 5. Build frontend

```bash
npm run build
```

### 6. Chạy môi trường phát triển

```bash
composer run dev
```

Ứng dụng sẽ khởi chạy theo cấu hình development của Laravel.

> Lưu ý: `composer run setup` cũng cung cấp quy trình cài đặt tự động gồm Composer dependencies, `.env`, application key, VaniShop installer và frontend build. Có thể sử dụng quy trình này thay cho các bước cài đặt thủ công tương ứng.

---

## Kiểm thử & Code Quality

VaniShop sử dụng automated tests để bảo vệ nghiệp vụ và ranh giới kiến trúc.

Chạy toàn bộ test:

```bash
composer test
```

Chạy static analysis:

```bash
composer analyse
```

Kiểm tra type coverage:

```bash
composer types
```

Kiểm tra coding standards:

```bash
vendor/bin/pint --test
```

Build frontend:

```bash
npm run build
```

Kiểm thử Drag & Drop engine:

```bash
npm run test:dnd
```

Các nhóm kiểm thử tập trung vào:

- Unit tests.
- Feature tests.
- Commerce workflow tests.
- Inventory concurrency tests.
- Plugin contract tests.
- Architecture tests.
- Database compatibility tests.

---

## API

Storefront API hiện sử dụng version prefix:

```text
/api/storefront/v1
```

Một số nhóm endpoint:

```text
/api/storefront/v1/categories
/api/storefront/v1/brands
/api/storefront/v1/products
/api/storefront/v1/search

/api/storefront/v1/cart
/api/storefront/v1/checkout

/api/storefront/v1/orders
/api/storefront/v1/account
```

API được thiết kế phục vụ native storefront, JavaScript của theme và khả năng mở rộng headless trong tương lai.

**Định hướng API-first đang được củng cố:** Storefront API v1 sẽ là hợp đồng chung cho các tính năng storefront; Blade SSR là một consumer của Application layer, không phải nơi sở hữu logic nghiệp vụ. Một số phần vẫn đang trong lộ trình hợp nhất, chuẩn hóa validation và hoàn thiện hợp đồng API.

---

## Tài liệu

Tài liệu kiến trúc và nghiệp vụ được quản lý trong `docs/`.

| Tài liệu | Nội dung |
|---|---|
| `docs/00-overview/vision.md` | Tầm nhìn và định hướng dự án |
| `docs/00-overview/status.md` | Trạng thái triển khai thực tế |
| `docs/02-architecture/overview.md` | Kiến trúc tổng thể |
| `docs/02-architecture/commerce-kernel.md` | Commerce Microkernel |
| `docs/05-plugin/` | Plugin System |
| `docs/06-api/` | API architecture |
| `docs/08-inventory/` | Inventory |
| `docs/11-integration/` | Integration Platform |
| `docs/14-storefront/` | Storefront |
| `docs/15-security/` | Security |
| `docs/18-operations/` | Deployment và vận hành |
| `docs/19-adr/` | Architecture Decision Records |
| `docs/20-roadmap/` | Roadmap |

Tham khảo trạng thái triển khai trước khi sử dụng một capability trong môi trường production.

---

## Phạm vi dự án

VaniShop hiện tập trung vào:

- Một cửa hàng và một người bán.
- Nhiều thương hiệu trong cùng catalog.
- Một storefront và một checkout thống nhất.
- Vận hành thương mại điện tử theo mô hình SME.
- Mở rộng nghiệp vụ thông qua plugin.
- Tích hợp hệ thống bên ngoài thông qua API và connector.

VaniShop không định hướng trở thành SaaS multi-tenant hoặc marketplace multi-vendor trong phạm vi hiện tại. Dự án cũng không nhằm thay thế ERP, kế toán hay hệ thống điều phối kho chuyên biệt.

## Roadmap định hướng

- Hoàn thiện API-first cho Storefront.
- Chuẩn hóa contract và OpenAPI.
- Hoàn thiện trải nghiệm Admin và Storefront.
- Mở rộng plugin thanh toán và vận chuyển.
- Hoàn thiện quy trình đổi trả, hoàn tiền và đối soát.
- Nâng cao khả năng tích hợp ERP và hệ thống bên ngoài.
- Tăng cường bảo mật, observability và production readiness.
- Hoàn thiện kiểm thử tải và quy trình triển khai production.

Chi tiết trạng thái từng hạng mục nằm trong `docs/00-overview/status.md` và `docs/20-roadmap/`.

---

## Development Principles

VaniShop được phát triển dựa trên các nguyên tắc:

1. Modular Monolith trước Microservices.
2. Domain boundaries rõ ràng.
3. Commerce Core giữ primitive và business invariants.
4. Plugin-first cho nghiệp vụ đặc thù.
5. Không fork hoặc patch Core tùy tiện.
6. API và native storefront sử dụng chung Application layer.
7. Transactional consistency cho các luồng thương mại quan trọng.
8. Automated tests là một phần của tính năng.
9. SME-first, mobile-first và Vietnam-first.
10. Tài liệu phải phản ánh trạng thái implementation thực tế.

## License

Vui lòng kiểm tra thông tin license được công bố chính thức trong repository trước khi sử dụng, phân phối hoặc thương mại hóa VaniShop.

---

**VaniShop**  
*Commerce infrastructure designed for practical business operations.*