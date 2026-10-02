# ADR-032 — Dependency được duyệt: vận hành go-live và chất lượng code

- Trạng thái: Accepted · Ngày: 2026-10-02 · Người quyết định: Owner (chọn qua câu hỏi duyệt từng nhóm)
- Liên quan: R25 (dependency phải được duyệt), [ADR-018](ADR-018-infrastructure-vietnam.md) (hạ tầng tại VN), [ADR-020](ADR-020-admin-shared-domain.md) (đường dẫn Admin bí mật), [ADR-026](ADR-026-plugin-deploy-via-code.md).

## Decision

| Gói | Loại | Dùng thế nào |
|---|---|---|
| `laravel/horizon` | runtime | Queue Redis production (`QUEUE_CONNECTION=redis`, ext `phpredis`); hàng đợi ưu tiên `notifications` → `fulfillment` → `default` → `search`. Dashboard `/{VANI_ADMIN_PATH}/system/horizon`, quyền `system.monitor`. Dev vẫn dùng driver `database` |
| `league/flysystem-aws-s3-v3` | runtime | Ảnh catalog trên S3-compatible tại VN (`VANI_MEDIA_DISK=s3`, `AWS_ENDPOINT`); disk `s3_backup` riêng cho backup |
| `spatie/laravel-backup` | runtime | Backup DB + `storage/app` hằng đêm (02:00), dọn bản cũ, `backup:monitor`; đích `VANI_BACKUP_DISKS` (mặc định `local`). Không backup mã nguồn (triển khai qua CI) |
| `laravel/pulse` | runtime | Metric tự host (request/query chậm, queue, exception) — dữ liệu ở lại VN. Dashboard `/{VANI_ADMIN_PATH}/system/pulse`, quyền `system.monitor` |
| `intervention/image` | runtime | Bản WebP 400/800/1600 px tạo khi tải ảnh (`ImageVariants`), `Media::url($width)`; ảnh gốc giữ nguyên; lỗi xử lý → dùng gốc |
| `larastan/larastan` | dev | Phân tích tĩnh level 5 + baseline (217 lỗi cũ), CI chặn lỗi mới; giảm dần baseline |
| `roave/security-advisories` | dev | Chặn cài gói có lỗ hổng đã công bố; CI thêm `composer audit` |
| `rector/rector`, `driftingly/rector-laravel` | dev | Chạy có chủ đích (`composer rector`), không tự động: lần chạy đầu đề xuất đổi ~500 file (hằng có kiểu, first-class callable…) — áp theo đợt, có review, vì đổi chữ ký public API |
| `pestphp/pest-plugin-type-coverage` | dev | CI yêu cầu ≥ 97% (hiện 97,9%) |

Không thêm: Sentry/Nightwatch (SaaS, dữ liệu lỗi ra nước ngoài — chọn Pulse). Gói cho plugin (openspout, php-qrcode, socialite, laravel-pdf) cài khi làm plugin tương ứng.

## Consequences
- (+) Đủ hạ tầng cho go-live gate: queue có giám sát, lưu trữ tại VN, backup có kiểm tra, metric tự host, ảnh tối ưu.
- (+) CI mạnh hơn: phân tích tĩnh, audit bảo mật, type coverage.
- (−) Production cần Redis + phpredis cho Horizon; Pulse ghi vào DB (cân nhắc `PULSE_DB_CONNECTION` riêng khi tải lớn).
- (−) Baseline Larastan là nợ kỹ thuật phải trả dần.
