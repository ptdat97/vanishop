# Returns

> Vòng 1 · Mức domain: **Rich** · Tài liệu: [order.md](../../docs/09-order/order.md)

## Trách nhiệm

Yêu cầu đổi/trả, CSKH duyệt, nhận hàng, nhập kho lại, hoàn tiền hoặc đơn thay thế.

## Bất biến

- Số lượng trả mỗi dòng ≤ đã giao − đang/đã trả (`ReturnLimits`), khoá dòng đơn khi tạo.
- Tiền hoàn từ thành tiền dòng đã phân bổ giảm giá, không vượt số tính được; hoàn qua Payment.
- CSKH duyệt mọi yêu cầu (ADR-046); tự duyệt là điểm mở rộng.

## Bảng sở hữu

- `return_requests`
- `return_lines`
- `return_events`
- `return_reasons`
- `return_attachments`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `ReturnPolicy` | interface | tag `vani.returns.policies`, plugin |
| `ReturnRejected` | class | giữa module |
| `ReturnStatistics` | interface | plugin (số liệu đổi/trả cho báo cáo) |
| `Returns` | interface | giữa module |

## Event

`ReturnRequested`, `ReturnResolved`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
