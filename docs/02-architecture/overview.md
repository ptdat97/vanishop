# Kiến trúc tổng thể

> Trạng thái: **Designed**. Hiện chỉ có Laravel skeleton ([status](../00-overview/status.md)).

## 1. Phong cách kiến trúc

**Laravel + Modular Monolith + DDD (pragmatic) + API-first + Plugin Architecture.**

| Lựa chọn | Lý do | ADR |
|---|---|---|
| Modular Monolith | Một lần deploy, transaction cục bộ cho checkout/tồn kho, đội nhỏ–vừa | [ADR-001](../19-adr/ADR-001-modular-monolith.md) |
| DDD theo bounded context | Ranh giới rõ, ownership dữ liệu rõ, về sau có thể tách service | [ADR-002](../19-adr/ADR-002-ddd-boundaries.md) |
| Plugin Architecture | Nghiệp vụ thay đổi nhanh nằm ngoài Core | [ADR-003](../19-adr/ADR-003-plugin-architecture.md), [ADR-004](../19-adr/ADR-004-extension-points.md) |
| API-first | Storefront native, headless, mobile, đối tác dùng chung Application layer | [ADR-009](../19-adr/ADR-009-storefront-architecture.md), [ADR-010](../19-adr/ADR-010-api-versioning.md) |
| Tích hợp hướng sự kiện | Hệ thống ngoài không làm hỏng checkout | [ADR-005](../19-adr/ADR-005-event-driven-integration.md), [ADR-013](../19-adr/ADR-013-outbox-inbox.md) |

**Không** làm microservices. Chỉ tách service khi có lý do vận hành/scale đo được (rule R19).

## 2. Sơ đồ ngữ cảnh (C4 level 1)

```mermaid
flowchart TB
    Customer([Khách hàng<br/>Web / Mobile / Headless])
    Staff([Nhân viên<br/>Admin / Brand / CSKH / Cửa hàng])
    subgraph VS[VaniShop]
      SF[Native Storefront<br/>Blade SSR]
      ADM[Admin<br/>Inertia + Vue 3]
      API[API<br/>storefront · admin · integration]
      CORE[Commerce Kernel<br/>modules/*]
      PLG[Business Plugins<br/>custom/plugin/*]
    end
    ERP[[ERP]]
    ODO[[ODO — tạm hoãn]]
    PAY[[Cổng thanh toán]]
    SHIP[[Hãng vận chuyển]]
    MKT[[Sàn TMĐT]]
    NOTI[[Email / SMS / ZNS]]
    Customer --> SF & API
    Staff --> ADM
    SF & ADM & API --> CORE
    PLG --> CORE
    PLG <--> PAY & SHIP & MKT & NOTI & ERP
    API <-.-> ERP & ODO
```

Hệ thống ngoài kết nối theo hai cách ([integration-platform](../11-integration/integration-platform.md)): **tự gọi Integration API** (ERP, ODO, POS), hoặc **qua connector plugin** (cổng thanh toán, hãng vận chuyển, sàn TMĐT).

## 3. Sơ đồ container (C4 level 2)

```mermaid
flowchart LR
    CDN[CDN + WAF] --> WEB[Web nodes<br/>PHP-FPM / Octane]
    WEB --> DB[(MySQL 8.4)]
    WEB --> R[(Redis<br/>cache · session · lock · queue)]
    WEB --> S[(Meilisearch)]
    WEB --> OBJ[(Object storage)]
    WK[Queue workers<br/>Horizon] --> DB & R & S & OBJ
    WK --> EXT[[External APIs]]
    SCH[Scheduler] --> R
```

Tất cả đặt tại trung tâm dữ liệu ở Việt Nam ([ADR-018](../19-adr/ADR-018-infrastructure-vietnam.md)). Chi tiết vận hành: [operations](../18-operations/operations.md).

## 4. Cấu trúc thư mục dự án

```
vanishop/
├── app/                    # Khung ứng dụng: Providers, middleware chung, Console
│   └── Providers/ModuleServiceProvider.php
├── modules/                # Commerce Kernel — namespace Modules\<Context>\
│   ├── Shared/  Tenancy/  Brand/  Channel/  Identity/  Extension/
│   ├── Catalog/ Pricing/  Inventory/ Customer/ Cart/ Checkout/
│   ├── Ordering/ Payment/ Fulfillment/ Returns/ Promotion/
│   └── Content/ Notification/ Reporting/ Integration/
├── custom/
│   ├── plugin/             # Business plugins — namespace Plugin\<Name>\
│   └── theme/              # Theme storefront theo brand
├── resources/js/           # Entry Admin Inertia, component Vue dùng chung
├── routes/                 # Chỉ route khung; route nghiệp vụ nằm trong module/plugin
├── tests/                  # Test xuyên module (E2E, architecture)
└── docs/
```

Autoload (`composer.json`):

```json
"psr-4": {
    "App\\": "app/",
    "Modules\\": "modules/",
    "Plugin\\": "custom/plugin/",
    "Database\\Factories\\": "database/factories/",
    "Database\\Seeders\\": "database/seeders/"
}
```

Cấu trúc bên trong module: [bounded-contexts §4](bounded-contexts.md). Cấu trúc plugin: [plugin-system](../05-plugin/plugin-system.md).

## 5. Tech stack

| Tầng | Lựa chọn | Trạng thái |
|---|---|---|
| Ngôn ngữ / Framework | PHP 8.4, Laravel 13 | Implemented |
| Hook | `tormjens/eventy`, bọc bởi `Modules\Extension\Hook` | Package đã cài; lớp bọc Designed |
| CSDL | MySQL 8.4 LTS, InnoDB, `utf8mb4` ([ADR-016](../19-adr/ADR-016-mysql.md)) | Designed |
| Cache / Queue / Lock | Redis 7 + Laravel Horizon | Designed |
| Tìm kiếm | Laravel Scout; `SearchProvider` contract; Meilisearch là provider mặc định | Designed |
| File | Object storage S3-compatible tại VN + CDN | Designed |
| Admin | Inertia 2 + Vue 3 + TypeScript + Tailwind 4 ([ADR-017](../19-adr/ADR-017-admin-ui-inertia.md)) | Designed |
| Storefront | Blade SSR + Alpine.js + Tailwind 4, theme `custom/theme/*` | Designed |
| API auth | Sanctum (storefront/admin), API key + HMAC (integration) | Designed |
| Test | Pest 5 (unit, feature, arch), Pest Browser (E2E) | Pest Implemented |
| Chất lượng | Pint, Larastan level ≥ 6 | Pint Implemented |

## 6. Luồng request tiêu biểu

```mermaid
sequenceDiagram
    participant C as Client
    participant MW as Middleware
    participant H as Controller
    participant A as Application (Action/Query)
    participant D as Domain
    participant P as Persistence
    participant O as Outbox
    C->>MW: HTTP request
    MW->>MW: correlation id, ResolveChannel → CurrentContext, auth
    MW->>H: request
    H->>A: Command DTO (đã validate)
    A->>P: load aggregate (khoá nếu cần)
    A->>D: thực thi quy tắc nghiệp vụ
    A->>P: lưu (trong transaction)
    A->>O: ghi message (cùng transaction)
    A-->>H: Result DTO
    H-->>C: Resource / View
    Note over A: Domain event dispatch sau commit (afterCommit)
```

## 7. Thuộc tính chất lượng (NFR)

| Thuộc tính | Mục tiêu |
|---|---|
| Khả dụng | 99,9%/tháng cho storefront và checkout |
| Hiệu năng | Storefront TTFB P95 < 300 ms (cache nóng); API P95 < 200 ms (không tính cổng ngoài) |
| Tải | 500 đơn/phút giờ cao điểm; 5.000 req/s qua CDN |
| Nhất quán tồn | 0 oversell do race condition ([inventory](../08-inventory/inventory.md)) |
| Mở rộng | Thêm brand không cần deploy; thêm capability bằng plugin, không sửa Core |
| Quan sát | Mọi flow truy vết được bằng correlation id ([observability](../16-observability/observability.md)) |
| Bảo mật | OWASP ASVS L2; PCI-DSS SAQ-A ([security](../15-security/security.md)) |
