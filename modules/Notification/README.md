# Notification

> Vòng 1 · Mức domain: **CRUD (khung)** · Tài liệu: [notification.md](../../docs/03-domains/notification.md)

## Trách nhiệm

Mẫu tin theo loại × kênh × ngôn ngữ, gửi bất đồng bộ idempotent, kênh gửi là plugin.

## Bất biến

- Mỗi tin có idempotency key; gửi lại không gửi trùng.
- Tin marketing cần consent; tin giao dịch luôn gửi.

## Bảng sở hữu

- `notification_templates`
- `notification_logs`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `NotificationCatalog` | interface | plugin |
| `NotificationChannel` | interface | tag `vani.notification.channels`, plugin |
| `Notifier` | interface | plugin |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
