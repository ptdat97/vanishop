# ADR — Architecture Decision Records

Mỗi quyết định kiến trúc quan trọng được ghi lại thành 1 file `NNNN-ten-quyet-dinh.md` theo mẫu bên dưới. ADR đã **Accepted** thì không sửa nội dung; khi đổi quyết định, tạo ADR mới và đánh dấu ADR cũ là *Superseded by NNNN*.

| # | Quyết định | Trạng thái |
|---|---|---|
| [0001](0001-modular-monolith.md) | Modular monolith trên Laravel, module tại `modules/` | Accepted |
| [0002](0002-single-database-brand-scope.md) | Một database, cô lập theo phạm vi brand | Accepted |
| [0003](0003-mysql.md) | MySQL 8.4 cho cơ sở dữ liệu | Accepted |
| [0004](0004-transactional-outbox.md) | Transactional outbox cho tích hợp | Accepted |
| [0005](0005-admin-ui.md) | Admin bằng Inertia + Vue 3 | Accepted |
| [0006](0006-extension-packaging.md) | Plugin tại `custom/plugin/`, theme tại `custom/theme/` | Accepted |
| [0007](0007-integration-module-odo-deferred.md) | Hoãn vai trò ODO; module Integration cung cấp API tích hợp | Accepted |
| [0008](0008-ha-tang-tai-viet-nam.md) | Hạ tầng và dữ liệu đặt tại Việt Nam | Accepted |
| [0009](0009-core-toi-gian-nghiep-vu-bang-plugin.md) | Core tối giản, nghiệp vụ bằng plugin | Accepted |

## Mẫu

```markdown
# NNNN — Tiêu đề

- Trạng thái: Proposed | Accepted | Superseded by NNNN
- Ngày: YYYY-MM-DD
- Người quyết định: ...

## Bối cảnh
## Quyết định
## Hệ quả (tích cực / tiêu cực)
## Phương án đã cân nhắc
```
