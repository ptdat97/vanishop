# ADR-051 — Cache trang có tag, xoá theo sự kiện

- Trạng thái: **Accepted** (Owner duyệt 2026-11-27, đợt 1–3) · Code: đợt 1–3 xong ở Core 0.5.101 (Extension API 1.45.0); đợt 4–5 chưa làm · Ngày: 2026-11-26 · Người đề xuất: AI agent (vai trò nghiên cứu, [clean-room §7](../01-principles/clean-room-license.md#7-lưu-trữ-mã-nguồn-tham-khảo))
- Liên quan: [ADR-025](ADR-025-native-storefront-ssr-slots.md) (SSR-first), [ADR-044](ADR-044-storefront-api-first.md) (HTML là bản khách vãng lai, phần cá nhân tải bằng JS), [storefront §5](../14-storefront/storefront.md), [operations §5](../18-operations/operations.md#5-cache), [§6b](../20-roadmap/reference-parity-refactor.md) L4, L59, L60, L62–L66.

## Context

- Trang công khai (trang chủ, danh mục, thương hiệu, sản phẩm, trang CMS) **render được không dùng phiên**: HTML là bản khách vãng lai, giá theo nhóm khách và trạng thái tài khoản tải sau bằng Storefront API (ADR-044). Nhưng chế độ không phiên chỉ bật cùng cache CDN (`VANI_PAGE_CACHE=true`, **mặc định tắt**); mặc định trang chạy có phiên và in sẵn phần riêng của khách (nhãn tài khoản, số món, flash, giá thành viên). Middleware cache trang chỉ gửi header cho CDN (`s-maxage`, `stale-while-revalidate`). *(Sửa 2026-11-27: bản đề xuất viết nhầm là HTML luôn là bản vãng lai.)*
- Không có CDN thì mỗi lượt xem render lại từ đầu. Sau khi sửa ở Core 0.5.100, trang chủ dữ liệu demo vẫn cần ~105 truy vấn, ~115 ms trên dev. Phần lớn đến từ các khối sản phẩm, mỗi khối tự dựng thẻ sản phẩm.
- Có CDN thì sửa giá, ẩn sản phẩm, đổi menu phải chờ hết `s-maxage`: chưa xoá theo sự kiện (L4).
- Cache ứng dụng hiện có: số đếm và facet theo phiên bản read model, cài đặt theo phiên bản, vài khối cache theo TTL (L59). Không có cache cả trang.
- Đã khảo sát cách hai hệ tham chiếu làm (ngoại lệ Owner cho phép 2026-11-26, ghi ở clean-room §7). Hệ thương mại lớn hơn cache **cả response**, gắn **tag theo dữ liệu đã đọc** khi render, rồi xoá theo tag khi dữ liệu đổi, gom lại và xoá theo lô định kỳ. Hệ còn lại cache từng mảnh theo TTL, chỗ xoá cache nằm rải rác, không có cache trang. Đây là cách VaniShop không muốn lặp lại.

## Problem

Cửa hàng SME thường chạy một VPS, không có CDN. Làm sao để trang công khai trả về nhanh mà không cần CDN, và dữ liệu đổi (giá, tồn, hiển thị, menu, Builder) hiện ra trong vòng một phút, không phải chờ TTL dài hay xoá toàn bộ cache?

## Decision

1. **Cache trang trong ứng dụng** cho các route công khai không dùng phiên (đúng danh sách route của middleware cache trang hiện có), chỉ với `GET`/`HEAD` trả 200 và không đặt cookie.
   - HTML lưu ở store cache mặc định: mất do bị đẩy ra hay `cache:clear` thì chỉ render lại, nên không dùng store `durable`.
   - Chế độ đặt qua `VANI_PAGE_CACHE`:
     - `app` (đề xuất mặc định): cache trong ứng dụng.
     - `cdn`: như `app`, thêm header cho CDN.
     - `off`: tắt.
   - Trang 404, trang tìm kiếm có từ khoá, trang có tham số lọc vượt ngưỡng không lưu, vì số tổ hợp quá lớn so với lượt trúng cache.
2. **Tag theo dữ liệu đã dùng.** Trong request có một bộ gom tag (contract mới ở Shared, `#[PluginApi]`). Presenter, read model và khối Builder ghi tag cho thứ mình đọc:
   - `product:{id}` cho từng thẻ và trang sản phẩm;
   - `category:{id}`, `collection:{id}`, `brand:{id}` cho trang danh sách;
   - `catalog:listing` cho lưới "mới nhất", "bán chạy" và khối không cố định danh sách;
   - `builder:{vùng}`, `menu:{vị trí}`, `settings:{namespace}`, `theme`;
   - plugin dùng tag riêng có tiền tố mã plugin (ví dụ `vani.cms:page:{id}`).

   Trang ghi quá nhiều tag sản phẩm (ngưỡng cấu hình, ví dụ 200) thì thay bằng tag thô `catalog:listing`. Khối hoặc slot có nội dung phụ thuộc request (vị trí, A/B, giờ thực) phải đánh dấu trang **không cache**.
3. **Kiểm tra bằng phiên bản tag, không xoá bản ghi.** Mỗi tag có một phiên bản: chuỗi ngẫu nhiên lưu ở store mặc định; bị đẩy ra thì sinh phiên bản mới, tức là coi như đã xoá.
   - Bản cache lưu kèm phiên bản của mọi tag lúc render.
   - Khi đọc, lấy phiên bản hiện tại của các tag đó (một lệnh đọc nhiều khoá); lệch một tag là coi như không có cache.
   - Cách này chạy với mọi store, kể cả `database` (tag có sẵn của Laravel chỉ hỗ trợ Redis/Memcached), cùng mẫu với `ListingCacheVersion` và cài đặt (L58).
   - Mỗi bản có hạn tối đa (đề xuất 1 giờ) làm lưới an toàn khi sót tag.
4. **Xoá theo sự kiện, gom theo lô.** Một bảng chuyển các event domain đã có thành tag:
   - đổi read model sản phẩm: `product:{id}` và tag của danh mục, bộ sưu tập, thương hiệu chứa sản phẩm, cùng `catalog:listing`;
   - đổi giá, đổi tồn có làm đổi trạng thái còn hàng, `SettingChanged`, lưu Builder, menu, theme;
   - event của plugin.

   Tag cần xoá được ghi vào hàng đợi trong store `durable`. Một lệnh của scheduler chạy **mỗi phút** để đổi phiên bản các tag đó và phát event `PageCacheTagsInvalidated` cho plugin xoá CDN (L4). Thao tác hàng loạt (nhập file, sửa giá 200 sản phẩm) vì thế chỉ đổi phiên bản một lần. Một số thay đổi xoá **ngay**: bật/tắt bảo trì, đổi chế độ hiện giá, đổi theme, và nút "Xoá cache trang" trong Admin.
5. **Khoá cache.** Gồm đường dẫn; query string đã sắp xếp và bỏ tham số theo dõi (`utm_*`, `gclid`, `fbclid`…, danh sách cấu hình); ngôn ngữ; chế độ hiện giá; phiên bản theme; mã bản build (deploy mới thì dùng khoá mới).
6. **Chống dồn tải.** Không có cache thì lấy khoá theo khoá trang trong vài giây. Request khác cùng trang thì chờ ngắn rồi đọc lại; chờ quá hạn thì tự render, không lỗi. Mảnh dùng chung đắt (sitemap, khối bán chạy, menu) dùng "phục vụ bản cũ, tính lại nền" (L59).
7. **CDN và API dùng chung nguồn tag.** Chế độ `cdn` gửi thêm header danh sách tag (tên header cấu hình theo nhà cung cấp). GET công khai của Storefront API (L60) dùng cùng bộ gom tag, chính sách và hàng đợi xoá.
8. **Quan sát được.** Header `X-Vani-Cache: HIT | MISS | BYPASS` kèm lý do bỏ qua, bộ đếm tỉ lệ trúng trên Pulse, và một lệnh liệt kê tag của một URL để gỡ lỗi.

## Quyết định của Owner (2026-11-27)

- Làm đợt 1–3; đợt 4 (Storefront API, L60) để đợt sau.
- Mặc định `app`: mọi trang công khai chạy **không phiên** như chế độ CDN cũ, phần riêng của khách tải bằng JS (khách đã đăng nhập thấy nhãn tài khoản, số món hiện chậm một nhịp). `off` giữ hành vi có phiên.
- Không có cron: request trang công khai thấy lô cuối đã quá 2 phút thì tự chạy lô sau khi trả response (khoá, một request mỗi phút). Health check `scheduler` vẫn cảnh báo.

## Ghi chú triển khai (0.5.101)

- Contract: `Shared\Contracts\PageCacheTags`, `PageCacheInvalidator`, event `Shared\Events\PageCacheTagsInvalidated` (đều `experimental`). Plugin: `PluginServiceProvider::pageCacheModels()`.
- Nguồn tag: `CatalogReader` (Catalog tự ghi cho dữ liệu nó trả ra), `Settings`, Builder, menu. Bảng event → tag: `Catalog\Listeners\QueuePageCacheInvalidation` (sản phẩm, giá, tồn) + model event của danh mục/thương hiệu/bộ sưu tập/thuộc tính/tuỳ chọn; `Storefront\Listeners\InvalidatePageCacheOnSettingChange` (cài đặt, Builder, menu, xoá ngay).
- Ngoài §3: hạn bản cache ngắn lại tới mốc **lịch bảng giá** (`PriceListSchedule::nextChangeAfter`), **khung giờ sản phẩm** (`Catalog\Contracts\PublicationSchedule`) và khung giờ mục menu — sale hẹn giờ hiện đúng lúc, không chờ tới 1 giờ.
- Render bắt đầu trước một lô xoá mà lưu sau lô thì bỏ (khoá "thế hệ" đổi mỗi lần xoá) — tránh lưu HTML đọc dữ liệu cũ với phiên bản tag mới.
- Tìm kiếm có từ khoá, quá 4 giá trị lọc: không lưu trong ứng dụng; chế độ `cdn` vẫn cho CDN cache như trước.
- Plugin `vani.cms`, `vani.lookbook`, `vani.bundles`, `vani.size-chart`: lưu bản ghi thì xoá toàn bộ cache trang (lô kế tiếp) — §6b L67.
- Tag gom trong bộ nhớ, ghi hàng đợi một lần cuối request/lệnh/job: nhập file nghìn sản phẩm chỉ ghi hàng đợi một lần.
- Đo 2026-11-27 (dữ liệu demo, store `database`, dev): HIT 4 truy vấn bảng `cache`, 2–4 ms; MISS trang chủ 137 truy vấn/180 ms, sản phẩm 66/40 ms ([§6b](../20-roadmap/reference-parity-refactor.md) L68).

## Alternatives

- **Gộp thẻ sản phẩm của mọi khối vào một lần nạp** (ước lượng ~4 ngày, bớt ~65 truy vấn ở trang chủ). Vẫn render mỗi lượt xem. Khi đã có cache trang, cách này chỉ giúp lần render đầu sau mỗi lần xoá, nên để sau (L66).
- **Cache từng khối theo TTL.** Ít việc hơn nhưng giá/tồn trên khối trễ theo TTL, và chỗ xoá cache rải rác như hệ tham chiếu thứ hai.
- **Chỉ dựa vào CDN.** SME không có CDN thì không được gì; xoá theo sự kiện vẫn phải làm (L4).
- **Dùng tag cache có sẵn của Laravel** hoặc gói response cache bên thứ ba. Không chạy với store `database` mặc định; thêm phụ thuộc cần Owner duyệt.
- **Cache riêng cho khách đã đăng nhập** (biến thể theo ngữ cảnh khách như hệ tham chiếu lớn). Không cần: HTML đã là bản vãng lai, phần cá nhân tải bằng JS (ADR-044).

## Consequences

- Trang công khai trúng cache trả về trong vài ms, không truy vấn DB, cho mọi khách vãng lai, kể cả khi không có CDN.
- Thay đổi hiện ra sau tối đa ~1 phút (lô xoá). Riêng các thay đổi "xoá ngay" thì tức thì. Tài liệu vận hành và Admin phải nói rõ điều này.
- Presenter, khối Builder và slot của plugin phải ghi tag đúng. Sót tag thì trang cũ tối đa bằng hạn của bản cache (1 giờ). Plugin có khối hoặc slot hiển thị dữ liệu cần ghi tag, hoặc đánh dấu không cache; checklist plugin thêm mục này.
- Store mặc định lớn thêm khoảng (số trang được xem × kích thước HTML, ~50–150 KB). Với store `database`, cần dọn dòng hết hạn của bảng `cache` (mở rộng lệnh dọn của L57).
- Test mặc định chạy với cache trang tắt; có test riêng cho HIT/MISS, xoá theo tag, khoá cache và việc không lộ dữ liệu cá nhân.
- **Clean-room:** ADR này viết ở vai trò nghiên cứu sau khi đọc mã tham chiếu. Việc code phải làm ở phiên khác, chỉ đọc ADR này và tài liệu VaniShop (clean-room §3.4).

## Trade-offs

- Đổi việc "luôn đúng tức thì" lấy "đúng trong ~1 phút" để thao tác hàng loạt không xoá cache liên tục.
- Mỗi lần đọc cache phải lấy phiên bản của mọi tag trên trang. Trần số tag và tag thô `catalog:listing` giữ chi phí này nhỏ, đổi lại một thay đổi sản phẩm có thể làm render lại nhiều trang danh sách hơn mức cần.
- Tự viết lớp phiên bản tag (khoảng vài trăm dòng) thay cho dùng gói có sẵn, để chạy được trên store `database` của SME và không thêm phụ thuộc.

## Triển khai theo đợt (khi Owner duyệt)

| Đợt | Nội dung | Khối lượng | §6b |
|---|---|---:|---|
| 1 | Contract bộ gom tag, ghi tag ở presenter, Builder, menu, cài đặt; trần số tag; đánh dấu không cache | ~1 ngày | L62 |
| 2 | Bảng event → tag, hàng đợi tag trong `durable`, lệnh xoá mỗi phút, xoá ngay, event cho CDN | ~1 ngày | L63 |
| 3 | Middleware cache trang trong ứng dụng: chế độ `app/cdn/off`, khoá cache, phiên bản tag, chống dồn tải, header `X-Vani-Cache`, nút xoá trong Admin, dọn bảng `cache` | ~2 ngày | L64 |
| 4 | Storefront API GET công khai dùng chung cơ chế | ~1.5 ngày | L60 |
| 5 | Plugin xoá CDN theo tag hoặc URL (khi mở lại kết nối ngoài) | — | L4 |
