# 0006 — Plugin tại `custom/plugin/`

- Trạng thái: Accepted
- Ngày: 2026-09-28
- Người quyết định: Owner

## Bối cảnh
Connector (ERP, cổng TT, hãng VC, HĐĐT, sàn…) và tính năng mở rộng cần tách khỏi lõi, bật/tắt theo phạm vi, dùng lại giữa các môi trường.

## Quyết định
- Plugin đặt tại **`custom/plugin/<Tên>/`**, namespace `Plugin\<Tên>\`, autoload PSR-4 `"Plugin\\": "custom/plugin/"`.
- Mỗi plugin có manifest `vanishop.json` + `ServiceProvider` kế thừa `PluginServiceProvider` (xem [10](../10-hook-va-plugin.md)).
- Theme storefront theo brand đặt cùng nhóm tại **`custom/theme/<tên>/`**.
- Khi một plugin cần dùng lại ở nơi khác: tách thành Composer package private, giữ nguyên namespace.

## Hệ quả
- (+) Tách rõ lõi (`modules/`) và phần tuỳ biến (`custom/`); dễ rà soát, dễ bật/tắt.
- (−) Plugin nằm cùng repo nên cần CI chạy test của cả `custom/plugin/*/tests`.
