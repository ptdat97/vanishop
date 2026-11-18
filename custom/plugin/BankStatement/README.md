# vani.bank-statement

Đối soát sao kê ngân hàng — plugin tham chiếu EP v3 X5 ([extension-point-catalog](../../../docs/04-extension/extension-point-catalog.md)).

Trang Thanh toán → **Nhập sao kê ngân hàng** (cột `Mã GD`, `Số tiền`, `Nội dung`):
- nội dung chứa số đơn và số tiền đúng bằng khoản chờ → xác nhận đã nhận tiền (`Payments::confirmManually`);
- không thấy số đơn, lệch tiền, đơn không chờ chuyển khoản → dòng lệch trong `Reconciliations` (khu "Đối soát lệch", ô Việc cần làm);
- nhập lại cùng giao dịch → bỏ qua (theo mã giao dịch).
