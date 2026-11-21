# Rà soát storefront Flat Ecommerce Luxury

## Hiện trạng đã xác minh

| Phạm vi | Quan sát từ mã nguồn | Ưu tiên triển khai |
| --- | --- | --- |
| Cấu hình | CSS đã có token trung tính, font Anivele cục bộ và focus-visible. Manifest lại đặt màu chính xanh đậm, accent nâu và bo góc 8px. Nhiều partial dùng thang slate. | Đồng bộ mặc định và giữ khả năng ghi đè của cửa hàng. |
| Header và search | Layout có header sticky, slot plugin, form GET dự phòng, modal và menu mobile. header.js quản lý gợi ý, hủy yêu cầu cũ, phím điều hướng và trả focus. | Thu gọn khoảng cách header, tăng phân cấp kết quả và giữ các trigger. |
| PLP | ListingComposer cung cấp URL bộ lọc, sort và phân trang. listing.js quản lý request đang chạy và popstate. Các grid có hoặc không có sidebar dùng chung product-card, gap 24px cả trên mobile. | Giảm gap ngang di động, giữ cấu hình lưới theo ngữ cảnh, tỷ lệ từ ProductFrameComposer và kích thước ảnh responsive. |
| PDP | ProductPage dựng gallery và ánh xạ trục sang SKU. app.js tăng cường gallery, trạng thái lựa chọn và giá. Hai cột 50/50 và hai CTA cạnh nhau làm yếu thứ tự mua hàng. | Mở rộng gallery, CTA chính toàn chiều ngang, cải thiện mục thông tin có dữ liệu. |
| Giỏ và checkout | cart.js bảo vệ submit, đồng bộ số lượng và thay HTML mini-cart từ server. session.js xử lý phiên và dữ liệu riêng của khách. Checkout gửi quote/voucher/order qua form có khóa idempotency. | Mở rộng drawer desktop, tăng vùng chạm, dành thêm chỗ cho form và tổng tiền. |
| Trang chủ và plugin | Home render các block theo cấu hình hoặc danh mục, thương hiệu và hàng mới. StorefrontExtras cung cấp slideshow, category grid, banner và sản phẩm. Lookbook cung cấp marker và link sản phẩm đã lọc trạng thái xuất bản. | Dùng ảnh và nội dung đã có, ưu tiên bộ sưu tập, áp dụng giao diện ngay trong theme. |
| SEO, khả năng truy cập và tốc độ | Layout SSR xuất canonical/locale; PDP xuất JSON-LD. Có skip link, dialog, chế độ reduced-motion, kích thước ảnh, lazy import gallery. Test browser có ngân sách 64 KiB JS và 96 KiB CSS giải nén. | Kiểm tra lại focus, overflow, ảnh màn hình đầu và ngân sách sau build. |

## Ranh giới

Catalog, Pricing, Cart và Checkout sở hữu SKU, giá, tồn kho, quote và đơn hàng. Theme chỉ trình bày DTO và HTML. Giữ route, API, hook, thứ tự khởi tạo JS và data attribute cho plugin. Không chọn hộ SKU hoặc suy diễn chính sách giao hàng. Các thay đổi trình bày phải hoạt động khi cửa hàng không cấu hình block, ảnh hoặc plugin.

## Rủi ro cần đo

Nội dung plugin thay đổi theo cấu hình. Trang chủ có slideshow cần kiểm tra ảnh thực trên cả điện thoại và desktop; figcaption phủ ảnh có thể khó đọc. Mẫu sản phẩm thiếu ảnh cần giữ khung ổn định. Số đo vitals trong Chromium cục bộ không thay thế phân vị 75 từ khách thực.

## Quyết định thiết kế

Bộ sưu tập theo mùa dẫn tới nhóm sản phẩm ở Saint Laurent; ảnh phối đồ trên trang nữ của COS dẫn tới danh mục hoặc bộ sưu tập. VaniShop áp dụng hai nguyên tắc này bằng ảnh và liên kết mà cửa hàng đã nhập trong page builder. Trên desktop, ảnh chiếm ba phần năm khối slideshow; trên điện thoại, tiêu đề và link nằm ngay dưới ảnh. [Saint Laurent](https://www.ysl.com/en-us) · [COS Womenswear](https://www.cos.com/en-us/women)

## Bàn giao theo giai đoạn

Các đường dẫn theme dưới đây tính từ `custom/theme/vani-base`. Các kiểm thử liên luồng được chạy chung trên bản build tích hợp.

| Phase | Trạng thái và tệp chính | Thực hiện và quyết định kiến trúc | Kiểm tra | Giới hạn và bước tiếp |
| --- | --- | --- | --- | --- |
| 0 · Audit | Đã ghi trong tài liệu này | Đối chiếu manifest, template, DTO, module JS, block plugin và bộ test. Chụp trang chủ ban đầu. | Mở trang chủ có dữ liệu trên Herd. | Cần duyệt lại khi thay cấu hình cửa hàng. |
| 1 · Foundation | Đã triển khai trong theme.json và css/app.css | Token mặc định, thang màu trung tính, nút mua chính, input và lỗi form. Giữ font cục bộ và token cấu hình. | Build Tailwind/Vite và kiểm tra kích thước bundle qua browser. | Màu ghi đè của merchant cần kiểm tra contrast riêng. |
| 2 · Header và PLP | Đã triển khai ở layouts/app, search-modal-content, listing, product-grid, product-card, price và js/listing.js | Thu gọn header, giảm gap lưới di động xuống 10px. Giữ form lọc GET, cập nhật disclosure khi đổi breakpoint và thay HTML. | Test browser header và listing; xem ảnh PLP ở 390 và 1440px; thử tìm kiếm và mở bộ lọc khi tắt JS. | Giữ danh sách và số cột riêng của các block. |
| 3 · PDP | Đã triển khai ở pages/product và lang/{vi,en}/product.php | Hai cột 4:3, sizes theo khung ảnh, CTA toàn hàng, mục mô tả có thể thu gọn. Giữ selector SKU và gallery. | Chọn size trên trang có ảnh, thêm giỏ; browser test mua ngay, đổi số lượng và cập nhật giá thành viên. | Mục mô tả chỉ xuất hiện khi sản phẩm có nội dung. |
| 4 · Giỏ và checkout | Đã triển khai ở pages/cart, pages/checkout, drawer, mini-cart và cart-page | Drawer 464px trên desktop, nút số lượng lớn hơn, tóm tắt đơn riêng và một CTA đặt hàng hiển thị trên từng breakpoint. | Cart/CheckoutMobile browser, thao tác đổi lượng, quote và COD trên fixture. Đo drawer và checkout với giỏ có SKU ở cả bảy chiều rộng. | Cổng thanh toán thật cần nghiệm thu sandbox theo runbook. |
| 5 · Home và Lookbook | Đã triển khai ở pages/home, blocks/{hero,product_grid,brand_grid} và css/app.css | Tách caption khỏi ảnh, đưa hàng mới lên đầu trang mặc định, kết nối kiểu marker và danh sách Lookbook qua selector plugin. | Trang chủ mặc định và cấu hình bằng block, LookbookProducts, xem ảnh thật trên Herd. | Chất lượng ảnh và mapping sản phẩm phụ thuộc nội dung đã xuất bản. |
| 6 · Nghiệm thu local | Đã mở rộng tests/Browser/StorefrontMobileBudgetTest.php | Kiểm tra overflow và trạng thái bộ lọc sau resize ở 320/375/390/768/1024/1280/1440px. | Kiểm thử Pest, browser Chromium, PHPStan, Pint, build và đo ở bảng dưới. | Kiểm tra Safari/iOS thật, screen reader và chỉ số thực địa trước khi xác nhận production. |

## Số đo cục bộ

Chromium trên Herd, viewport 390 × 844, bỏ tài nguyên `/herd-studio/` của công cụ phát triển, chặn cache trong phép đo transfer. Transfer gồm header HTTP. Một lần tải cho mỗi trang; các con số này dùng để tái kiểm, chưa đại diện cho traffic thực tế.

| Trang | LCP | CLS trong khoảng đo | JS transfer | CSS transfer |
| --- | ---: | ---: | ---: | ---: |
| Trang chủ có slideshow | 284 ms | 0,089 | 15.041 B | 15.660 B |
| PLP | 140 ms | 0,089 | 15.041 B | 15.660 B |
| PDP có thư viện ảnh | 92 ms | 0 | 65.669 B | 20.637 B |
| Giỏ trống | 72 ms | 0,089 | 15.041 B | 15.660 B |

Lần đo bổ sung trên trang chủ mô phỏng CPU chậm 4×, mạng tải xuống 200.000 B/s và độ trễ 80 ms cho LCP 1.040 ms, CLS 0, sáu tài nguyên ảnh và 40 ms từ chạm mở tìm kiếm đến lần vẽ tiếp theo. Đo INP p75 cần nhiều lượt truy cập thực tế. Các test ngân sách riêng dùng kích thước giải nén trên fixture.

Bản build nghiệm thu đạt 1.805 test Pest, bỏ qua 3; 26 test browser storefront đạt với 291 assertion. PHPStan báo 0 lỗi, Pint và Vite build thành công.
