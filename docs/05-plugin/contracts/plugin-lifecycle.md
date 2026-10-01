# Contract: vòng đời và cấu trúc plugin

> Kiểm chứng từ: `modules/Extension/Domain/Plugin/PluginManifest.php`, `Application/Plugins/PluginManager.php`, `PluginLoader.php`, `PluginServiceProvider.php`, `custom/plugin/*`. Thiết kế tổng: [plugin-system](../plugin-system.md).

## 1. Manifest `vanishop.json`

```json
{
  "id": "vani.vietqr",
  "name": { "vi": "Thanh toán VietQR", "en": "VietQR Payment Gateway" },
  "version": "0.1.0",
  "kind": "payment_gateway",
  "provider": "Plugin\\VietQr\\VietQrServiceProvider",
  "requires": { "vanishop": "^0.2", "plugins": {} },
  "conflicts": [],
  "permissions": ["payments.view"],
  "author": "VaniShop Team"
}
```

| Trường | Bắt buộc | Kiểm tra của loader (sai → `InvalidManifest`, plugin không được liệt kê) |
|---|---|---|
| `id` | ✔ | Dạng `vendor.name`, chữ thường/số/gạch ngang. Không đổi sau khi phát hành |
| `name` | ✔ | Chuỗi hoặc `{locale: tên}` |
| `version` | ✔ | SemVer `x.y.z[-pre]` |
| `kind` | ✔ | Phân loại hiển thị (`payment_gateway`, `shipping_carrier`, `promotion`, `connector`, `notification_channel`, `storefront`, `business`, `theme_extension`) |
| `provider` | ✔ | FQCN của provider, extends `Modules\Extension\PluginServiceProvider` |
| `requires.vanishop` | ✔ | Ràng buộc Composer semver với `config('vanishop.version')` (hiện `0.2.0`) |
| `requires.plugins` | | `{ "plugin.id": "^x.y" }` → sắp thứ tự nạp, chặn bật khi thiếu |
| `conflicts` | | Kiểm tra hai chiều |
| `scopes` | | **Deprecated** ([ADR-028](../../19-adr/ADR-028-single-store-brand-as-catalog.md)): plugin bật/tắt toàn cửa hàng. Code hiện vẫn đọc (`owner`, `brand`, `channel`) cho tới slice 12 |
| `permissions` | | Hiển thị khi cài |

## 2. Cấu trúc thư mục

```text
custom/plugin/<Name>/                 # namespace Plugin\<Name>\
├── vanishop.json
├── <Name>ServiceProvider.php
├── Contracts/ Events/                # (tuỳ chọn) API cho plugin khác
├── Domain/ Application/              # quy tắc + use case của plugin
├── Infrastructure/                   # client HTTP, implementation của contract Core
├── Http/{Controllers,routes}/        # routes/admin.php, routes/webhooks.php
├── Database/migrations/              # bảng plg_<plugin>_* — install chạy thư mục này
├── Resources/{lang,js/Pages}/
├── Config/                           # mặc định; cấu hình chỉnh trong Admin dùng settings() (§4)
├── README.md                         # phạm vi, cấu hình, bảng, event nghe/phát
└── Tests/{Unit,Feature,Contract}/
```

## 3. Vòng đời

| Lệnh | Điều kiện | Việc Core làm |
|---|---|---|
| `vani:plugin:install <id>` | Chưa cài; deps/conflict/`requires.vanishop` đạt | Chạy migration trong `Database/migrations` → `installed`. Migration lỗi → `failed` + `last_error` |
| `vani:plugin:enable <id>` | Không `failed`; deps đã bật | → `enabled`, audit. (Code hiện còn `--scope` + `plugin_scopes`, bỏ ở slice 12) |
| `vani:plugin:disable <id>` | Không còn plugin phụ thuộc đang bật | → `disabled`. **Không đụng dữ liệu** |
| `vani:plugin:uninstall <id> [--purge]` | Đã tắt; không còn plugin phụ thuộc đã cài | Mặc định **giữ bảng**. `--purge`: chạy `down()` theo thứ tự ngược |

Sau mỗi thay đổi trạng thái: dựng lại `bootstrap/cache/vanishop-plugins.php`, xoá cache trạng thái bật (`vani:plugins:enabled-scopes`). Plugin `installed`/`disabled` vẫn được `register()` (để route Admin cấu hình, webhook chạy được) nhưng listener/implementation **không có hiệu lực**. Plugin `failed` không được nạp.

Plugin chỉ vào hệ thống qua **mã nguồn + CI**, không upload qua Admin ([ADR-026](../../19-adr/ADR-026-plugin-deploy-via-code.md)).

## 4. `PluginServiceProvider`: chỉ đăng ký, không chứa logic

```php
final class VietQrServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string { return 'vani.vietqr'; }   // trùng manifest

    public function register(): void { /* bind implementation, merge config */ }

    public function boot(): void
    {
        $this->contribute(PaymentGateway::TAG, VietQrGateway::class);
    }
}
```

| Helper | Tác dụng | Hiệu lực |
|---|---|---|
| `contribute($tag, $class)` | Đóng góp implementation cho extension point. **Dùng hằng `TAG` trên interface**, không gõ chuỗi | Chỉ khi plugin bật |
| `onFilter/onAction/onValidate/onSlot($hook, $cb, $priority = 10)` | Nghe hook public ([hook-signatures](hook-signatures.md)) | Chỉ khi plugin bật |
| `onEvent($event, $handler)` | Nghe domain event | Chỉ khi plugin bật |
| `settings([...])` | Khai báo cấu hình → Admin → Cấu hình sinh form | Đọc bằng `Settings::current($pluginId, $key, $default)` |
| `schedule(fn (Schedule $s) => …)` | Tác vụ định kỳ | Chạy khi plugin bật; chạy với actor `system` |
| `adminMenu`, `permissions`, `adminRoutes`, `adminPages` | Màn hình Admin | Route trả 404 khi plugin không active |
| `webhookRoutes($file)` | `/api/integrations/{slug}/…` | Luôn đăng ký (plugin tự kiểm tra) |
| `migrations`, `translations` | Nạp migration/lang | — |

**Không** gọi thẳng `Hook::onFilter()`, `Event::listen()` hay `app()->tag()`: listener/implementation khi đó chạy cả khi plugin đã tắt.

## 5. Dữ liệu của plugin

| Được | Không được |
|---|---|
| Bảng riêng `plg_<plugin>_*`, FK tới ID của Core (`ON DELETE RESTRICT`) | Thêm/đổi/xoá cột trên bảng Core ([ADR-027](../../19-adr/ADR-027-plugin-data-no-core-columns.md)) |
| `meta` JSON trên `orders`, `order_lines`, `carts`, `customers`, `styles`, `variants`, **khoá theo plugin id** | Dùng `meta` cho dữ liệu cần lọc/báo cáo |
| Migration idempotent (`Schema::hasTable` trước khi tạo), `down()` dọn sạch bảng của mình | Sửa migration Core; Core FK sang bảng plugin |

MySQL không rollback DDL: migration lỗi giữa chừng → plugin `failed`; sửa lỗi rồi cài lại phải chạy được từ trạng thái dở dang.

## 6. Checklist

- [ ] `vanishop.json` đủ trường bắt buộc; `requires.vanishop` khớp phiên bản Core
- [ ] Provider chỉ đăng ký; logic ở `Application`/`Infrastructure`
- [ ] Dùng `contribute()`/`on*()` của `PluginServiceProvider`, không đăng ký trực tiếp vào container/eventy/event
- [ ] Tag lấy từ hằng `TAG` của interface trong `Contracts`
- [ ] Chỉ dùng `Contracts`, `Events`, hook public, `Shared\Domain` (arch test R5)
- [ ] Bảng `plg_<plugin>_*`, migration idempotent, `down()` dọn sạch
- [ ] Cấu hình chỉnh trong Admin qua `settings()`; secret khai báo type `secret` (mã hoá)
- [ ] Unit + feature + **contract test** của contract mình implement
- [ ] README đặc tả; tuân thủ [clean-room](../../01-principles/clean-room-license.md)

## Giới hạn hiện tại

- `vani:plugin:upgrade`, `vani:plugin:doctor`: Designed. Tăng version plugin hiện phải cài lại.
- Code hiện còn bật plugin theo scope `owner`/`brand`/`channel`; gỡ ở slice 12 ([store-and-brand §6](../../12-store/store-and-brand.md)).
- Plugin có sẵn (`VietQr`, `Ghn`) vẫn đọc cấu hình từ `Config/*.php` + `.env`; chỉ `vani.sms-brandname` đọc qua `Settings`.
