# Tenancy

> Vòng 0 · Mức domain: **CRUD** · Tài liệu: [ADR-028](../../docs/19-adr/ADR-028-single-store-brand-as-catalog.md)

## Trách nhiệm

Cửa hàng và cấu hình theo namespace (core + plugin).

## Bất biến

- Một cửa hàng, một người bán (ADR-028).
- Cấu hình đọc qua `Settings`; giá trị mặc định ở `config/vanishop.php`.

## Bảng sở hữu

- `legal_entities`
- `settings`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `LegalEntityDirectory` | interface | giữa module |
| `Settings` | interface | plugin |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
