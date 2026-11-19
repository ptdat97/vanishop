# vani.cod-risk-guard

Chống bom hàng COD — plugin tham chiếu cho EP v3 X2 ([extension-point-catalog](../../../docs/04-extension/extension-point-catalog.md), [ADR-046](../../../docs/19-adr/ADR-046-staffed-operations-vietnam.md)).

- `OrderRiskSignal`: SĐT trong danh sách đen → rủi ro **cao**; SĐT đã có ≥ N đơn hoàn về → **cao**, ít hơn → **trung bình**. Hiện trên dòng "Cần xác nhận" và trang đơn.
- Hook `vani.order.before_confirm`: chặn xác nhận đơn của SĐT trong danh sách đen (nhân viên bấm, xác nhận hàng loạt, tự xác nhận của `vani.cod`). Nhân viên gọi khách, gỡ số khỏi danh sách đen rồi xác nhận.

Cấu hình: Admin → Cài đặt → `vani.cod-risk-guard` (danh sách đen — mỗi dòng một số; bật/tắt chặn; ngưỡng đơn hoàn về).
