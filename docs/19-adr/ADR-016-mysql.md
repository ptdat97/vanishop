# 0003 — MySQL cho cơ sở dữ liệu

- Trạng thái: Accepted
- Ngày: 2026-09-28
- Người quyết định: Owner

## Bối cảnh
Cần khoá dòng cho reservation, `SKIP LOCKED` cho outbox, cột JSON cho payload/cấu hình, hỗ trợ tốt từ nhà cung cấp hạ tầng tại Việt Nam và đội ngũ quen thuộc.

## Quyết định
Dùng **MySQL 8.4 LTS** (InnoDB, `utf8mb4`, `utf8mb4_0900_ai_ci`, transaction isolation `READ COMMITTED`) cho local, CI (feature/concurrency test), staging, production. SQLite chỉ cho unit test không phụ thuộc DB đặc thù.

## Hệ quả và cách bù trừ
| Thiếu so với PostgreSQL | Cách làm trên MySQL |
|---|---|
| Partial index | Index ghép `(status, next_attempt_at)` |
| JSONB có index | Cột `json` + generated column có index khi cần lọc |
| `unaccent` | Cột `search_text` chuẩn hoá bỏ dấu + collation `ai_ci`; Meilisearch cho storefront |
| Sequence theo phạm vi | Bảng `number_sequences` + `SELECT … FOR UPDATE` |
| DDL transactional | Migration nhỏ, idempotent; online schema change cho bảng lớn |

- (+) Phổ biến ở hosting/managed DB tại VN, đội quen thuộc.
- (−) Không FK trên bảng partition; cần cẩn trọng deadlock khi khoá nhiều dòng tồn (luôn khoá theo thứ tự `variant_id` tăng dần).

## Phương án đã cân nhắc
- **PostgreSQL 16**: nhiều tính năng tiện hơn (partial index, unaccent), nhưng Owner chọn MySQL.
