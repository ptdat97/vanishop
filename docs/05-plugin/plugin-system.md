# Plugin System

> Trạng thái: **Partially Implemented** (slice 0). Vị trí trong mô hình microkernel (vòng 0 Extension nạp plugin vòng 2–3): [commerce-kernel](../02-architecture/commerce-kernel.md). Đã có: manifest, dependency resolver, lifecycle, loader, cache, safe mode, `PluginServiceProvider`, CLI chính, plugin mẫu. Chi tiết từng phần ghi ngay trong các mục bên dưới. Quyết định: [ADR-003](../19-adr/ADR-003-plugin-architecture.md), [ADR-026](../19-adr/ADR-026-plugin-deploy-via-code.md) (chỉ triển khai qua mã nguồn), [ADR-027](../19-adr/ADR-027-plugin-data-no-core-columns.md) (dữ liệu plugin). **Viết plugin: đọc [contracts/](contracts/README.md)** (chữ ký chính xác, lỗi thường gặp, checklist).

## 1. Hướng phụ thuộc

```text
Plugin  ──►  Core Contracts / Events / Hooks / Registry  ──►  Core
Core    ──X  Plugin            (cấm — arch test, rule R4)
Plugin  ──►  Plugin khác        (chỉ qua Contracts/Events của plugin đó, khai báo trong requires)
```

## 2. Cấu trúc plugin

Plugin đặt tại `custom/plugin/<Name>/`, namespace `Plugin\<Name>\`. Cấu trúc giống một module, nhưng có thể mỏng hơn tuỳ độ phức tạp:

```
custom/plugin/VietQr/
├── vanishop.json                 # manifest
├── VietQrServiceProvider.php     # extends Modules\Extension\PluginServiceProvider
├── Contracts/  Events/           # (tuỳ chọn) API công khai cho plugin khác
├── Domain/                       # quy tắc của plugin (thuần PHP)
├── Application/                  # use case của plugin
├── Infrastructure/               # client HTTP gọi dịch vụ ngoài, adapter
│   └── VietQrGateway.php         # implements PaymentGateway
├── Http/
│   ├── Controllers/WebhookController.php
│   └── routes/{webhooks,admin-api}.php
├── Config/vietqr.php
├── Database/{migrations,factories}/   # bảng plg_vietqr_*
├── Resources/{views,lang,js/Pages}/
├── settings-schema.json
├── README.md                     # đặc tả + hướng dẫn cấu hình
└── Tests/{Unit,Feature,Contract}/
```

## 3. Manifest `vanishop.json`

```json
{
  "id": "vani.vietqr",
  "name": { "vi": "Thanh toán VietQR", "en": "VietQR Payment" },
  "version": "1.0.0",
  "kind": "payment_gateway",
  "provider": "Plugin\\VietQr\\VietQrServiceProvider",
  "requires": {
    "vanishop": "^1.0",
    "plugins": {}
  },
  "conflicts": [],
  "permissions": ["payments.write"],
  "settings_schema": "settings-schema.json",
  "author": "VaniShop Team"
}
```

| Trường | Bắt buộc | Ý nghĩa |
|---|---|---|
| `id` | ✔ | `vendor.name`, duy nhất, không đổi |
| `version` | ✔ | SemVer |
| `provider` | ✔ | ServiceProvider |
| `requires.vanishop` | ✔ | Ràng buộc phiên bản Core (Composer semver) |
| `requires.plugins` | | `{ "vani.einvoice": "^1.0" }` |
| `conflicts` | | Danh sách plugin id không được bật cùng lúc |
| `scopes` | | **Bỏ** từ ADR-028 (plugin bật/tắt toàn cửa hàng); loader bỏ qua |
| `permissions` | | Quyền plugin cần; hiển thị khi cài |
| `settings_schema` | | (cũ) thay bằng `settings()` trong provider |
| `kind` | ✔ | Loại plugin, thuộc `PluginManifest::KINDS` (`payment_gateway`, `shipping_carrier`, `shipping_rate`, `tax`, `promotion`, `notification_channel`, `search`, `integration`, `marketing`, `analytics`, `customer_service`, `content`, `theme_extension`, `language`, `feature`). Loại gắn với extension point → doctor cảnh báo nếu không đóng góp |
| `bundled` | | `true` = **plugin hệ thống** ([ADR-029](../19-adr/ADR-029-commerce-microkernel.md)): tự cài + bật khi dựng hệ thống; không tắt được nếu là implementation đang bật cuối cùng của extension point bắt buộc. Implemented (`vani:install`) |

## 4. Vòng đời

```mermaid
stateDiagram-v2
    [*] --> discovered: quét custom/plugin/*/vanishop.json
    discovered --> installed: vani:plugin:install
    installed --> enabled: vani:plugin:enable [--scope]
    enabled --> disabled: vani:plugin:disable
    disabled --> enabled
    disabled --> uninstalled: vani:plugin:uninstall [--purge]
    uninstalled --> [*]
    installed --> failed: migration/boot lỗi
    enabled --> failed: boot lỗi
    failed --> installed: sửa lỗi + vani:plugin:install --retry
```

| Trạng thái | ServiceProvider được `register()`? | Tham gia flow? |
|---|---|---|
| discovered | Không | Không |
| installed | Có (để route webhook/Admin cấu hình chạy được) | Không |
| enabled (theo scope) | Có | Có, trong scope được bật |
| disabled | Có | Không |
| failed | **Không** | Không |

Lưu trữ: `plugins(id, version, status, installed_at, last_error)`, `plugin_scopes(plugin_id, scope_type, scope_id, enabled)`. Danh sách provider cần nạp (theo thứ tự phụ thuộc) được cache vào `bootstrap/cache/vanishop-plugins.php` (cấu hình `VANI_PLUGINS_CACHE`) mỗi khi trạng thái plugin thay đổi, để lúc boot không phải truy vấn DB.

Scope trong code hiện tại: `owner`, `brand`, `channel`. Theo [ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md), plugin chỉ còn **bật/tắt toàn cửa hàng**; `plugin_scopes` và tham số `--scope` gỡ ở slice 12.

Phạm vi đang bật được cache trong **cache store dùng chung** (`vani:plugins:enabled-scopes`, xoá mỗi khi install/enable/disable/failed) nên request/job không truy vấn `plugins`/`plugin_scopes`. Production nhiều server phải dùng store dùng chung (`database`/`redis`), không dùng `file`/`array`. Trong một request/job, danh sách implementation có hiệu lực của mỗi tag được ghi nhớ theo phạm vi.

## 5. CLI

`list`, `install`, `enable`, `disable`, `uninstall`, `hooks`, `upgrade`, `doctor`: **Implemented**. Kiểm tra extension point bắt buộc trong `disable`/`doctor`: **Implemented** ([commerce-kernel §5](../02-architecture/commerce-kernel.md)).

**Chặn tắt khi còn việc dở dang** (0.3.16): module sở hữu dữ liệu đăng ký `Extensions::guardDisable($tag, $check)`; `disable` từ chối khi implementation của plugin còn việc chưa xong, nêu lý do:

| Tag | Chặn khi | Vì sao |
|---|---|---|
| `vani.payment.gateways` | Cổng còn khoản thanh toán `pending`/`authorized` | IPN về cổng đã tắt trả 404 → tiền khách đã trả không được ghi nhận, khoản hết hạn và đơn bị huỷ |
| `vani.shipping.carriers` | Hãng còn vận đơn chưa `delivered`/`returned`/`cancelled` | Webhook trả 404 → không cập nhật giao hàng, thu COD, nhập lại hàng hoàn |

`--force` tắt dù còn việc dở dang (khẩn cấp: plugin lỗi), ghi `forced` + lý do vào audit; việc dở dang phải xử lý tay. Muốn ngừng nhận đơn mới qua một cổng/hãng mà vẫn hoàn tất đơn cũ: dùng cấu hình của plugin (vd. giới hạn số tiền, tắt phương thức), không tắt plugin.

```bash
php artisan vani:plugin:list                      # id, version, trạng thái, scope, tương thích
php artisan vani:plugin:install vani.vietqr       # kiểm tra deps → chạy migration → installed
php artisan vani:plugin:enable vani.vietqr        # (--scope chỉ còn trong code cũ, bỏ ở slice 12)
php artisan vani:plugin:disable vani.vietqr [--force]   # từ chối khi còn thanh toán chờ / vận đơn đang giao
php artisan vani:plugin:uninstall vani.vietqr [--purge]   # --purge: rollback migration, xoá bảng plg_*
php artisan vani:plugin:hooks [vani.vietqr]       # hook đã khai báo & listener (Core/plugin)
php artisan vani:plugin:upgrade vani.vietqr       # chỉ tiến version: kiểm tra tương thích Core/deps → chạy migration mới; failed → installed khi thành công; audit
php artisan vani:plugin:doctor [--json]           # nâng/hạ version chờ, migration chờ, plugin failed, thiếu manifest/provider, không tương thích, cache không dùng chung ở production; exit 1 khi có lỗi
```

## 6. Dependency resolution và tương thích

1. Đọc mọi manifest, kiểm tra `requires.vanishop` với phiên bản Core (`config('vanishop.version')`), dùng `composer/semver`.
2. Dựng đồ thị `requires.plugins`; **topological sort** để xác định thứ tự install/boot; phát hiện vòng thì báo lỗi, không cài.
3. Kiểm tra `conflicts` hai chiều.
4. `enable` plugin A khi dependency B chưa enable trong cùng scope → từ chối (hoặc `--with-deps`).
5. `disable`/`uninstall` B khi còn plugin phụ thuộc đang enable → từ chối.
6. Nâng Core làm plugin không còn tương thích → plugin chuyển `failed` với lý do `incompatible_core`, Core vẫn chạy.
7. (Designed) `disable`/`uninstall` plugin cung cấp implementation **cuối cùng** của extension point bắt buộc (thanh toán, phí giao, thuế, carrier, kênh thông báo) → từ chối.

## 7. Migration, upgrade, rollback

| Hành động | Hành vi |
|---|---|
| install | Chạy migration trong `Database/migrations` của plugin (bảng `plg_<plugin>_*`), ghi version |
| upgrade | So version manifest với version đã cài → chạy migration chưa chạy; migration **phải tương thích ngược** (expand/contract) |
| disable | Không đụng dữ liệu |
| uninstall | Giữ bảng (mặc định). `--purge` chạy `down()` của các migration theo thứ tự ngược rồi xoá dữ liệu cấu hình |
| Lỗi migration | MySQL không rollback được DDL → plugin chuyển `failed` + `last_error`; migration phải idempotent (`if (! Schema::hasTable(...))`) để chạy lại an toàn |

## 8. Xử lý lỗi và cô lập

| Tình huống | Hành vi |
|---|---|
| Exception trong `register()`/`boot()` (kể cả khi nghe hook chưa khai báo/hook internal) | Loader bắt lỗi, ghi log, đánh dấu plugin `failed` (không nạp ở request sau), **tiếp tục boot Core**: Implemented |
| Plugin làm hỏng request liên tục | (Designed) Circuit breaker theo plugin: quá N lỗi/phút ở slot/filter không giao dịch thì tạm bỏ qua plugin đó 5 phút |
| Sự cố nghiêm trọng | **Safe mode**: `VANI_PLUGINS_SAFE_MODE=true` → không nạp plugin nào: Implemented |
| Cổng thanh toán plugin lỗi | `PaymentGateway::isAvailable()` trả false khi health check fail → ẩn phương thức |

## 9. `PluginServiceProvider`

API đã có (**Implemented**, `modules/Extension/PluginServiceProvider.php`). Mọi listener/menu đăng ký qua các helper này gắn với plugin id, nên **chỉ có hiệu lực trong phạm vi plugin được bật**. Plugin không nên gọi thẳng `Hook::onFilter()`, vì khi đó listener chạy ở mọi phạm vi.

| Helper | Trạng thái | Tác dụng |
|---|---|---|
| `pluginId()` (abstract) | Implemented | Trùng `id` trong manifest |
| `pluginPath($path)` | Implemented | Đường dẫn trong thư mục plugin |
| `onFilter` / `onAction` / `onValidate` / `onSlot` | Implemented | Nghe hook public, chỉ chạy khi plugin active |
| `onEvent($event, $handler)` | Implemented (2026-10-13) | Nghe domain event: chỉ chạy khi plugin đang bật (code hiện còn lọc theo `brandId` của event — bỏ ở slice 12); lỗi được log, không làm hỏng flow. **Không** dùng `Event::listen()` trực tiếp |
| `adminMenu($key, $label, $route, $permission, $order)` | Implemented | Menu Admin, ẩn khi thiếu quyền hoặc plugin không active |
| `permissions([...])` | Implemented | Khai báo permission vào `PermissionRegistry` |
| `adminRoutes($file)` | Implemented | `/{VANI_ADMIN_PATH}/plugins/{slug}/…`, route name `admin.plugins.{slug}.…`, middleware Admin + `vani.plugin-active` (404 khi plugin không active) |
| `webhookRoutes($file)` | Implemented | `/api/integrations/{slug}/…` |
| `adminPages($namespace, $path)` | Implemented | `Inertia::render('<Namespace>::<Trang>')` |
| `migrations($path)`, `translations($path, $namespace)` | Implemented | |
| `settings([...])` | Implemented (2026-10-14) | Khai báo cấu hình (key, label, type `string/secret/text/int/bool/select`, default, options) → Admin → Cấu hình sinh form; đọc lúc chạy qua `Tenancy\Contracts\Settings::get($pluginId, $key, $default)`. Thay cho `settings_schema` JSON trong manifest |
| `storefrontRoutes()`, `adminApiRoutes()`, `scheduledTasks()`… | Designed | Xem [extension-point-catalog §5](../04-extension/extension-point-catalog.md) |

`{slug}` là plugin id với dấu `.` đổi thành `-` (ví dụ `vani.hello-world` → `vani-hello-world`).

```php
namespace Plugin\HelloWorld;

use Modules\Extension\PluginServiceProvider;

final class HelloWorldServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.hello-world';
    }

    public function boot(): void
    {
        $this->permissions(['hello-world.view' => 'Xem trang Hello World']);
        $this->onSlot('vani.admin.dashboard.cards', fn (): array => ['title' => 'Hello World', 'body' => '…']);
        $this->adminMenu('hello-world', 'Hello World', 'admin.plugins.vani-hello-world.index', 'hello-world.view');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('HelloWorld', $this->pluginPath('Resources/js/Pages'));
    }
}
```

Plugin mẫu đầy đủ (kèm test): `custom/plugin/HelloWorld/`.

## 9b. Plugin công bố extension point cho plugin khác

Designed ([commerce-kernel §7](../02-architecture/commerce-kernel.md)). Plugin đặt contract/event trong `Plugin\<Name>\Contracts`/`Events` và khai báo hook trong `custom/plugin/<Name>/hooks.php` (tên `<plugin-id>.<…>`); registry hook sẽ đọc thêm file này khi plugin được nạp. Plugin dùng khai báo `requires.plugins`. Cùng compatibility policy, theo SemVer của plugin cung cấp.

## 10. Quy tắc cho người viết plugin

1. Chỉ dùng extension point public ([catalog](../04-extension/extension-point-catalog.md)).
2. Bảng riêng `plg_<plugin>_*`; không sửa bảng hay migration của Core.
3. Gọi HTTP ra ngoài phải có timeout, retry, circuit breaker, log qua `integration_logs`, và mang correlation id.
4. Có test: unit + feature + **contract test của contract mình implement**. CI chạy test của mọi plugin.
5. Có README đặc tả (phạm vi, cấu hình, bảng dữ liệu, event nghe/phát).
6. Tuân thủ [clean-room](../01-principles/clean-room-license.md) và [architecture rules](../01-principles/architecture-rules.md).
7. Thiếu extension point thì mở PR vào Core, không vá Core từ trong plugin (rule R1).
8. Plugin vào hệ thống qua repo + CI, không upload qua Admin ([ADR-026](../19-adr/ADR-026-plugin-deploy-via-code.md)).
9. Checklist theo loại plugin: [contracts/](contracts/README.md).
