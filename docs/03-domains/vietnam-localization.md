# Việt Nam: localization & compliance

> Trạng thái: **Designed**.

> Các mục pháp lý dưới đây là **tóm tắt để định hướng kỹ thuật**, cần bộ phận pháp chế xác nhận văn bản hiện hành trước khi go-live (quy định thay đổi thường xuyên).

> **Core** đảm nhận các nền tảng VN: địa giới hành chính (§1), SĐT/tên/tiếng Việt (§2), tiền tệ/ngày (§3), các yêu cầu pháp lý về thông tin website, consent, lịch sử giá (§6). **Plugin**: thanh toán/vận chuyển VN (§4), hoá đơn điện tử (§5), sàn TMĐT (§7) — xem [plugin-catalog](../05-plugin/plugin-catalog.md).

## 1. Địa chỉ & địa giới hành chính *(core)*

- Từ **01/07/2025**, Việt Nam áp dụng **chính quyền địa phương 2 cấp**: **34 tỉnh/thành phố** và **cấp xã (phường/xã/đặc khu)**, **bỏ cấp quận/huyện**.
- Thiết kế:
  - Bảng `administrative_units(code, name, full_name, level[province|ward], parent_code, effective_from, effective_to, replaced_by)` — có hiệu lực theo thời gian.
  - Bảng ánh xạ **địa chỉ cũ (3 cấp) → mới (2 cấp)** để: chuyển đổi sổ địa chỉ khách cũ, hiển thị gợi ý "Phường X (trước đây thuộc Quận Y)", và map sang mã của hãng vận chuyển/ERP chưa cập nhật.
  - Địa chỉ lưu: `province_code`, `ward_code`, `street_line`, và **chuỗi snapshot** đầy đủ tại thời điểm đặt hàng (không phụ thuộc bảng tham chiếu sau này).
  - Mỗi hãng VC có mã địa giới riêng → bảng mapping theo connector.
- UX: ô tìm kiếm địa chỉ có gợi ý, gõ không dấu vẫn ra kết quả ("phuong ben thanh" → "Phường Bến Thành").
- **Hiện trạng (slice 6):** checkout nhận `province_code/province_name/ward_code/ward_name/street_line` và lưu snapshot vào đơn; **chưa** đối chiếu với bảng `administrative_units` (chưa có). VAT gồm trong giá theo `VANI_VAT_RATE_BP` (mặc định 10%) — kế toán xác nhận mức hiện hành.
- Nguồn dữ liệu: danh mục đơn vị hành chính chính thức của Tổng cục Thống kê/Bộ Nội vụ; cập nhật bằng lệnh `php artisan vani:geo:import`.

## 2. Số điện thoại, tên, ngôn ngữ

- Chuẩn hoá SĐT về E.164 (`+849xxxxxxxx`), chấp nhận nhập `09…`, `849…`, có/không khoảng trắng; kiểm tra đầu số di động hợp lệ.
- Họ tên: 1 trường `full_name` (người Việt không quen tách họ/tên); nếu cần thì tách phía sau.
- Tiếng Việt có dấu: UTF-8 toàn hệ thống; collation/tìm kiếm **không phân biệt dấu** (collation `utf8mb4_0900_ai_ci` của MySQL cho so sánh không dấu; cột `search_text` đã bỏ dấu + chuyển `đ→d` cho tìm kiếm trong DB; Meilisearch cho storefront).
- Slug URL: bỏ dấu (`áo sơ mi lụa` → `ao-so-mi-lua`), xử lý đúng chữ `đ` → `d`.

## 3. Tiền tệ, số, ngày

| Mục | Quy ước |
|---|---|
| Tiền | VND, **số nguyên**, không phần thập phân. Hiển thị `1.250.000 ₫` hoặc `1.250.000đ` (cấu hình theo brand) |
| Làm tròn | Giảm % → làm tròn đến đồng (hoặc 1.000đ theo cấu hình brand) ở từng dòng; tổng đơn = tổng các dòng |
| Số | Dấu `.` phân cách nghìn, `,` thập phân |
| Ngày giờ | `dd/mm/yyyy`, múi giờ `Asia/Ho_Chi_Minh`; DB lưu UTC |

## 4. Thanh toán & giao nhận *(plugin)*

- **COD** vẫn chiếm tỷ trọng lớn → đầu tư: xác nhận đơn tự động (ZNS/gọi tự động), chấm điểm rủi ro "bom hàng", đối soát COD tự động ([order](../09-order/order.md)).
- **VietQR/chuyển khoản** tăng mạnh → xác nhận tự động theo nội dung chuyển khoản.
- **Hãng vận chuyển** phổ biến: GHN, GHTK, Viettel Post, VNPost, J&T Express, Ninja Van, BEST Express, Ahamove/Grab/Be (giao nhanh nội thành).
- **Cho xem hàng / thử hàng** khi nhận: tuỳ chọn theo brand (ghi chú gửi hãng VC).
- Ngày giao dự kiến theo tuyến nội tỉnh/liên vùng; lưu ý Tết Nguyên đán (hãng ngưng lấy hàng).

## 5. Hoá đơn điện tử *(plugin `vani.einvoice` + plugin nhà cung cấp)*

- Theo **Nghị định 123/2020/NĐ-CP** (sửa đổi bởi **Nghị định 70/2025/NĐ-CP**) và Thông tư hướng dẫn: bán lẻ cho người tiêu dùng cũng phải lập hoá đơn điện tử (có thể từ máy tính tiền hoặc lập theo từng lần bán / tổng hợp theo quy định hiện hành — kế toán quyết định mô hình).
- Tích hợp nhà cung cấp HĐĐT (VNPT Invoice, Viettel S-Invoice, MISA meInvoice, BKAV eHoadon, EasyInvoice, FPT eInvoice…) qua contract `EInvoiceProvider`:
  ```php
  interface EInvoiceProvider
  {
      public function issue(InvoiceDraft $draft): IssuedInvoice;   // số HĐ, mã tra cứu, mã CQT
      public function cancel(IssuedInvoice $invoice, string $reason): void;
      public function adjust(IssuedInvoice $invoice, InvoiceDraft $adjustment): IssuedInvoice;
      public function replace(IssuedInvoice $invoice, InvoiceDraft $replacement): IssuedInvoice;
  }
  ```
- Hoá đơn phát hành theo **pháp nhân** của đơn (đơn con trong kênh tập đoàn).
- Khách yêu cầu hoá đơn công ty: thu MST, tên, địa chỉ; tra cứu MST tự điền (nếu có dịch vụ).
- Thời điểm phát hành: cấu hình (khi giao thành công / khi thanh toán) — phải khớp quy định về thời điểm lập hoá đơn với bán hàng hoá.
- Đổi trả → hoá đơn điều chỉnh/thay thế.
- **Quyết định mở**: VaniShop phát hành trực tiếp hay ERP phát hành (xem checklist [integration-platform](../11-integration/integration-platform.md)).

## 6. Pháp lý thương mại điện tử

| Chủ đề | Văn bản (tham khảo) | Tác động kỹ thuật |
|---|---|---|
| Website TMĐT bán hàng | Nghị định 52/2013/NĐ-CP, sửa đổi bởi NĐ 85/2021/NĐ-CP; **Luật Thương mại điện tử** (theo dõi hiệu lực & văn bản hướng dẫn) | Một domain chung nên có **một website** cần thông báo/đăng ký với Bộ Công Thương (online.gov.vn). ⚠️ Nếu các brand thuộc **nhiều pháp nhân** cùng bán trên website này, website có thể bị xem là **sàn giao dịch TMĐT** (phải **đăng ký**, không chỉ thông báo, kèm quy chế hoạt động). Pháp chế phải chốt mô hình (một pháp nhân vận hành website, hay đăng ký sàn) trước go-live ([ADR-019](../19-adr/ADR-019-shared-domain-brand-path.md)). Footer mỗi storefront hiển thị pháp nhân vận hành website và pháp nhân bán hàng của brand (tên, MST, địa chỉ, hotline) |
| Thông tin bắt buộc | NĐ 52/2013 & 85/2021 | Trang chính sách: đổi trả, vận chuyển, thanh toán, bảo mật, giải quyết khiếu nại; điều khoản sử dụng. Quy trình checkout hiển thị rõ tổng giá, phí, cho phép khách xem lại trước khi xác nhận |
| Bảo vệ dữ liệu cá nhân | **Nghị định 13/2023/NĐ-CP**, **Luật Bảo vệ dữ liệu cá nhân 2025** (hiệu lực 01/01/2026) | Consent rõ ràng từng mục đích, quyền truy cập/xoá/rút consent, đánh giá tác động xử lý DLCN, thông báo vi phạm, hạn chế chuyển dữ liệu ra nước ngoài (chọn vị trí lưu trữ) — xem [security](../15-security/security.md) |
| Bảo vệ người tiêu dùng | Luật Bảo vệ quyền lợi người tiêu dùng 2023 | Ghi âm/lưu vết giao dịch, chính sách đổi trả rõ ràng, không điều khoản bất lợi ẩn |
| Khuyến mãi | Luật Thương mại, **Nghị định 81/2018/NĐ-CP** (và văn bản thay thế nếu có) | Mức giảm tối đa (thường 50%, trừ trường hợp đặc biệt), thời gian KM, thông báo/đăng ký KM với Sở Công Thương; lưu lịch sử giá để chứng minh giá gốc |
| Quảng cáo, tin nhắn | Luật Quảng cáo, Nghị định về chống tin nhắn rác | SMS/Email marketing cần consent, có cách từ chối, giới hạn khung giờ gửi |
| Nhãn hàng hoá | Nghị định 43/2017 & 111/2021 | PDP hiển thị xuất xứ, thành phần/chất liệu, hướng dẫn sử dụng/bảo quản |
| Thuế | Luật Quản lý thuế, quy định với sàn/website TMĐT | Báo cáo doanh thu theo pháp nhân, xuất dữ liệu cho kế toán |

## 7. Sàn TMĐT & mạng xã hội *(plugin, đợt P3)*

- Plugin Shopee / Lazada / TikTok Shop: đẩy sản phẩm & tồn (theo channel allocation), kéo đơn về → vào luồng fulfillment chung.
- Social commerce: đơn từ Facebook/Zalo/livestream do CSKH tạo trong Admin (kênh `social`), dùng chung tồn/khuyến mãi.
- Zalo Mini App cho brand (tuỳ chọn) dùng Storefront API.

## 8. Hành vi người dùng & UX

- > 80% traffic từ mobile: thiết kế mobile-first, ảnh tối ưu, checkout ít bước.
- Nút **chat Zalo/Messenger** nổi; hotline click-to-call.
- **Bảng size** & gợi ý size (chiều cao/cân nặng) — giảm tỷ lệ đổi trả.
- Mùa cao điểm: 9.9, 10.10, 11.11, 12.12, Black Friday, Tết, 8/3, 20/10 → kế hoạch tải & flash sale ([operations](../18-operations/operations.md)).
