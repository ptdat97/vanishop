# 0009 — Core tối giản, nghiệp vụ bằng plugin

- Trạng thái: Accepted
- Ngày: 2026-09-28
- Người quyết định: Owner

## Bối cảnh
Nhu cầu nghiệp vụ của từng brand và từng giai đoạn thay đổi liên tục: cổng thanh toán, hãng vận chuyển, luật khuyến mãi, loyalty, omnichannel, hoá đơn điện tử, sàn TMĐT, ERP/ODO. Nếu đưa hết vào lõi thì lõi sẽ phình to, khó test, và mỗi thay đổi nghiệp vụ đều chạm tới phần dùng chung.

## Quyết định
1. **Core** (`modules/`) chỉ chứa:
   - **Kernel nền tảng**: tenancy đa brand, phân quyền, hook/plugin loader, integration framework, localization VN.
   - **Nguyên liệu thương mại (commerce primitives)**: catalog, giá cơ bản, tồn kho + giữ hàng, khách hàng, giỏ + pipeline tính tiền, đơn hàng + máy trạng thái, khung thanh toán, khung fulfillment, đổi trả cơ bản, CMS cơ bản, khung thông báo.
   - **Điểm mở rộng** được công bố và cam kết ổn định: contract, domain event, hook, registry. Danh sách ở [10 §6](../10-hook-va-plugin.md).
   - **Bản cài mặc định tối thiểu** để bán được ngay: COD, chuyển khoản thủ công, phí ship cố định/theo bảng, vận đơn nhập tay, email.
2. **Mọi tính năng nghiệp vụ còn lại là plugin** trong `custom/plugin/`, gồm cổng thanh toán, hãng VC, khuyến mãi, loyalty, omnichannel cửa hàng, HĐĐT, SMS/ZNS, sàn TMĐT, connector ERP/ODO, báo cáo nâng cao… Danh mục ở [17](../17-danh-muc-plugin.md).
3. **Tiêu chí đưa một thứ vào core**: nó (a) là bất biến mà nhiều plugin cùng dựa vào (tiền, tồn, trạng thái đơn, phạm vi brand), **hoặc** (b) là điểm mở rộng, **hoặc** (c) thiếu nó thì không bán được đơn đầu tiên. Không thoả điều nào thì là plugin.
4. Plugin **không được vượt qua bất biến của core**: mọi thay đổi trạng thái đơn đi qua state machine, mọi thay đổi tồn đi qua reservation/movement, mọi số tiền là `Money`, mọi dữ liệu có phạm vi brand.

## Hệ quả
- (+) Core nhỏ, ổn định, test kỹ; brand bật/tắt tính năng bằng cấu hình.
- (+) Tài liệu và công sức tập trung vào core; plugin được viết song song, độc lập.
- (−) Phải thiết kế điểm mở rộng cẩn thận ngay từ đầu. Thiếu điểm mở rộng thì plugin sẽ phải "hack" core, nên nếu thiếu thì bổ sung vào core chứ không vá trong plugin.
- (−) Plugin chính thức cũng cần quản lý phiên bản và tương thích (`requires.vanishop`).
