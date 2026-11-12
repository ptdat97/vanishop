# Củng cố kiến trúc API-first cho Storefront

> Trạng thái: **Approved** (đánh giá và kế hoạch 2026-11-11, Core 0.4.6; Owner duyệt cả bốn quyết định §4 cùng ngày). Trạng thái từng đợt ghi ở §5.
>
> Liên quan: [ADR-009](../19-adr/ADR-009-storefront-architecture.md) (native và headless dùng cùng Application layer), [ADR-021](../19-adr/ADR-021-storefront-composition-module.md) (module Storefront là tầng ghép), [ADR-022](../19-adr/ADR-022-guest-cart-token.md) / [ADR-024](../19-adr/ADR-024-customer-api-token.md) (token giỏ, token khách), [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md) (SSR + slot), [storefront](../14-storefront/storefront.md), [api](../06-api/api.md), [vision §2.1](../00-overview/vision.md#21-định-hướng-giai-đoạn-bắt-đầu-từ-sme-mở-rộng-khi-doanh-nghiệp-lớn-lên) và R36 (SME trước).

> **Storefront API v1 là hợp đồng của mọi tính năng storefront. Storefront Blade (theme `vani-base`) chỉ là một cách dùng hợp đồng đó — cách mặc định cho cửa hàng SME — chứ không phải nơi định nghĩa hành vi.**

## 1. Kết luận

Nền móng đúng hướng: tầng ghép `modules/Storefront` có sẵn, controller Web và controller API cùng gọi các Presenter của `Storefront\Application` (ADR-021), controller không truy vấn DB, luồng mua hàng có đủ ở cả hai kênh (59 endpoint API, 58 route Web).

Nhưng "API-first" mới đúng ở **lớp đọc dữ liệu**, chưa đúng ở **lớp hành vi**:

1. Đầu vào và một phần quyết định nghiệp vụ được viết riêng ở controller Web, nên hai kênh đã lệch nhau thật: quy tắc 0.3.104 (mã giảm giá không dùng được thì bỏ và vẫn đặt đơn) chỉ có ở checkout Blade.
2. Phần "vỏ" của trang (menu, đầu/chân trang, trang chủ dựng bằng khối, tìm kiếm phổ biến) chỉ có qua `View::composer`, không có endpoint — một client headless không dựng được header, footer hay trang chủ.
3. Có một bề mặt JSON thứ hai ngoài API (`/_vani/*`, `/p/vani-wishlist/*`) để phục vụ JS của theme, vì API chỉ nhận token còn Blade dùng phiên.
4. Hợp đồng HTTP của Storefront không được mô tả và khoá: không có OpenAPI, không có test schema, tài liệu `api.md` mô tả khác code.

Kế hoạch ở §5 sửa bốn điểm trên mà **không** làm thêm hạ tầng: Blade vẫn render phía server, vẫn gọi Application layer trong cùng process (không tự gọi HTTP tới chính mình), không thêm Node/SPA.

## 2. Mô hình đích

```text
Commerce Core (modules/*: Contracts)
      ↓
Storefront Application  ── use-case (query/command) + Input DTO + Output DTO  ◄── MỘT nơi định nghĩa hành vi
      ↓                                   ↓
/api/storefront/v1 (HTTP JSON)       Storefront Blade (SSR, in-process)
  ├─ JS của theme (giỏ mini, gợi ý,     └─ controller Web mỏng: FormRequest dùng chung
  │   giá thành viên, yêu thích…)          → use-case → view; lỗi → redirect + errors
  ├─ App mobile / Zalo Mini App
  └─ Headless (khi doanh nghiệp lớn lên)
```

Năm nguyên tắc (đề xuất thành rule **R37**, §5 đợt A0):

1. **Hành vi ở use-case, không ở controller.** Mỗi việc khách làm được trên storefront (xem, lọc, thêm giỏ, áp mã, đặt hàng, đổi trả, sửa địa chỉ…) là một use-case trong `Storefront\Application` (hoặc contract của module sở hữu). Controller Web và API chỉ chuyển đổi đầu vào/đầu ra.
2. **Một hợp đồng đầu vào.** Luật validate của một use-case viết một lần (FormRequest/Input DTO dùng chung); Web và API khác nhau ở cách trả lỗi, không ở luật.
3. **Có trên Blade thì có trên API.** Tính năng storefront mới (Core hoặc plugin) phải có endpoint API trước hoặc cùng lúc; trang Blade là tuỳ chọn. Ngoại lệ có danh sách: phần chỉ thuộc trình bày web (sitemap, robots, trang trả về của cổng thanh toán, mã theo dõi quảng cáo).
4. **Một bề mặt JSON.** JS của theme chỉ gọi `/api/storefront/v1`; không thêm endpoint JSON riêng cho theme.
5. **Hợp đồng được khoá.** Storefront API v1 có OpenAPI và test schema như Integration API; đổi phá vỡ theo [ADR-010](../19-adr/ADR-010-api-versioning.md).

## 3. Đánh giá hiện trạng

### 3.1 Đã đạt

| Tiêu chí | Bằng chứng |
|---|---|
| Một tầng ghép cho cả hai kênh | Controller Web và API cùng dùng `ProductViews`, `CartPresenter`, `CheckoutPresenter`, `OrderPresenter`, `PaymentPresenter`, `AddressOptions`, `MemberPrices` |
| Controller không chứa persistence | Không có `DB::`/`::query()`/`Persistence` trong `modules/Storefront/Http/Controllers/{Web,Api}` |
| Luồng mua hàng có đủ trên API | Danh mục, thương hiệu, sản phẩm + lọc/sắp xếp, gợi ý tìm kiếm, giỏ (token), chọn dòng, quote, đặt hàng (idempotency), đơn, huỷ, thanh toán lại, đã nhận, đổi trả, tài khoản (OTP, mật khẩu, địa chỉ, consent, xuất/xoá dữ liệu) |
| Dữ liệu plugin tới được API | `StorefrontEnricher` áp ở `ProductViews` → API cũng nhận; `vani.cms`, `vani.lookbook`, `vani.wishlist` có endpoint `x/<plugin>` |
| Quy ước lỗi, ngôn ngữ, ẩn giá chung | `ApiErrorRenderer`; `X-Vani-Locale`; `vani.prices-visible` ở cả hai kênh |

### 3.2 Lệch so với API-first

| # | Lệch | Bằng chứng (2026-11-11) | Hệ quả |
|---|---|---|---|
| L-A1 | **Luật đầu vào viết hai lần** | Checkout: 17 luật inline ở `Web/CheckoutController` và `CheckoutRequestForm` của API; tài khoản/địa chỉ: 19 luật ở `Web/AccountSettingsController` và 19 ở `Customer/Api/AccountController`; đổi trả: `Web/ReturnController` và `Api/OrderController` | Sửa một kênh, quên kênh kia |
| L-A2 | **Quyết định nghiệp vụ nằm ở controller Web** | Quy tắc bỏ mã giảm giá không dùng được rồi vẫn đặt đơn (0.3.104) chỉ ở `Web/CheckoutController::store`: Blade bỏ mã trước khi gọi, còn `CheckoutService::placeOrder` ném `CheckoutRejected::vouchers` nên API trả lỗi. (Ngược lại, quy tắc "bắt đăng nhập" đã đúng chỗ: chặn trong `CheckoutService` cho mọi kênh, controller Web chỉ chuyển hướng sớm.) | **Hai kênh đã lệch hành vi**: cùng giỏ, cùng mã hỏng, Blade đặt được đơn, API bị từ chối |
| L-A3 | **Hai mô hình mã giảm giá** | Blade giữ **một** mã trong phiên (`CartVoucher`, `voucher_code`); API nhận **tối đa 5** mã mỗi lần gọi (`voucher_codes[]`) | Khó gom về một use-case; khách đổi kênh mất mã |
| L-B1 | **"Vỏ" trang không có API** | Menu (`MenuPresenter`), đầu/chân trang (`ThemeSettings`), tìm kiếm phổ biến, SEO hreflang chỉ được đưa vào view qua `View::composer`; trang chủ dựng bằng khối (`PageBlocks`) không có endpoint — 9 khối của `vani.storefront-extras`, 2 của `vani.cms`, 2 của `vani.lookbook` chỉ tới được Blade | App/headless không dựng được header, footer, trang chủ |
| L-B2 | **Cấu hình cửa hàng công khai không có API** | Ẩn giá với khách vãng lai, cho phép mua không đăng nhập, ngôn ngữ, tiền tệ, thông tin liên hệ chỉ đọc được ở phía server | Client phải đoán hoặc ghi cứng |
| L-C1 | **Bề mặt JSON thứ hai** | `GET /_vani/phien` (trạng thái phiên khách), `GET /_vani/gia` (trùng ý với `GET /me/prices`), `POST /_vani/xem` (lượt xem); `vani.wishlist` có cả `/p/vani-wishlist/{ids,toggle,merge}` lẫn `x/vani-wishlist/items` | Hai hợp đồng cho một việc; endpoint `_vani` không có version, không vào OpenAPI |
| L-C2 | **Xác thực tách đôi** | API: `Authorization: Bearer` + `X-Vani-Cart-Token` (ADR-022/024); Blade: phiên cookie (`vani.customer-session`) | Nguyên nhân gốc của L-C1: JS của theme không gọi được endpoint cần đăng nhập của API |
| L-D1 | **Hợp đồng HTTP chưa được khoá** | Có `docs/api/openapi/integration-v1.json`, **không** có bản Storefront; 4 file test API storefront kiểm hành vi, không kiểm schema; `public-api.snapshot` chỉ chụp contract PHP | Đổi tên/kiểu một trường JSON không làm đỏ test nào |
| L-D2 | **Tài liệu lệch code** | `api.md §2` ghi response bọc Eloquent API Resource và phân trang cursor; code trả mảng từ Presenter, phân trang `page`/`per_page` | Người viết client tin sai tài liệu |
| L-E1 | **Tính năng plugin chỉ có HTML** | 54 slot `vani.storefront.*` trả HTML; plugin chỉ dùng slot cho phần có dữ liệu thì client khác không thấy | Chấp nhận cho phần trình bày (mã theo dõi, banner thông báo); không chấp nhận cho dữ liệu khách cần |

Bảng plugin có mặt trên storefront (đếm trong `ServiceProvider` và mã plugin):

| Plugin | Trang Blade | API `x/<plugin>` | Slot HTML | Enricher (tới API) | Khối trang chủ |
|---|---|---|---|---|---|
| `vani.cms` | có | có | 5 | 2 | 2 |
| `vani.lookbook` | có | có | 1 | — | 2 |
| `vani.wishlist` | có | có (+ JSON phiên riêng) | 4 | — | — |
| `vani.storefront-extras` | — | — | 2 | — | **9, không có API** |
| `vani.size-chart` | — | — | 1 | 2 | — |
| `vani.scarcity-signals` | — | — | 3 | 3 | — |
| `vani.shipping-flat-rate` | — | — | 3 | 2 | — |
| `vani.tracking-pixels` | — | — | 6 | — | — (chỉ web, hợp lệ) |
| `vani.vnpay` | trang trả về | — | — | — | — (chỉ web, hợp lệ) |

## 4. Quyết định của Owner (2026-11-11)

Owner chọn đúng các đề xuất dưới đây. Bổ sung cho Q4: **VaniShop đang ở môi trường dev** — không cần giữ tương thích dữ liệu hay giao diện: gỡ route cũ ngay (không alias), được đổi HTML/URL của Blade, không chuyển dữ liệu phiên/giỏ cũ.

| # | Câu hỏi | Quyết định | Lý do |
|---|---|---|---|
| Q1 | JS của theme gọi API bằng gì khi khách đã đăng nhập bằng phiên web? | **API nhận thêm phiên web cho request cùng nguồn** (cookie phiên + CSRF, kiểu "first-party"), bên cạnh Bearer/cart token. Ghi thành ADR-044 | Bỏ được toàn bộ `/_vani/*` và JSON phiên của wishlist (L-C1) mà không bắt theme giữ token trong JavaScript. Phương án khác — để theme tự giữ Bearer token — lộ token cho script trên trang và phải làm lại luồng đăng nhập Blade |
| Q2 | Một hay nhiều mã giảm giá? | **Gom về `voucher_codes[]`** (API đã có); Blade hiện một ô nhập, lưu danh sách trong giỏ thay vì trong phiên | Một use-case, một mô hình; giữ được trải nghiệm một ô của SME |
| Q3 | Quy tắc 0.3.104 (bỏ mã không dùng được, vẫn đặt đơn) áp cho API? | **Có** — chuyển vào use-case đặt hàng, API trả mã bị bỏ trong `meta.dropped_vouchers` | Cùng giỏ thì cùng kết quả ở mọi kênh |
| Q4 | Phạm vi | **Chỉ Storefront API, phá vỡ thoải mái trên dev.** Admin API vẫn đóng băng; không làm app/headless mẫu trong đợt này | Theo quyết định đóng băng 2026-11-05 và R36; dev chưa có dữ liệu thật cần giữ |

## 5. Kế hoạch theo đợt

Thứ tự theo nguyên tắc roadmap: **Correctness → Consistency → Extensibility → Observability**. Mỗi đợt một hoặc vài commit; được đổi HTML/URL của Blade khi cần (dev, Q4); Definition of Done chung của [roadmap](roadmap.md).

| Đợt | Việc | Module | Xong khi |
|---|---|---|---|
| **A0 ✅ 2026-11-11 — Chốt nguyên tắc** | ADR-044 "Storefront API-first" (năm nguyên tắc §2 + Q1–Q4); rule **R37** trong [architecture-rules](../01-principles/architecture-rules.md); sửa `api.md §2` cho đúng code (Presenter → mảng, `page`/`per_page`) (L-D2) | docs | ADR-044 Accepted, R37 có trong architecture-rules, `api.md` khớp code |
| **A1 — Một hành vi (đúng trước)** — đang làm: ✅ quy tắc mã hỏng vào `CheckoutService` (0.4.7); ✅ mã giảm giá lưu theo giỏ, `PUT /carts/{id}/vouchers`, Blade bỏ mã trong phiên (0.4.8) | Trích use-case cho các luồng ghi: đặt hàng (gồm quy tắc 0.3.104), giỏ (thêm/sửa/chọn/mã giảm giá), đổi trả, địa chỉ/hồ sơ. FormRequest dùng chung cho Web và API; controller Web chỉ map lỗi sang redirect. Gom mã giảm giá về `voucher_codes[]` lưu theo giỏ (Q2) | Storefront, Customer, Cart | **Test song kênh**: cùng kịch bản chạy qua Web và qua API cho ra cùng đơn/tổng tiền/lỗi (đặt hàng có mã hỏng, hết hàng giữa chừng, khách chưa đăng nhập khi cửa hàng bắt đăng nhập); không còn `validate(` inline trong `Http/Controllers/{Web,Api}` của Storefront |
| **A2 — API cho "vỏ" trang** | `GET /store` (tên, liên hệ, ngôn ngữ, tiền tệ, ẩn giá, mua không đăng nhập, tìm kiếm phổ biến, cài đặt đầu/chân trang công khai của theme); `GET /menus/{location}`; `GET /pages/home` trả `[{type, data}]` từ `StorefrontBlock::resolve()` (khối plugin có ngay, kể cả 9 khối của `vani.storefront-extras`). `View::composer` của Blade gọi cùng use-case | Storefront | Dựng được header, footer, trang chủ chỉ từ API (test); Blade dựng từ cùng use-case |
| **A3 — Một bề mặt JSON** | Theo Q1: API nhận phiên web cùng nguồn; chuyển `/_vani/phien` → `GET /session`, `/_vani/gia` → `GET /me/prices` (đã có), `/_vani/xem` → `POST /products/{slug}/views`; `vani.wishlist` bỏ `/p/vani-wishlist/*`, JS dùng `x/vani-wishlist/items`. Gỡ route `_vani` cũ ngay, không alias (dev, Q4) | Storefront, Customer, `vani.wishlist`, theme | JS của theme chỉ gọi `/api/storefront/v1` (arch test quét `custom/theme/*/js`); bật cache CDN (storefront §5) vẫn chạy |
| **A4 — Khoá hợp đồng** | `docs/api/openapi/storefront-v1.json`; test đối chiếu response của mọi endpoint với schema (dùng `tests/Support/JsonSchemaSubset` như Integration); snapshot danh sách route `api/storefront/v1` | Storefront, tests | Đổi/xoá trường JSON làm đỏ test; CHANGELOG-extension ghi thay đổi API HTTP như thay đổi contract PHP |
| **A5 — Plugin theo API-first** | Quy ước trong [plugin-system](../05-plugin/plugin-system.md): plugin có `storefrontPages()` phải có `storefrontRoutes()` cho cùng dữ liệu; dữ liệu khách cần đi qua enricher/endpoint, slot HTML chỉ để trình bày. Danh sách ngoại lệ: `vani.tracking-pixels`, trang trả về của cổng thanh toán | Extension, plugin | Arch test: plugin khai báo trang Blade mà không có route API thì đỏ (trừ danh sách ngoại lệ) |

Kiểm soát ranh giới sau khi xong (thêm vào `tests/Architecture`):

- Controller trong `modules/Storefront/Http/Controllers/{Web,Api}` không gọi `validate(` inline, chỉ nhận FormRequest dùng chung (A1).
- Mọi route Web `POST/PUT/DELETE` của storefront khai báo endpoint API tương ứng trong một bảng ánh xạ; route mới thiếu ánh xạ thì đỏ (A1).
- JS của theme không gọi đường dẫn ngoài `/api/storefront/v1` (A3).
- Response API khớp OpenAPI (A4); plugin có trang Blade phải có route API (A5).

## 6. Không làm (giai đoạn SME)

- **Không chuyển Blade sang gọi HTTP tới chính mình** — vẫn in-process theo ADR-009 (thêm độ trễ, là distributed monolith).
- **Không làm app/headless mẫu (Next.js…), SDK client, GraphQL** — chỉ khoá hợp đồng để làm được khi cần. Ghi vào backlog [§6b](reference-parity-refactor.md).
- **Không mở Admin API** — vẫn đóng băng (Q4).
- **Không đưa phần trình bày web vào API**: sitemap, robots, JSON-LD, mã theo dõi, trang trả về của cổng thanh toán.

## 7. Rủi ro

| Rủi ro | Giảm thiểu |
|---|---|
| Trích use-case làm đổi hành vi checkout đang chạy | Viết test song kênh (A1) **trước** khi chuyển code; chạy bộ bất biến `tests/Feature/Invariants` và concurrency test checkout |
| API nhận phiên web mở đường CSRF | Chỉ nhận phiên khi cùng nguồn + token CSRF; endpoint ghi vẫn yêu cầu `Idempotency-Key` như hiện tại; ghi trong ADR-044 và kiểm ở `tests/Feature/Security` |
| Bỏ `/_vani/*` làm hỏng chế độ cache CDN | Test chế độ cache CDN (storefront §5) chạy với endpoint mới trước khi gỡ route cũ |
| OpenAPI viết tay lệch code | Test schema chạy trên response thật của mọi endpoint (A4), không chỉ đọc file |
