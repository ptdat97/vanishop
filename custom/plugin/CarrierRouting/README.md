# vani.carrier-routing

Chọn hãng vận chuyển cho vận đơn mới theo quy tắc của shop — tham chiếu cho `CarrierAssignment` (EP v3 X3, [extension-point-catalog](../../../docs/04-extension/extension-point-catalog.md)).

Cấu hình: Admin → Cài đặt → `vani.carrier-routing` → "Quy tắc chọn hãng", mỗi dòng `điều kiện = hãng[:dịch vụ]`:

```
tinh:79 = ahamove
cod>=3000000 = ghtk
* = ghn:standard
```

Lý do chọn hãng ghi vào lịch sử vận đơn. Hãng chưa bật thì Core bỏ qua dòng đó và dùng quy tắc mặc định (phương thức khách chọn → hãng mặc định).
