# ADR-047 — Đặt trước có giới hạn và hàng đang về

- Trạng thái: **Accepted** · Code: **Implemented** (Core 0.4.65, 2026-11-16, EP v3 X7) · Ngày: 2026-11-16 · Người quyết định: Owner (giao làm cùng đợt X1–X7)
- Liên quan: [extension-point-catalog](../04-extension/extension-point-catalog.md), [extension-model §2](../04-extension/extension-model.md) (bất biến ATS), [inventory](../08-inventory/inventory.md), [ADR-046](ADR-046-staffed-operations-vietnam.md) (nhân viên đẩy đơn lên hãng).

## Context

- Shop thời trang hay **mở bán đặt trước** bộ sưu tập mới: hàng đang may/đang về, giao sau 10–15 ngày, chỉ mở một số suất ("50 suất đầu").
- Core cho phép bán vượt tồn ở đúng một trường hợp: sản phẩm **không theo dõi tồn** (0.4.48) — bù tồn ảo vào kho bù tồn. Cách đó sai cho hàng có theo dõi tồn: hàng về nhập +N thì tồn ảo cộng thêm, đếm trùng.
- Bất biến hiện tại: "`InventoryStrategy` chỉ được **giảm** ATS". Đặt trước cần **tăng** ATS — phải quyết định ở cấp kiến trúc, không mở `InventoryStrategy`.

## Decision

1. **Hạn mức đặt trước theo SKU, không có bộ đếm.** Contract `BackorderPolicy` (tag `vani.inventory.backorder_policies`) trả hạn mức `limit` + ngày dự kiến có hàng cho từng SKU. Core cho phép **số đang giữ vượt tồn thật tối đa `limit`** tại **kho bù tồn** (kho giao online đầu tiên do VaniShop quản lý tồn — `OnlineLocations::topUpLocation`):

   ```
   ATS(kho bù tồn) = max(0, on_hand − reserved − safety_stock + limit)
   đang chờ hàng   = max(0, reserved + safety_stock − on_hand)   ≤ limit
   ```

   Không có bảng/bộ đếm "đã bán đặt trước": số chờ hàng suy ra từ tồn — huỷ đơn tự trả suất, hàng về (nhập kho, kiểm kê, chuyển kho đến) tự lấp suất. Không có kho bù tồn (mọi kho online do ERP/POS quản lý) → không đặt trước.
2. **Hàng thật trước, suất đặt trước sau.** Giữ hàng phân bổ ATS thật ở mọi kho online trước, phần thiếu mới lấy hạn mức ở kho bù tồn. Hàng về lấp cho đơn **cũ trước** (FIFO theo thứ tự giữ hàng): `Backorders::waitingFor(reservationKey)` trả số còn chờ của một đơn.
3. **Chỉ áp cho sản phẩm có theo dõi tồn.** Sản phẩm không theo dõi tồn vẫn theo cơ chế bù tồn 0.4.48.
4. **`InventoryStrategy` giữ nguyên bất biến** "chỉ giảm": Core áp strategy lên ATS đã cộng hạn mức, rồi lấy `min`. Nhiều `BackorderPolicy` cùng trả cho một SKU → lấy hạn mức lớn nhất (cùng ngày dự kiến của nó). Policy lỗi → bỏ qua (hạn mức 0), không chặn bán hàng thật.
5. **Hàng đang về là dữ liệu Core, không phải hạn mức.** Service contract `IncomingStock` (bảng `incoming_stock`: SKU, số lượng, ngày dự kiến, nguồn): nhân viên/plugin/ERP ghi; plugin đọc để hiển thị ("Hàng đang về") và để tính hạn mức. Core không tự cộng hàng đang về vào ATS.
6. **Có người ở chốt đẩy hãng (ADR-046).** Đơn còn SKU chờ hàng không được đẩy lên hãng — plugin chặn qua hook `vani.fulfillment.before_push` (tham chiếu `vani.preorder`); nhân viên thấy lý do trên trang vận đơn.

## Consequences

- Không có plugin đăng ký `BackorderPolicy` → hành vi y như cũ (hạn mức 0), không đổi dữ liệu.
- `StockLevel::reserve($qty, $backorder)` và `Allocation::allocate(..., $backorders)` nhận thêm hạn mức; mọi kiểm tra giữ hàng vẫn trong cùng transaction + khoá dòng (R14).
- Hàng về thì **hạn mức cũ vẫn còn** nếu plugin không hạ: `vani.preorder` tính hạn mức từ hàng đang về và trừ hàng đang về khi nhập kho (`StockAdjusted`).
- Báo cáo tồn có thể thấy `reserved > on_hand` ở kho bù tồn — đó là số đơn chờ hàng, hiển thị "Chờ hàng".
- Không làm: đơn tách giao (phần có hàng giao trước), đặt cọc một phần, tự huỷ đơn đặt trước quá hạn — để plugin/đợt sau (backlog §6b).
