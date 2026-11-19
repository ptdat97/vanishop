# Identity

> Vòng 0 · Mức domain: **Rich (RBAC)** · Tài liệu: [security.md](../../docs/15-security/security.md)

## Trách nhiệm

Nhân viên, vai trò theo phạm vi (Owner/location), đăng nhập Admin, nhật ký audit; danh mục quyền, phạm vi và menu Admin (contract).

## Bất biến

- Mọi thao tác Admin kiểm quyền qua `Authorizer`/Gate theo phạm vi.
- `audit_logs` chỉ thêm, không sửa/xoá (R17, R31).
- Nhân viên không tự khoá/đổi vai trò của mình; chỉ Owner cấp quyền `*`.

## Bảng sở hữu

- `staff_users`
- `roles`
- `role_permissions`
- `staff_role_assignments`
- `audit_logs`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `AdminNavigation` | interface | giữa module |
| `AuditLogger` | interface | plugin |
| `Authorizer` | interface | giữa module |
| `PermissionRegistry` | interface | giữa module |
| `StaffRejected` | class | giữa module |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
