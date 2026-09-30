# Contract cho người viết plugin

> Trạng thái: **Implemented** cho các contract liệt kê dưới đây (kiểm chứng từ `modules/*/Contracts/`, `modules/Extension/`). Danh mục đầy đủ extension point: [extension-point-catalog](../../04-extension/extension-point-catalog.md). Cơ chế và policy: [extension-model](../../04-extension/extension-model.md).

Thư mục này trả lời câu hỏi **"viết plugin loại X thì phải implement chính xác cái gì, Core gọi lúc nào, sai thì chuyện gì xảy ra"**. Mỗi tài liệu có: chữ ký lấy từ code, thời điểm Core gọi (trong hay ngoài transaction), trách nhiệm plugin vs Core, lỗi thường gặp, checklist.

| Tài liệu | Dùng khi |
|---|---|
| [plugin-lifecycle.md](plugin-lifecycle.md) | Mọi plugin: manifest, cấu trúc, `PluginServiceProvider`, install/enable/disable/uninstall, migration, settings |
| [hook-signatures.md](hook-signatures.md) | Plugin nghe hook (filter/action/validate/slot) hoặc domain event |
| [payment-gateway.md](payment-gateway.md) | Plugin cổng thanh toán (`kind: payment_gateway`) |
| [shipping-carrier.md](shipping-carrier.md) | Plugin hãng vận chuyển (`kind: shipping_carrier`) |

## Quy ước

- Chữ ký trong thư mục này **chép từ code VaniShop**, không từ tài liệu khác. Khi code đổi, cập nhật tại đây cùng PR và ghi [CHANGELOG-extension](../../04-extension/CHANGELOG-extension.md).
- Chỗ nào code còn thiếu so với thiết kế, ghi ở mục **Giới hạn hiện tại** của từng tài liệu, không giấu.
- Mỗi extension contract có **bộ contract test** trong `Modules\<Ctx>\Testing`; plugin implement contract nào thì chạy bộ đó (rule R26).
- Viết thêm tài liệu ở đây khi một extension point có plugin thật đầu tiên (ưu tiên kế tiếp: `PromotionRule`, `NotificationChannel`, `Connector`).
