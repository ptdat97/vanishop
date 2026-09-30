# ADR-027 — Plugin mở rộng dữ liệu bằng bảng riêng và `meta`, không thêm cột vào bảng Core

- Trạng thái: Accepted · Ngày: 2026-09-30 · Liên quan: [extension-point-catalog §6](../04-extension/extension-point-catalog.md), [plugin-lifecycle §5](../05-plugin/contracts/plugin-lifecycle.md), R11 · Nghiên cứu: [reference-comparison §3](../02-architecture/reference-comparison.md)

## Context
Hệ tham chiếu cho plugin thêm cột vào bảng Core (bằng migration có kiểm tra cột tồn tại) và tiêm thêm trường/quan hệ vào model Core lúc chạy. Cách này tiện, nhưng buộc mọi plugin tự dọn cột khi gỡ, và model Core có hành vi thay đổi theo plugin đang cài. Quy tắc "plugin không thêm cột vào bảng Core" đã có trong catalog nhưng chưa có ADR ghi lý do.

## Problem
Plugin cần lưu dữ liệu gắn với thực thể Core (mã số thuế trên đơn, điểm trên khách, thuộc tính riêng của sản phẩm). Lưu ở đâu?

## Decision
- Dữ liệu cần lọc, báo cáo hoặc nhiều dòng: **bảng riêng** `plg_<plugin>_*`, FK tới ID của Core (`ON DELETE RESTRICT`). Core không FK sang bảng plugin.
- Dữ liệu nhỏ đi kèm một bản ghi: cột `meta` JSON có sẵn trên `orders`, `order_lines`, `carts`, `customers`, `styles`, `variants`, **khoá theo plugin id**. Ghi qua hook (`vani.order.before_create`…), đọc lại qua DTO (`OrderData::$meta`).
- Plugin **không** thêm/đổi/xoá cột trên bảng Core, không tiêm trường/quan hệ vào model Core, không sửa migration Core. Model Core là internal (R5).
- Plugin cần dữ liệu Core → gọi service contract (`OrderReader`, `CatalogReader`…), không join bảng Core.

## Alternatives
- Thêm cột nullable vào bảng Core + tiêm fillable/quan hệ động: truy vấn nhanh, nhưng gỡ plugin phải dọn cột, migration Core có thể xung đột với cột plugin, và model Core đổi hành vi theo plugin.
- Bảng EAV chung cho mọi plugin: linh hoạt nhưng khó ràng buộc kiểu, khó index.

## Consequences
- (+) Gỡ plugin (`--purge`) chỉ xoá bảng của nó; schema Core luôn là của Core.
- (+) Model và bảng Core đổi tự do (internal) mà không phá plugin.
- (−) Đọc dữ liệu plugin kèm dữ liệu Core cần hai truy vấn hoặc filter hook bổ sung dữ liệu hiển thị.
- (−) `meta` không lọc/index được; dữ liệu cần lọc phải lên bảng riêng.

## Trade-offs
Chấp nhận thêm truy vấn để giữ ranh giới sở hữu dữ liệu (R11) và gỡ plugin sạch.
