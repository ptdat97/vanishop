# ADR-011 — Money Representation

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
VND không có phần lẻ; tương lai có thể có tiền tệ khác.

## Problem
Float gây sai số; decimal dễ bị làm tròn không nhất quán giữa DB và PHP.

## Decision
- Tiền = **BIGINT minor unit** + `currency_code` ISO 4217; VND exponent 0 (`159000`).
- Value object `Money` bất biến; tỷ lệ bằng basis points; phân bổ bằng largest remainder; quy tắc làm tròn thuế/giảm giá cố định ([money](../02-architecture/money.md)).
- Cấm FLOAT/DOUBLE/DECIMAL cho tiền (rule R16).

## Alternatives
- `DECIMAL(15,2)`: thừa phần lẻ cho VND, dễ lẫn quy tắc làm tròn.
- Thư viện `moneyphp/money`: tốt nhưng thêm dependency; `Money` tự viết đủ nhỏ. Có thể thay sau nếu cần.

## Consequences
- (+) Chính xác tuyệt đối, dễ test.
- (−) Phải chuyển đổi khi hiển thị; đa tiền tệ cần exponent đúng.

## Trade-offs
Thêm một lớp VO để đổi lấy độ chính xác.
