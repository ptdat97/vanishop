# ADR-042 — Tách vận hành kho thành plugin `vani.warehouse`; tồn kho (Inventory) ở lại Core

- Trạng thái: **Accepted** · Ngày: 2026-11-06 · Người quyết định: Owner
- Lịch sử: bản đầu (cùng ngày) đề xuất tách cả Inventory thành plugin `vani.inventory`; Owner đổi hướng — **giữ Inventory trong Core để an toàn cho bất biến, chỉ tách Warehouse**.
- Liên quan: [ADR-029](ADR-029-commerce-microkernel.md) (giữ nguyên §6: reservation là bất biến Core), [ADR-006](ADR-006-inventory-authority.md) (authority), [ADR-027](ADR-027-plugin-data-no-core-columns.md), [inventory](../08-inventory/inventory.md).

## Context
- VaniShop phải phục vụ cả cửa hàng thời trang nhỏ (một kho, chỉ nhập/kiểm tồn) lẫn doanh nghiệp nhiều kho, quy trình kho phức tạp — không bắt mọi cửa hàng mang theo một hệ thống quản lý kho lớn.
- Module `Inventory` gộp: (a) **engine tồn** — giữ/nhả/chốt hàng, sổ biến động append-only, ATS, đồng bộ số tuyệt đối từ authority ngoài, engine đối soát, kiểm tra bất biến; (b) **vận hành kho** — quản lý nhiều kho (loại, khả năng, ưu tiên, authority ERP/POS), chuyển kho, đối soát snapshot nguồn ngoài. Admin hiện cả (b) cho mọi cửa hàng → khó cấu hình.
- (a) là bất biến R6/R14 dùng chung transaction với PlaceOrder — ADR-029 §6 giữ ở Core.

## Decision
1. **Inventory (Core) giữ engine và dữ liệu tồn**: bảng `locations`, `stock_levels`, `stock_reservations`, `stock_movements`, `inventory_reconciliations(_lines)`; giữ/nhả/chốt, ATS, nhập lại hàng trả, `InventorySync` (đồng bộ authority), engine đối soát + `vani:inventory:verify`, lưới tồn (sửa ô, chọn nhiều, CSV), lịch sử biến động, trang Đối soát (chỉ đọc). Admin Core chỉ có **"Kho chính"** (tạo tự động; sửa tên/địa chỉ).
2. **`vani.warehouse` (plugin, không bundled)** — bật khi cửa hàng có nhiều kho / quy trình kho:
   - Quản lý kho/cửa hàng đầy đủ: thêm kho, loại, giao online / nhận tại quầy / nhận hàng trả, thứ tự ưu tiên giữ hàng, **authority ngoài** (kho do ERP/POS quản lý → chỉ nhận đồng bộ).
   - Chuyển kho `pending → shipped → received`, `cancelled` (bảng `plg_warehouse_transfers(_lines)`; event `Inventory\Events\StockTransfer*` giữ ở Core, plugin phát).
   - Đối soát nguồn ngoài bằng snapshot (`vani:warehouse:reconcile`).
3. **Core ↔ plugin chỉ qua contract** (plugin không đọc/ghi bảng tồn):
   - `Inventory\Contracts\LocationRegistry` — đọc/tạo/sửa kho (`LocationData`), optimistic lock, audit.
   - `Inventory\Contracts\StockMovements` — `transferOut`/`transferIn` tại một kho: khoá dòng tồn, ghi sổ biến động, phát `AvailabilityChanged`; gọi trong transaction của bên gọi (chung DB) nên đổi trạng thái phiếu + tồn là một transaction.
   - `Inventory\Contracts\InventoryReconciliation` — `reconcileExternal(source, rows, apply)`.
   - Test kiến trúc: plugin không nhắc tên bảng tồn của Core.
4. **ERP/POS thuộc Warehouse về vận hành**: engine `InventorySync` + Integration API ở lại Core (bất biến `sync_version`, chỉ authority được ghi), nhưng chỉ `vani.warehouse` đặt được authority ngoài cho kho — không có plugin thì mọi kho do VaniShop quản lý và API đồng bộ trả `not_data_owner`.
5. Tắt `vani.warehouse`: dữ liệu giữ nguyên; kho thêm trước đó vẫn được engine dùng (giữ hàng theo ưu tiên, ATS); phiếu chuyển đang `shipped` chờ bật lại để nhận. Doctor/guard: chặn tắt khi còn phiếu `shipped`.

## Alternatives
- **Tách cả Inventory thành plugin** (bản đầu): Core mỏng hơn, thay được engine tồn — nhưng bất biến không còn ở Core, đổi lớn (Core 0.4.0). Owner không chọn.
- **Giữ nguyên, ẩn UI theo "chế độ đơn giản"**: rẻ, nhưng Core vẫn mang toàn bộ vận hành kho.

## Consequences
- (+) Cửa hàng nhỏ: một kho, không thấy chuyển kho/authority/đối soát ngoài. Doanh nghiệp: bật `vani.warehouse`.
- (+) Bất biến tồn ở nguyên Core, contract test và concurrency test giữ nguyên.
- (−) Thêm 3 contract public; quyền `inventory.transfer` thành `warehouse.transfer`; route Admin chuyển kho/kho & cửa hàng chuyển sang `/{admin}/plugins/vani-warehouse/…`.
- (−) Chuyển kho cần plugin bật; cửa hàng đang dùng chuyển kho phải cài `vani.warehouse` khi nâng cấp (migration đổi tên bảng giữ dữ liệu).
