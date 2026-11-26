# Inventory

> Vòng 1 · Mức domain: **Rich** · Tài liệu: [inventory.md](../../docs/08-inventory/inventory.md)

## Trách nhiệm

Tồn theo (kho, SKU), giữ hàng atomic, sổ biến động, đối soát nội bộ, đặt trước có giới hạn và hàng đang về (ADR-047), phiếu nhập nhanh (stabilization A6), kiểm kê nhanh (A7).

## Bất biến

- Giữ hàng atomic: khoá dòng tồn, kiểm ATS, ghi reservation trong một transaction (R14).
- ATS = Σ max(0, on_hand − reserved − safety); `InventoryStrategy` chỉ giảm; hạn mức đặt trước chỉ ở kho bù tồn.
- `stock_movements` chỉ thêm (R17); mọi thay đổi tồn qua service của module (R30).
- Kiểm kê ghi phần chênh `số đếm − tồn lúc đếm` (hàng bán trong lúc đếm không bị ghi đè), một lần mỗi phiên.
- Phiếu nhập cộng tồn đúng một lần (khoá phiếu, trạng thái `draft` → `received`); phiếu đã nhập không sửa.

## Bảng sở hữu

- `locations`
- `stock_levels`
- `stock_reservations`
- `stock_movements`
- `inventory_reconciliations`
- `inventory_reconciliation_lines`
- `incoming_stock`
- `stock_receipts`
- `stock_receipt_lines`
- `stock_counts`
- `stock_count_lines`
- `stock_grid_index`
- `stock_grid_options`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `AvailabilityReader` | interface | plugin |
| `BackorderPolicy` | interface | tag `vani.inventory.backorder_policies`, plugin (experimental) |
| `Backorders` | interface | plugin (experimental) |
| `IncomingStock` | interface | plugin (experimental) |
| `InventoryReconciliation` | interface | plugin |
| `InventoryReservation` | interface | giữa module |
| `InventoryReturns` | interface | giữa module |
| `InventoryStrategy` | interface | tag `vani.inventory.strategies`, plugin |
| `InventorySync` | interface | giữa module |
| `LocationRegistry` | interface | plugin |
| `NotStockAuthority` | class | giữa module |
| `StockImporter` | interface | giữa module |
| `StockMovementRejected` | class | plugin |
| `StockMovements` | interface | plugin |
| `StockPageLink` | interface | tag `vani.inventory.stock_links`, plugin |
| `StockUnavailable` | class | giữa module |

## Event

`AvailabilityChanged`, `StockAdjusted`, `StockCommitted`, `StockReleased`, `StockReserved`, `StockTransferCancelled`, `StockTransferCreated`, `StockTransferReceived`, `StockTransferShipped`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
