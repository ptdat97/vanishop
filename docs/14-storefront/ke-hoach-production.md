# Kế hoạch đưa storefront VaniShop vào production

Ngày đối chiếu mã nguồn 22/11/2026. Mục tiêu phát hành đầu tiên là khách tìm được đúng biến thể, biết số tiền phải trả, mua trên điện thoại và tra cứu được đơn. Owner có thể cập nhật nội dung và vận hành một đơn từ lúc nhận đến sau giao hàng.

## Phạm vi và cách kế thừa

Một cửa hàng dùng một theme hoạt động. Phát triển trong `custom/theme/vani-base`, giữ namespace view, hook, translation VI/EN, named route theo locale và khả năng override của theme con. Blade render DTO từ Storefront Application trong cùng process. Giá, tồn, voucher, cước, điều kiện đổi trả và quyền xem đơn thuộc use-case dùng chung với API. Khi thêm hành vi nghiệp vụ, đối chiếu ADR-044, OpenAPI và test parity. Riêng dữ liệu phiên web, mã hiện tại vẫn dùng endpoint riêng, được kiểm tra trong `LocalizedRoutesTest`.

| Khu vực | Tài sản đã có | Việc cần làm trước phát hành |
| --- | --- | --- |
| Trang chủ và điều hướng | Layout, theme tokens, menu desktop/mobile, footer, page builder và bốn block Core. StorefrontExtras có slideshow dùng Swiper khi bật plugin | Dựng nội dung thương hiệu bằng cấu hình Admin. Kiểm tra cả trang chủ có block và fallback khi chưa cấu hình. Kiểm tra banner, menu sâu, logo dài/ngang/dọc, link trống và nội dung song ngữ. |
| Khám phá sản phẩm | Trang danh mục, thương hiệu, tìm kiếm, partial listing và product-card. Bộ lọc dùng cùng drawer trên desktop và mobile | Chạy hành trình tìm → lọc → sắp xếp → phân trang → quay lại. Nghiệm thu truy vấn rỗng, không có kết quả, ảnh thiếu, giá thường/khuyến mãi và liên kết theo locale. |
| Chi tiết sản phẩm | Trang PDP, ảnh theo biến thể, bộ chọn, giá, mua ngay, slot plugin | Kiểm tra ma trận size/màu thật trên mobile, SKU hết hàng và tồn thay đổi sau khi mở trang. Khớp biến thể, giá và số lượng từ PDP đến dòng giỏ. Bật SizeChart nếu cửa hàng có dữ liệu. |
| Giỏ và checkout | Mini-cart, giỏ trang riêng, chọn dòng, voucher, báo giá, địa chỉ, đặt hàng | Nghiệm thu với khách vãng lai và thành viên, địa chỉ đã lưu và nhập mới. Thử voucher hết hiệu lực, tổng thay đổi, hết tồn, gửi lại sau mất mạng. Đúng tổng đã xác nhận và mỗi lần mua thành công sinh đúng một đơn. |
| Sau mua | Trang đơn, tra cứu, lịch sử, huỷ, đổi/trả | Dùng hai tài khoản và một phiên khách độc lập để kiểm tra phạm vi truy cập. Diễn tập tạo đơn → xác nhận → giao → đổi/trả, gồm trường hợp yêu cầu chưa đủ điều kiện. |
| Nội dung mở rộng | CMS, Wishlist, Lookbook, StorefrontExtras, TrackingPixels và các plugin bán hàng đã có trong repo | Chọn plugin theo nhu cầu và dữ liệu có thật. Xác nhận trạng thái cài/bật ở staging. Kiểm tra layout, checkout và trang tài khoản khi plugin tuỳ chọn tắt. |

## Các đợt phát triển sản phẩm

Các đợt dưới đây có thể phát hành độc lập. Ưu tiên đề xuất dựa trên hành trình mua thời trang. Dùng số liệu thực tế về lượt tìm không ra hàng, lượt thêm giỏ và lý do liên hệ CSKH để điều chỉnh thứ tự.

| Đợt | Người dùng sẽ làm được gì | Phần kế thừa và phần cần xây | Nghiệm thu |
| --- | --- | --- | --- |
| 1 · Khám phá catalog | Khách duyệt theo danh mục hoặc thương hiệu, kết hợp nhiều bộ lọc và quay lại tập kết quả ban đầu bằng một thao tác. | Listing hiển thị các bộ lọc đang áp dụng cạnh số kết quả, cho phép bỏ từng lựa chọn hoặc bỏ tất cả. ListingComposer dựng nhãn dịch và URL. Form sắp xếp gửi được khi tắt JS. Điều hướng qua JS chỉ áp dụng yêu cầu mới nhất. | Giữ từ khoá, thứ tự, locale và phạm vi danh mục/bộ sưu tập. Bỏ phân trang khi đổi bộ lọc. Bao phủ cả tổ hợp trả về 0 kết quả và nút Back trên mobile. |
| 2 · Trang chủ theo mùa | Người vận hành ghép được trang chủ mùa mới từ danh mục, bộ sưu tập và nội dung biên tập. | Dùng Hero, Product Grid, Lookbook và các khối của StorefrontExtras. StorefrontExtras có lưới danh mục sáu vị trí, slideshow và block Bộ sưu tập nổi bật với ảnh, liên kết theo locale và hai thẻ sản phẩm. | Admin chọn và sắp xếp danh mục. Trang chủ SSR và dữ liệu home API có cùng thứ tự. Danh mục ẩn hoặc đã xoá tự biến mất, ảnh thiếu có fallback, cấu hình cũ vẫn render. |
| 3 · Mua theo bộ phối | Từ một ảnh lookbook, khách mở các món trong bộ, chọn đúng màu/size từng món và thêm vào giỏ. | Lookbook 1.2.0 cho phép Admin gắn tối đa sáu sản phẩm vào mỗi ảnh. Khách mở PDP từ nút đánh số trên ảnh hoặc danh sách tên sản phẩm bên dưới. API trả cùng thứ tự và liên kết. Dùng dữ liệu sử dụng để quyết định bước mua nhiều món và chọn biến thể. | Điều hướng được cả bằng bàn phím và danh sách chữ dưới ảnh. Sản phẩm ngừng bán tự ẩn. Bước mua nhiều món phải báo rõ dòng nào hết hàng, tổng theo báo giá hiện tại và xử lý được khi gửi lại. |
| 4 · Hỗ trợ quyết định mua | Khách tìm được bảng size đúng mẫu, lưu sản phẩm và chọn nhận tin khi hàng về. | Kích hoạt và cấu hình SizeChart, Wishlist, các slot PDP theo nhu cầu. Wishlist có danh sách vãng lai và thông báo theo mẫu. Giá trong thông báo giảm giá lấy theo nhóm khách. Nếu cần theo đúng màu/size, mở rộng subscription trong plugin tới variant, dùng sự kiện tồn và kênh Notification hiện có. | Kiểm tra mẫu có nhiều hệ size, hai biến thể về hàng khác thời điểm, khách rút consent và sự kiện gửi lặp. Có trạng thái rõ khi tắt plugin hoặc chưa có bảng size. |
| 5 · Mua lại từ lịch sử đơn | Khách đã đăng nhập mở đơn cũ, chọn món muốn mua lại và xem giá hiện tại trước khi thêm giỏ. | Đề xuất hành vi mới qua Storefront Application hoặc plugin tài khoản, dùng quyền đọc đơn, dữ liệu catalog và lệnh giỏ hiện hành. Dùng snapshot cũ để nhận diện món. Định nghĩa API cùng lúc với giao diện. | Không truy cập được đơn của khách khác. Món đã gỡ hoặc hết hàng có lý do riêng. Xác nhận số lượng/giá mới, thêm đúng dòng đã chọn và không tạo đơn thanh toán tự động. |
| 6 · Tự phục vụ sau mua | Khách nhìn thấy bước xử lý hiện tại và hành động tiếp theo trên một trang đơn, cả khi mua vãng lai. | Dùng order receipt, dữ liệu vận chuyển và order-returns sẵn có. Rà dữ liệu mốc thời gian trong DTO rồi bổ sung trình bày tiến độ giao, liên kết vận đơn và trạng thái đổi trả cho từng kiện khi có dữ liệu. | Đối chiếu một đơn giao nhiều kiện, huỷ một phần, đổi size và hoàn tiền. Hiển thị đúng lịch sử, giờ địa phương và quyền truy cập của đơn token/tài khoản. |

### Nghiệm thu trang bộ sưu tập

- Mở trang bộ sưu tập có sẵn để kiểm tra banner mặc định. Lưu ảnh Hero, thêm khối trước và sau lưới rồi kiểm tra thứ tự trên desktop và mobile.
- Mở trang từ menu và khối Bộ sưu tập nổi bật. Kiểm tra đổi ngôn ngữ, lọc, sắp xếp và phân trang giữ trong bộ sưu tập. Bộ sưu tập ẩn trả 404.

### Tái cấu trúc theo đường đi của dữ liệu

| Phạm vi | Thay đổi và thứ tự chuyển | Cách bảo toàn tương thích |
| --- | --- | --- |
| Thành phần Blade | Theme dùng lại lưới logo cho trang chủ và block brand_grid, trạng thái trống cho listing và giỏ. Partial tóm tắt checkout cho phép override riêng phần voucher, dòng quote và tổng tiền. | Giữ namespace theme, tên view điểm vào, vị trí hook và markup mà plugin sử dụng. Kiểm cả theme mặc định lẫn một override ở theme con. |
| JS storefront | Listing và history ở `js/listing.js`. Phiên, CSRF và đăng nhập ở `js/session.js`. Điều hướng, modal và gợi ý tìm kiếm ở `js/header.js`. Giỏ mini, trang giỏ và thao tác thêm hàng ở `js/cart.js`. Gallery và biến thể ở `js/product.js`. Báo giá và đặt hàng ở `js/checkout.js`. `app.js` tiếp tục là entry Vite. | Giữ API window.vani, sự kiện, selector và thứ tự khởi tạo của plugin. Test back/forward, double submit, mạng lỗi, thay HTML từng phần và ngân sách JS sau mỗi đợt. |
| Shell và API | Giữ `/_vani/phien` cho dữ liệu phiên web của trang cache. Giá riêng, lượt xem và gợi ý đi qua Storefront API. Kiểm tra caller và hợp đồng theme/plugin trước khi thay dữ liệu. | So sánh khách/thành viên, CSRF, flash và giá riêng theo hai chế độ CDN. Cần quy trình chuyển phiên bản cho theme con đã triển khai. |
| Nội dung chiến dịch | Sau thử nghiệm trang chủ theo mùa, nếu Owner cần URL landing độc lập, mở rộng CMS để tái dùng trình phân giải block hiện có. Bắt đầu với một landing có URL VI/EN, preview và publish. | Bản nháp chỉ cho người có quyền xem, URL public chỉ trả nội dung đã publish. Kiểm canonical, sitemap, cache và khả năng quay lại bản đã xuất bản trước. |

### Phụ thuộc và cách quyết định phạm vi release

- **Release mở bán** gồm luồng catalog, PDP, giỏ, checkout, đơn và các điều kiện P0 bên dưới. Đợt 1 đi cùng release này. Đợt 2 có thể đi cùng nếu Owner bàn giao ảnh và thứ tự danh mục trước nghiệm thu.
- **Release merchandising** gồm đợt 2–4 theo nhu cầu. Owner chuẩn bị ảnh có quyền sử dụng, mapping ảnh → mẫu và bảng size. Làm luồng từ ảnh đến PDP trước khi thêm hành vi ghi nhiều dòng giỏ. Đo lượt mở PDP và thêm giỏ từ lookbook theo cấu hình consent.
- **Release sau mua** gồm đợt 5–6. Ưu tiên hiển thị tiến độ trước nếu phần lớn yêu cầu CSKH là hỏi vị trí đơn. Chọn mua lại trước nếu dữ liệu cho thấy khách thường mua lặp cùng mẫu. QA chuẩn bị đơn có nhiều kiện và SKU đã ngừng bán.
- Mỗi lần tái cấu trúc chỉ chuyển một luồng có test bảo vệ. Phát hành độc lập với thay đổi quy tắc giá, tồn và thanh toán. Các tính năng plugin mới được thử trên staging với plugin bật và tắt trước khi mở cho khách.

## Trình tự và tiêu chí bàn giao

1. **P0 · Chốt cấu hình mở bán — Owner và vận hành.** Chốt locale, tiền tệ, catalog mẫu, vùng giao, phí giao, phương thức thanh toán, liên hệ và chính sách. Bàn giao danh sách SKU có ảnh, giá, tồn, cùng nội dung đã duyệt và ma trận plugin bật/tắt. Cho phép bắt đầu kiểm thử theme ngay bằng fixture của repo.
2. **P0 · Hoàn thiện theme — frontend.** Dùng lại layout, partial, block và token nêu trên. Đợt đầu bổ sung liên kết bỏ qua menu và dành kích thước cố định cho logo. Duyệt ở 320/375/390/768/1024/1280/1440 px bằng dữ liệu bước 1. Kiểm tra bàn phím, focus khi đóng drawer/modal, thông báo lỗi form, ảnh đầu màn hình, trạng thái hết hàng và trang đóng bảo trì. Ghi lỗi theo template và bộ dữ liệu tái hiện.
3. **P0 · Chứng minh luồng thu tiền — backend và QA.** Sau khi có cấu hình giao hàng/thanh toán, chạy test native, API contract, CheckoutParity, CartSelection, FirstPartySession và browser checkout. Chạy concurrency trên MySQL cho hai khách tranh SKU cuối, một giỏ gửi lặp và callback trùng. Với từng cổng online bật, dùng sandbox của nhà cung cấp để kiểm tra thành công, huỷ, callback đến chậm và đối soát. Lưu mã đơn thử và đối chiếu ledger, tồn và số tiền.
4. **P0 · Nội dung, SEO và quyền riêng tư — Owner và QA.** Duyệt title/canonical/hreflang, sitemap, robots, JSON-LD sản phẩm/breadcrumb, sản phẩm đã gỡ và trang riêng tư. Duyệt trang liên hệ, giao hàng, thanh toán và đổi trả qua CMS/menu. Nếu bật CDN, kiểm tra hai phiên độc lập để xác nhận HTML công khai không lộ giỏ, giá thành viên, token hay thông tin cá nhân.
5. **P0 · Diễn tập trên staging — vận hành và QA.** Build bằng lockfile của release. Chạy browser mobile với ngân sách hiện có JS ≤ 64 KB, CSS ≤ 96 KB chưa nén. Chạy k6 với dữ liệu đủ tồn, đối chiếu số đơn thành công thực tế với mục tiêu 500 đơn/phút, p95 HTML < 300 ms, API < 200 ms, checkout < 1 s, lỗi < 1%, không vượt tồn. Khi bật CDN, đo riêng tải đọc ở edge theo NFR 5.000 request/s. Lưu cấu hình tải và kết quả cùng mã release.
6. **P0 · Quyết định mở bán — người phụ trách release.** Áp dụng runbook production, kiểm tra plugin cache, plugin doctor, security check, health, Redis, Horizon, scheduler, email cảnh báo, backup và phục hồi trên môi trường tách biệt. Mục tiêu RPO ≤ 5 phút, RTO ≤ 2 giờ. Owner xác nhận nội dung và các điều kiện kinh doanh trong go-live gate. Chỉ mở bán sau khi hoàn tất các bước P0 và có người nhận cảnh báo.
7. **Sau phát hành — vận hành.** Theo dõi lỗi đặt đơn, giao dịch chờ, queue trễ, tỷ lệ lỗi và tốc độ. Nếu sai tổng tiền, đặt trùng hoặc mất phương thức giao/thanh toán, dừng nhận đơn theo runbook, bảo toàn giao dịch đang xử lý, quay về release tốt và đối soát các đơn trong khoảng sự cố. Chạy lại smoke test trước khi mở bán lại.

## Bằng chứng kiểm thử local

- [Audit Flat Ecommerce Luxury](flat-luxury-audit.md) ghi phạm vi thay đổi trực tiếp trong vani-base, kiểm tra bảy chiều rộng, số đo tải trên Herd và các điều kiện còn cần nghiệm thu trên thiết bị thật.

- Browser dùng logo ngang và dọc ở trang chủ mặc định và trang chủ dựng bằng block, kiểm kích thước ảnh và ngân sách tải. Có ca 320 px, bỏ qua điều hướng bằng bàn phím, Back/Forward trên mobile, gắn sản phẩm vào Lookbook và đặt COD trên mobile.
- Browser kiểm tài khoản, giá dành cho thành viên và số món ở cả hai chế độ phiên. Khi đóng gợi ý bằng Escape, phản hồi đến chậm bị bỏ qua. Đóng modal tìm kiếm trả focus về nút mở.
- Browser kiểm bấm thêm hàng hai lần, cập nhật số lượng từ giỏ mini và mất phản hồi sau khi máy chủ đã thêm hàng. Khi mất phản hồi, khách được đưa tới giỏ để kiểm tra số lượng đã ghi.
- Bộ test concurrency trên MySQL bao phủ tranh tồn, tranh lượt voucher, đặt lặp một giỏ và callback thanh toán trùng. Lưu kết quả chạy trên DB kiểm thử theo release. Khi nâng từ bản Lookbook cũ, áp dụng bước nâng plugin trong runbook production để tạo cột liên kết sản phẩm.

## Ma trận nghiệm thu staging

Lấy một bộ fixture có ít nhất một SKU còn hàng, một SKU chỉ còn một đơn vị, một size hết hàng, một sản phẩm mất ảnh và một sản phẩm có slug đã đổi. Thêm hai tài khoản có giỏ và nhóm giá khác nhau, một phiên khách độc lập và một đơn được phép tra cứu bằng token. Dùng cùng bộ dữ liệu để đối chiếu desktop, iOS/Safari và Android/Chromium.

| Luồng | Ca phải kiểm tra | Bằng chứng cần lưu |
| --- | --- | --- |
| Trang chủ → listing | Có và thiếu block, slideshow một hoặc nhiều ảnh, giảm chuyển động, menu hai locale. Mở drawer lọc ở desktop/mobile, chọn lọc bằng bàn phím rồi Back/Forward. Thử gợi ý đến muộn sau khi nhấn Escape. | Ảnh ở 320/390 px và desktop, URL lọc trước/sau, phần tử nhận focus và locale. |
| PDP → giỏ | Chọn màu/size, khớp ảnh và giá với SKU, bật/tắt giá thành viên, bấm hai lần, thử mất phản hồi sau khi server đã ghi. | SKU và số lượng trong giỏ, giá qua parity, phản hồi sau khi tải lại. |
| Checkout → đơn | Đặt COD và từng cổng online sẽ bật. Thử báo giá lại sau thay địa chỉ, voucher, giá hoặc tồn. Gửi lại bằng cùng khóa chống đặt trùng. Kiểm callback đến muộn hoặc trùng trong sandbox. | Mã đơn thử, trạng thái cổng, tổng tiền, số lần giữ tồn/thu tiền. Che dữ liệu cá nhân trong bằng chứng. |
| Nội dung và media | Kiểm nguồn ảnh gốc và bản thu nhỏ, yêu thích vãng lai, Lookbook có sản phẩm ẩn, thông báo CMS sát giờ bắt đầu/kết thúc. | HTTP status và Content-Type của ảnh, kết quả trước/trong/sau lịch tại origin và edge. |
| Sau mua | Đối chiếu quyền truy cập bằng hai tài khoản và một khách vãng lai. Kiểm đơn nhiều kiện, trang tra cứu, huỷ và đổi trả trong phạm vi release. | Kết quả cùng mã đơn hoặc correlation ID và điều kiện được phép thao tác. |

### Kiểm tra media và cache trước khi bật lưu lượng

- Với disk `public`, kiểm tra `public/storage` trên release đang phục vụ trỏ đến vùng media dùng chung. Tải thử ảnh gốc dưới `/storage/media/`, một URL thu nhỏ dưới `/cache/` và ảnh đang được chọn từ `srcset`. Yêu cầu HTTP 200, Content-Type ảnh và đúng kích thước. Lỗi 403 trên ảnh gốc có thể do thiếu symlink hoặc quyền truy cập. [Runbook production](../18-operations/production.md)
- Khi dùng CDN, kiểm từng loại URL và query ở cả origin lẫn edge. Khoá cache giữ locale và query ảnh hưởng tới HTML như lọc, tìm và phân trang. Trang công khai có thể cache; phiên, giỏ, checkout, tài khoản, preview có chữ ký và callback giữ riêng tư. HTML công khai giống nhau giữa hai phiên; giá, giỏ và dữ liệu tài khoản đúng theo phiên sau khi tải.
- Đo thông báo CMS sát hai mốc lịch và kiểm edge tôn trọng `Cache-Control` từ origin, gồm cả `stale-while-revalidate`. Đợt giảm giá nhạy giờ cần ghi cách purge hoặc hạ TTL nếu chọn bật CDN. Purge tự động theo sự kiện còn ở L4. [Roadmap mục 6b](../20-roadmap/reference-parity-refactor.md#6b-giới-hạn-còn-lại--để-xử-lý-sau-owner-2026-11-05)

### Lệnh và hồ sơ phát hành

Chạy các lệnh dưới đây với checkout của release và cơ sở dữ liệu kiểm thử riêng. Bộ concurrency yêu cầu MySQL kiểm thử và có ca dọn dữ liệu giữa các lượt.

```bash
npm ci
npm run build
php artisan test --compact
vendor/bin/pest --compact --group=browser tests/Browser/StorefrontMobileBudgetTest.php tests/Browser/StorefrontHeaderTest.php tests/Browser/StorefrontCartTest.php tests/Browser/CheckoutMobileTest.php tests/Browser/LookbookProductsTest.php
vendor/bin/pest --compact --group=concurrency tests/Concurrency
```

Chạy k6 trên staging có đủ tồn, COD bật và IP máy chạy nằm trong `VANI_LOAD_TEST_IPS` của staging.

```bash
k6 run -e BASE="$STAGING_BASE_URL" -e ORDERS_PER_MIN=500 -e PAGE_RPS=200 -e DURATION=10m scripts/load/storefront.js
```

Kịch bản yêu cầu `BASE` rõ ràng. Chỉ bật lượt xem và đặt đơn khi `ORDERS_PER_MIN` lớn hơn 0. Lưu `orders_created`, tỷ lệ checkout thành công, số lượt bị bỏ bởi bộ phát tải, p95 theo nhóm request và đối soát tồn sau test. Kết quả ApacheBench local chỉ dùng đo nhanh độ trễ. Nghiệm thu k6 có ghi đơn trên staging vẫn ở L47. [NFR](../02-architecture/overview.md#7-thuộc-tính-chất-lượng-nfr) · [Kịch bản k6](../../scripts/load/storefront.js)

Phiếu phát hành cần có commit, phiên bản plugin, chế độ cache, kết quả từng gate, người xác nhận và thời điểm thử. Kiểm `vani:plugin:doctor` sau khi nâng các plugin có migration riêng. Với Wishlist 1.4.0, plugin upgrade thêm nhóm giá vào mốc so sánh. Lưu health, hàng đợi, scheduler và bằng chứng khôi phục cùng phiếu phát hành. [Runbook production](../18-operations/production.md)

### Điều kiện dừng và xác minh sau khôi phục

| Dấu hiệu | Việc cần xác minh trước khi mở lại |
| --- | --- |
| Sai tổng, đặt trùng hoặc giữ tồn sai | Chặn đặt đơn theo runbook. Đối chiếu đơn, thanh toán, kho và khoá chống trùng trong khoảng sự cố. Chạy lại một đơn COD và một ca gửi lặp trên môi trường an toàn. |
| Edge trả nhầm giá, giỏ hoặc dữ liệu phiên | Ngừng phục vụ bản HTML bị ảnh hưởng. Kiểm quy tắc cache/bypass rồi thử lại bằng hai phiên khác nhau cho đến khi HTML và dữ liệu riêng đúng phạm vi. |
| Ảnh hoặc asset chính lỗi diện rộng | Kiểm symlink media, cấu hình origin và cả manifest Vite lẫn tài nguyên plugin của release. Kiểm ảnh đầu PDP, thẻ listing và thao tác giỏ sau khôi phục. |

## Đề xuất bổ sung có điều kiện

- **P1 sau mở bán.** Chỉ triển khai purge CDN theo event nếu bật cache HTML và thời gian chờ TTL vượt yêu cầu cập nhật catalog. Chỉ mở rộng page builder sang landing page khi CMS và Lookbook hiện có chưa đáp ứng chiến dịch cụ thể.

Nguồn đối chiếu: [Storefront](storefront.md), [ADR-044](../19-adr/ADR-044-storefront-api-first.md), [NFR](../02-architecture/overview.md), [kiểm thử](../17-testing/testing.md), [runbook production](../18-operations/production.md), [go-live gate](../20-roadmap/roadmap.md).
