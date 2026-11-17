# Extension

> Vòng 0 · Mức domain: **Microkernel** · Tài liệu: [extension-model.md](../../docs/04-extension/extension-model.md)

## Trách nhiệm

Vòng đời plugin, registry implementation theo tag, hook có khai báo, phần mở rộng Admin/storefront, circuit breaker, version API cho plugin (ADR-048).

## Bất biến

- Plugin chỉ dùng class `#[PluginApi]` (R5); Core không import `Plugin\` (R4).
- Hook phải khai báo trong `hooks.php`; plugin không nghe hook internal.
- Plugin không tương thích `requires.extension_api`/`requires.vanishop` không được nạp; lỗi boot không làm sập Core.

## Bảng sở hữu

- `plugins`
- `plugin_seeders`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `AccountPages` | interface | giữa module |
| `AdminScreen` | interface | giữa module |
| `DashboardWidget` | interface | tag `vani.admin.dashboard.widgets`, plugin |
| `Extensions` | interface | plugin |
| `PluginDirectory` | interface | giữa module |
| `PluginHealthCheck` | interface | tag `vani.health.checks`, plugin |
| `PluginViews` | interface | giữa module |
| `ReportProvider` | interface | tag `vani.admin.reports`, plugin |
| `Requirement` | enum | plugin |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
