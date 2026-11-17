# Integration

> Vòng 1 · Mức domain: **Khung (đóng băng tăng trưởng)** · Tài liệu: [integration-platform.md](../../docs/11-integration/integration-platform.md)

## Trách nhiệm

Outbox/inbox, event feed, webhook HMAC, Integration API v1, connector là plugin. **Đóng băng tăng trưởng** (architecture-consolidation C3): chỉ sửa lỗi cho tới khi Owner mở lại ERP.

## Bất biến

- Mọi tác vụ ra ngoài qua outbox sau commit (R12); idempotent theo event id (R13).
- Payload không lộ id tự tăng nội bộ; schema event có version (R32).

## Bảng sở hữu

- `integration_clients`
- `integration_client_keys`
- `integration_webhook_subscriptions`
- `integration_events`
- `integration_outbox`
- `integration_inbox`
- `external_references`
- `integration_mappings`
- `integration_reconciliations`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `Connector` | interface | tag `vani.integration.connectors`, plugin |
| `ExternalReferences` | interface | giữa module |
| `InboundHandler` | interface | tag `vani.integration.inbound`, plugin |
| `Inbox` | interface | giữa module |
| `IntegrationAccessDenied` | class | giữa module |
| `IntegrationEvents` | interface | giữa module |
| `MappingMissing` | class | giữa module |
| `Mappings` | interface | giữa module |

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
