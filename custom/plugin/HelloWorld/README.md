# vani.hello-world

Plugin mẫu dùng làm chuẩn cho người viết plugin ([docs/05-plugin/plugin-system.md](../../../docs/05-plugin/plugin-system.md)).

| Minh hoạ | Cách làm |
|---|---|
| Slot hook | `onSlot('vani.admin.dashboard.cards', …)` thêm card vào trang tổng quan |
| Menu Admin | `adminMenu(...)` — chỉ hiện khi plugin bật và nhân viên có quyền `hello-world.view` |
| Permission | `permissions([...])` |
| Trang Admin | `adminRoutes()` + `adminPages('HelloWorld', …)` → `/{VANI_ADMIN_PATH}/plugins/vani-hello-world` |

```bash
php artisan vani:plugin:install vani.hello-world
php artisan vani:plugin:enable vani.hello-world            # scope owner
php artisan vani:plugin:enable vani.hello-world --scope=brand:1
php artisan vani:plugin:disable vani.hello-world
php artisan vani:plugin:uninstall vani.hello-world
```

Không có bảng dữ liệu, không có migration.
