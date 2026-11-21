# Kế hoạch đưa storefront VaniShop vào production

Ngày đối chiếu mã nguồn 21/11/2026. Mục tiêu phát hành đầu tiên là khách tìm được đúng biến thể, biết số tiền phải trả, mua trên điện thoại và tra cứu được đơn. Owner có thể cập nhật nội dung và vận hành một đơn từ lúc nhận đến sau giao hàng.

## Phạm vi và cách kế thừa

Một cửa hàng dùng một theme hoạt động. Phát triển trong `custom/theme/vani-base`, giữ namespace view, hook, translation VI/EN, named route theo locale và khả năng override của theme con. Blade render DTO từ Storefront Application trong cùng process. Giá, tồn, voucher, cước, điều kiện đổi trả và quyền xem đơn thuộc use-case dùng chung với API. Khi thêm hành vi nghiệp vụ, đối chiếu ADR-044, OpenAPI và test parity. Riêng dữ liệu phiên web, mã hiện tại vẫn dùng endpoint riêng, được kiểm tra trong `LocalizedRoutesTest`.

| Khu vực | Tài sản đã có | Việc cần làm trước phát hành |
| --- | --- | --- |
| Trang chủ và điều hướng | Layout, theme tokens, menu desktop/mobile, footer, page builder và bốn block Hero, Product Grid, Brand Grid, Rich Text | Dựng nội dung thương hiệu bằng cấu hình Admin. Kiểm tra cả trang chủ có block và fallback khi chưa cấu hình. Kiểm tra banner, menu sâu, logo dài/ngang/dọc, link trống và nội dung song ngữ. |
| Khám phá sản phẩm | Trang danh mục, thương hiệu, tìm kiếm, partial listing và product-card | Chạy hành trình tìm → lọc → sắp xếp → phân trang → quay lại. Nghiệm thu truy vấn rỗng, không có kết quả, ảnh thiếu, giá thường/khuyến mãi và liên kết theo locale. |
| Chi tiết sản phẩm | Trang PDP, ảnh theo biến thể, bộ chọn, giá, mua ngay, slot plugin | Kiểm tra ma trận size/màu thật trên mobile, SKU hết hàng và tồn thay đổi sau khi mở trang. Khớp biến thể, giá và số lượng từ PDP đến dòng giỏ. Bật SizeChart nếu cửa hàng có dữ liệu. |
| Giỏ và checkout | Mini-cart, giỏ trang riêng, chọn dòng, voucher, báo giá, địa chỉ, đặt hàng | Nghiệm thu với khách vãng lai và thành viên, địa chỉ đã lưu và nhập mới. Thử voucher hết hiệu lực, tổng thay đổi, hết tồn, gửi lại sau mất mạng. Đúng tổng đã xác nhận và mỗi lần mua thành công sinh đúng một đơn. |
| Sau mua | Trang đơn, tra cứu, lịch sử, huỷ, đổi/trả | Dùng hai tài khoản và một phiên khách độc lập để kiểm tra phạm vi truy cập. Diễn tập tạo đơn → xác nhận → giao → đổi/trả, gồm trường hợp yêu cầu chưa đủ điều kiện. |
| Nội dung mở rộng | CMS, Wishlist, Lookbook, StorefrontExtras, TrackingPixels và các plugin bán hàng đã có trong repo | Chọn plugin theo nhu cầu và dữ liệu có thật. Xác nhận trạng thái cài/bật ở staging. Kiểm tra layout, checkout và trang tài khoản khi plugin tuỳ chọn tắt. |

## Các đợt phát triển sản phẩm

Các đợt dưới đây có thể phát hành độc lập. Ưu tiên đề xuất dựa trên hành trình mua thời trang. Dùng số liệu thực tế về lượt tìm không ra hàng, lượt thêm giỏ và lý do liên hệ CSKH để điều chỉnh thứ tự.

| Đợt | Người dùng sẽ làm được gì | Phần kế thừa và phần cần xây | Nghiệm thu |
| --- | --- | --- | --- |
| 1 · Khám phá catalog | Khách duyệt theo danh mục hoặc thương hiệu, kết hợp nhiều bộ lọc và quay lại tập kết quả ban đầu bằng một thao tác. | Đã bổ sung bỏ tất cả bộ lọc trên listing và gửi form sắp xếp khi tắt JS. Bước kế là hiện từng lựa chọn đang áp dụng cạnh số kết quả, cho phép bỏ riêng từng mục. Lấy nhãn dịch và URL từ ListingComposer. | Giữ từ khoá, thứ tự, locale và phạm vi danh mục/bộ sưu tập. Bỏ phân trang khi đổi bộ lọc. Bao phủ cả tổ hợp trả về 0 kết quả và nút Back trên mobile. |
| 2 · Trang chủ theo mùa | Người vận hành ghép được trang chủ mùa mới từ danh mục, bộ sưu tập và nội dung biên tập. | Dùng Hero, Product Grid, Lookbook và các khối của StorefrontExtras. Đề xuất thêm block lưới danh mục có ảnh, dùng thẻ category-card hiện có và catalog theo locale. Đăng ký qua StorefrontBlock trong StorefrontExtras. | Admin chọn và sắp xếp danh mục. Trang chủ SSR và dữ liệu home API có cùng thứ tự. Danh mục ẩn hoặc đã xoá tự biến mất, ảnh thiếu có fallback, cấu hình cũ vẫn render. |
| 3 · Mua theo bộ phối | Từ một ảnh lookbook, khách mở các món trong bộ, chọn đúng màu/size từng món và thêm vào giỏ. | Lookbook hiện có thư viện ảnh và liên kết danh mục. Triển khai phần gắn mẫu lên ảnh theo hướng OutfitBundle trong đặc tả Lookbook. Bước đầu chỉ dẫn sang PDP. Sau khi có dữ liệu sử dụng mới triển khai mua nhiều món và phần chọn biến thể. | Điều hướng được cả bằng bàn phím và danh sách chữ dưới ảnh. Sản phẩm ngừng bán tự ẩn. Bước mua nhiều món phải báo rõ dòng nào hết hàng, tổng theo báo giá hiện tại và xử lý được khi gửi lại. |
| 4 · Hỗ trợ quyết định mua | Khách tìm được bảng size đúng mẫu, lưu sản phẩm và chọn nhận tin khi hàng về. | Kích hoạt và cấu hình SizeChart, Wishlist, các slot PDP theo nhu cầu. Wishlist đã hỗ trợ thông báo theo mẫu. Nếu cần theo đúng màu/size, mở rộng subscription trong plugin tới variant, dùng sự kiện tồn và kênh Notification hiện có. | Kiểm tra mẫu có nhiều hệ size, hai biến thể về hàng khác thời điểm, khách rút consent và sự kiện gửi lặp. Có trạng thái rõ khi tắt plugin hoặc chưa có bảng size. |
| 5 · Mua lại từ lịch sử đơn | Khách đã đăng nhập mở đơn cũ, chọn món muốn mua lại và xem giá hiện tại trước khi thêm giỏ. | Đề xuất hành vi mới qua Storefront Application hoặc plugin tài khoản, dùng quyền đọc đơn, dữ liệu catalog và lệnh giỏ hiện hành. Dùng snapshot cũ để nhận diện món. Định nghĩa API cùng lúc với giao diện. | Không truy cập được đơn của khách khác. Món đã gỡ hoặc hết hàng có lý do riêng. Xác nhận số lượng/giá mới, thêm đúng dòng đã chọn và không tạo đơn thanh toán tự động. |
| 6 · Tự phục vụ sau mua | Khách nhìn thấy bước xử lý hiện tại và hành động tiếp theo trên một trang đơn, cả khi mua vãng lai. | Dùng order receipt, dữ liệu vận chuyển và order-returns sẵn có. Rà dữ liệu mốc thời gian trong DTO rồi bổ sung trình bày tiến độ giao, liên kết vận đơn và trạng thái đổi trả cho từng kiện khi có dữ liệu. | Đối chiếu một đơn giao nhiều kiện, huỷ một phần, đổi size và hoàn tiền. Hiển thị đúng lịch sử, giờ địa phương và quyền truy cập của đơn token/tài khoản. |

### Tái cấu trúc theo đường đi của dữ liệu

| Phạm vi | Thay đổi và thứ tự chuyển | Cách bảo toàn tương thích |
| --- | --- | --- |
| Thành phần Blade | Đã tách lưới logo dùng chung cho trang chủ mặc định và block brand_grid. Tiếp theo kiểm kê những phần PDP, checkout dùng cùng dữ liệu ở nhiều vị trí, ưu tiên phần tổng tiền và lỗi biểu mẫu. | Giữ namespace theme, tên view điểm vào, vị trí hook và markup mà plugin sử dụng. Kiểm cả theme mặc định lẫn một override ở theme con. |
| JS storefront | Tách theo từng luồng đang tập trung trong js/app.js. Bắt đầu với listing và history, tiếp theo là session/header, giỏ và PDP. Giữ app.js làm entry Vite trong quá trình chuyển. | Giữ API window.vani, sự kiện, selector và thứ tự khởi tạo của plugin. Test back/forward, double submit, mạng lỗi, thay HTML từng phần và ngân sách JS sau mỗi đợt. |
| Shell và API | Đối chiếu endpoint phiên hiện tại với ADR-044. Kiểm kê theme, plugin, load script và test locale đang gọi endpoint trước khi quyết định hợp đồng thay thế. Di chuyển từng caller và test cache/CDN cùng lượt phát hành. | So sánh khách/thành viên, CSRF, flash và giá riêng theo hai chế độ CDN. Cần quy trình chuyển phiên bản cho theme con đã triển khai. |
| Nội dung chiến dịch | Sau thử nghiệm trang chủ theo mùa, nếu Owner cần URL landing độc lập, mở rộng CMS để tái dùng trình phân giải block hiện có. Bắt đầu với một landing có URL VI/EN, preview và publish. | Bản nháp chỉ cho người có quyền xem, URL public chỉ trả nội dung đã publish. Kiểm canonical, sitemap, cache và khả năng quay lại bản đã xuất bản trước. |

### Phụ thuộc và cách quyết định phạm vi release

- **Release mở bán** gồm luồng catalog, PDP, giỏ, checkout, đơn và các điều kiện P0 bên dưới. Đợt 1 đi cùng release này. Đợt 2 có thể đi cùng nếu Owner bàn giao ảnh và thứ tự danh mục trước nghiệm thu.
- **Release merchandising** gồm đợt 2–4 theo nhu cầu. Owner chuẩn bị ảnh có quyền sử dụng, mapping ảnh → mẫu và bảng size. Làm luồng từ ảnh đến PDP trước khi thêm hành vi ghi nhiều dòng giỏ. Đo lượt mở PDP và thêm giỏ từ lookbook theo cấu hình consent.
- **Release sau mua** gồm đợt 5–6. Ưu tiên hiển thị tiến độ trước nếu phần lớn yêu cầu CSKH là hỏi vị trí đơn. Chọn mua lại trước nếu dữ liệu cho thấy khách thường mua lặp cùng mẫu. QA chuẩn bị đơn có nhiều kiện và SKU đã ngừng bán.
- Mỗi lần tái cấu trúc chỉ chuyển một luồng có test bảo vệ. Phát hành độc lập với thay đổi quy tắc giá, tồn và thanh toán. Các tính năng plugin mới được thử trên staging với plugin bật và tắt trước khi mở cho khách.

## Trình tự và tiêu chí bàn giao

1. **P0 · Chốt cấu hình mở bán — Owner và vận hành.** Chốt locale, tiền tệ, catalog mẫu, vùng giao, phí giao, phương thức thanh toán, liên hệ và chính sách. Bàn giao danh sách SKU có ảnh, giá, tồn, cùng nội dung đã duyệt và ma trận plugin bật/tắt. Cho phép bắt đầu kiểm thử theme ngay bằng fixture của repo.
2. **P0 · Hoàn thiện theme — frontend.** Dùng lại layout, partial, block và token nêu trên. Đợt đầu bổ sung liên kết bỏ qua menu và dành kích thước cố định cho logo. Duyệt từng mẫu ở 320/375 px và desktop bằng dữ liệu bước 1. Kiểm tra bàn phím, focus khi đóng drawer/modal, thông báo lỗi form, ảnh đầu màn hình, trạng thái hết hàng và trang đóng bảo trì. Ghi lỗi theo template và bộ dữ liệu tái hiện.
3. **P0 · Chứng minh luồng thu tiền — backend và QA.** Sau khi có cấu hình giao hàng/thanh toán, chạy test native, API contract, CheckoutParity, CartSelection, FirstPartySession và browser checkout. Chạy concurrency trên MySQL cho hai khách tranh SKU cuối, một giỏ gửi lặp và callback trùng. Với từng cổng online bật, dùng sandbox của nhà cung cấp để kiểm tra thành công, huỷ, callback đến chậm và đối soát. Lưu mã đơn thử và đối chiếu ledger, tồn và số tiền.
4. **P0 · Nội dung, SEO và quyền riêng tư — Owner và QA.** Duyệt title/canonical/hreflang, sitemap, robots, JSON-LD sản phẩm/breadcrumb, sản phẩm đã gỡ và trang riêng tư. Duyệt trang liên hệ, giao hàng, thanh toán và đổi trả qua CMS/menu. Nếu bật CDN, kiểm tra hai phiên độc lập để xác nhận HTML công khai không lộ giỏ, giá thành viên, token hay thông tin cá nhân.
5. **P0 · Diễn tập trên staging — vận hành và QA.** Build bằng lockfile của release. Chạy browser mobile với ngân sách hiện có JS ≤ 64 KB, CSS ≤ 96 KB chưa nén. Chạy k6 với dữ liệu đủ tồn, đối chiếu số đơn thành công thực tế với mục tiêu 500 đơn/phút, p95 HTML < 300 ms, API < 200 ms, checkout < 1 s, lỗi < 1%, không vượt tồn. Khi bật CDN, đo riêng tải đọc ở edge theo NFR 5.000 request/s. Lưu cấu hình tải và kết quả cùng mã release.
6. **P0 · Quyết định mở bán — người phụ trách release.** Áp dụng runbook production, kiểm tra plugin cache, plugin doctor, security check, health, Redis, Horizon, scheduler, email cảnh báo, backup và phục hồi trên môi trường tách biệt. Mục tiêu RPO ≤ 5 phút, RTO ≤ 2 giờ. Owner xác nhận nội dung và các điều kiện kinh doanh trong go-live gate. Chỉ mở bán sau khi hoàn tất các bước P0 và có người nhận cảnh báo.
7. **Sau phát hành — vận hành.** Theo dõi lỗi đặt đơn, giao dịch chờ, queue trễ, tỷ lệ lỗi và tốc độ. Nếu sai tổng tiền, đặt trùng hoặc mất phương thức giao/thanh toán, dừng nhận đơn theo runbook, bảo toàn giao dịch đang xử lý, quay về release tốt và đối soát các đơn trong khoảng sự cố. Chạy lại smoke test trước khi mở bán lại.

## Đề xuất bổ sung có điều kiện

- **Fixture có logo thật cho browser budget.** Ca hiện tại tạo ảnh sản phẩm. Bổ sung thương hiệu có logo và homepage có block thương hiệu để test phát hiện mất kích thước ảnh ở cả hai chế độ trang chủ.
- **Bằng chứng nghiệm thu theo release.** Dùng kết quả CI và phiếu release hiện có để gom ảnh mobile/desktop, đơn sandbox, báo cáo k6, health và kết quả restore. Gắn người xác nhận và trạng thái cho từng điều kiện.
- **P1 sau mở bán.** Chỉ triển khai purge CDN theo event nếu bật cache HTML và thời gian chờ TTL vượt yêu cầu cập nhật catalog. Chỉ mở rộng page builder sang landing page khi CMS và Lookbook hiện có chưa đáp ứng chiến dịch cụ thể.

Nguồn đối chiếu: [Storefront](storefront.md), [ADR-044](../19-adr/ADR-044-storefront-api-first.md), [NFR](../02-architecture/overview.md), [kiểm thử](../17-testing/testing.md), [runbook production](../18-operations/production.md), [go-live gate](../20-roadmap/roadmap.md).
