# ADR-003 — Plugin Architecture

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner

## Context
Nghiệp vụ thay đổi liên tục: cổng thanh toán, hãng vận chuyển, luật khuyến mãi, loyalty, ERP, marketplace, creator. Owner muốn tài liệu và Core tập trung vào phần lõi.

## Problem
Làm sao thêm capability mà không sửa và không fork Core, và nâng cấp Core an toàn?

## Decision
- **Core tối giản** ([commerce-kernel](../02-architecture/commerce-kernel.md)): primitives, invariants, extension points, và mặc định tối thiểu để bán được (COD, chuyển khoản thủ công, flat rate, vận đơn tay, email).
- **Plugin** đặt tại `custom/plugin/<Name>/` (namespace `Plugin\`), theme tại `custom/theme/`.
- Manifest `vanishop.json` (id, version, kind, requires, conflicts, scopes, permissions, settings schema); lifecycle `discovered → installed → enabled → disabled → uninstalled` (+ `failed`); CLI `vani:plugin:*`; dependency resolution theo semver ([plugin-system](../05-plugin/plugin-system.md)).
- Hướng phụ thuộc: Plugin → Core Contracts → Core. Cấm Core → Plugin.

## Alternatives
- Đưa mọi nghiệp vụ vào Core: Core phình to, mỗi thay đổi nghiệp vụ chạm phần dùng chung.
- Composer package riêng cho mỗi plugin ngay từ đầu: tốn chi phí release. Chỉ tách khi cần dùng lại ở nơi khác.

## Consequences
- (+) Brand bật/tắt capability bằng cấu hình; plugin phát triển song song.
- (−) Phải thiết kế extension point kỹ; loader, safe mode, contract test là chi phí ban đầu.

## Trade-offs
Chi phí thiết kế extension point ban đầu để đổi lấy việc không phải fork Core về lâu dài.
