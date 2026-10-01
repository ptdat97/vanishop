# Đối chiếu với hệ tham chiếu: học gì, giữ gì

> Trạng thái: **Designed** (tài liệu nghiên cứu). Phần "áp dụng" được hiện thực hoá bằng các tài liệu và ADR dẫn link bên dưới; trạng thái code nằm ở [status](../00-overview/status.md).

## 0. Lưu ý clean-room (đọc trước)

Hệ tham chiếu là **VaniCommerce** (`~/Ecommerce/VaniCommerce`), một nền tảng Laravel SSR-first **dẫn xuất từ BeikeShop**: header phiên khách, helper hook, cấu trúc plugin và bảng `*_descriptions` của nó trùng với những chuỗi mà [clean-room](../01-principles/clean-room-license.md) cấm. Vì vậy:

- VaniCommerce chịu **cùng quy tắc với BeikeShop**: không mở mã nguồn/tài liệu của nó khi code VaniShop, không đưa vào context của AI agent khi sinh code.
- Tài liệu này là **đầu ra của vai trò nghiên cứu**: chỉ ghi **khái niệm** bằng lời của VaniShop, không chép tên hook, tên bảng, tên class, cấu trúc file hay chuỗi thông báo. Người code chỉ đọc tài liệu này và các tài liệu nó dẫn tới.

## 1. Kết luận

VaniShop **đã vượt** hệ tham chiếu ở phần lõi thương mại: tồn kho (reservation đa kho, không oversell có concurrency test), trạng thái đơn tách 4 chiều, tiền `BIGINT`, idempotency, outbox/inbox, extension point có kiểu và compatibility policy. Các khoảng trống enterprise mà chính hệ tham chiếu tự liệt kê (hook trước khi ghi, event sau commit, outbox, idempotency key, audit, kiểm tra tương thích plugin) **VaniShop đều đã có**.

Hệ tham chiếu mạnh hơn ở bốn điểm, VaniShop nên học:

1. **Bốn mặt tiền trên một lõi**: web SSR, Admin, API khách, API quản trị gọi chung một tầng nghiệp vụ, không nhân đôi logic.
2. **Storefront SSR-first có sẵn điểm chèn UI** ở khắp theme: plugin thêm giao diện mà không sửa view.
3. **Tài liệu dành cho người viết plugin**: chữ ký chính xác, thông báo lỗi khi sai, checklist, "cạm bẫy đã gặp thật".
4. **Tài liệu tham chiếu kiểm chứng từ mã nguồn**: bản đồ thành phần, vòng đời request, số liệu đếm lại từ source.

## 2. Học và áp dụng

| # | Khái niệm của hệ tham chiếu | Cách VaniShop áp dụng (thiết kế riêng) | Tài liệu |
|---|---|---|---|
| L1 | Các mặt tiền khác nhau gọi chung một tầng nghiệp vụ | Năm bề mặt (Storefront native, Storefront API, Admin, Admin API, Integration API) cùng gọi Application/Contracts; bảng bề mặt × auth × middleware kiểm chứng từ code | [system-map](system-map.md), [request-lifecycle](request-lifecycle.md) |
| L2 | Render server là chính, JS chỉ tăng cường cục bộ; không ẩn nội dung SSR chờ JS | Native storefront Blade SSR + Alpine "đảo tương tác", dữ liệu qua JSON bridge đã render, không router phía client | [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md), [storefront §5](../14-storefront/storefront.md) |
| L3 | Theme đánh dấu sẵn nhiều điểm chèn UI cho plugin | Slot UI có tên trong registry hook (`type: slot`), **chỉ nối thêm**, lỗi một listener không làm sập trang; danh mục slot storefront chốt trước khi dựng theme | [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md), [extension-point-catalog §4](../04-extension/extension-point-catalog.md) |
| L4 | Page builder kéo-thả trang chủ, plugin thêm component | `StorefrontBlock` (đã thiết kế): block có schema cho Admin editor, dữ liệu lấy qua Query | [storefront §3](../14-storefront/storefront.md) |
| L5 | Contract riêng cho plugin thanh toán/vận chuyển, ghi rõ lỗi khi thiếu, checklist | Bộ "contract cho người viết plugin" kiểm chứng từ `Contracts/` của VaniShop | [05-plugin/contracts](../05-plugin/contracts/README.md) |
| L6 | Mỗi họ extension point có ≥ 1 plugin mẫu chạy được để xác thực | Rule R26: extension point public phải có implementation tham chiếu + contract test | [architecture-rules](../01-principles/architecture-rules.md) |
| L7 | ADR ghi "cạm bẫy đã gặp thật" + nguyên tắc rút ra | Mẫu ADR thêm mục tuỳ chọn **Bài học thực tế** | [ADR README](../19-adr/README.md) |
| L8 | Tài liệu tham chiếu đếm số liệu từ source, ghi mâu thuẫn theo code | `system-map` có lệnh đếm lại; chỗ tài liệu lệch code thì sửa theo code (R24) | [system-map §6](system-map.md) |
| L9 | Tổng đơn gồm nhiều dòng, plugin thêm dòng (giảm giá, điểm) không đổi schema | **Đã có và chặt hơn**: `TotalsCalculator` theo priority, `order_adjustments` truy vết, guard cuối pipeline | [cart-checkout](../03-domains/cart-checkout.md) |
| L10 | Plugin đăng ký tác vụ định kỳ, route API riêng cho headless | **Đã có** `schedule()`; `storefrontRoutes()` còn Designed | [plugin-system §9](../05-plugin/plugin-system.md) |

## 3. Giữ khác biệt có chủ đích (ưu điểm của VaniShop)

| Chủ đề | Hệ tham chiếu | VaniShop giữ | Lý do |
|---|---|---|---|
| Tồn kho | Trừ trên SKU khi thanh toán, không giữ chỗ (hợp lý khi nguồn hàng không giới hạn) | Reservation có TTL, đa location, ATS, ledger append-only | Thời trang có size/màu khan hiếm, sale theo đợt; 0 oversell là NFR ([inventory](../08-inventory/inventory.md)) |
| Trạng thái đơn | Một máy trạng thái, plugin được **sửa cả bảng chuyển** | 4 chiều trạng thái, bảng chuyển **không mở rộng** | Plugin sửa bảng chuyển từng làm rò kho ở hệ tham chiếu (thêm nhánh trừ kho mà thiếu nhánh hoàn) |
| Mở rộng dữ liệu | Plugin thêm cột vào bảng Core và thêm quan hệ động vào model | Bảng `plg_<plugin>_*` + `meta` JSON theo namespace plugin | Gỡ plugin không để lại cột trên bảng Core; migration Core không bị plugin chặn ([ADR-027](../19-adr/ADR-027-plugin-data-no-core-columns.md)) |
| Hook | Chuỗi tự do, không khai báo trước, filter không kiểm kiểu | Registry `hooks.php`, strict mode, public/internal, kiểm kiểu trả về, đo thời gian | Nâng cấp Core không phá plugin âm thầm |
| Lối thoát sửa view | Viết lại HTML của view bất kỳ bằng bộ phân tích DOM lúc render | **Không có**; thay khối bằng override view trong theme của cửa hàng | Viết lại HTML dễ vỡ khi view đổi, khó review ([ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md)) |
| Cài plugin | Upload zip trong Admin (phải chặn path traversal, zip bomb) | Plugin chỉ vào hệ thống qua mã nguồn + CI | Bỏ hẳn một bề mặt tấn công; một Owner, một đội ([ADR-026](../19-adr/ADR-026-plugin-deploy-via-code.md)) |
| Phạm vi | Một cửa hàng | **Nay giống**: một cửa hàng, brand là thuộc tính catalog ([ADR-028](../19-adr/ADR-028-single-store-brand-as-catalog.md), thay ADR-008) | Owner chỉ cần một website, một giao diện |
| Đa ngôn ngữ nội dung | Bảng mô tả theo ngôn ngữ | `*_translations` | Clean-room + đã triển khai |
| Tích hợp | Hook sau commit, gọi ngoài trong listener | Outbox/inbox, retry, dead letter, replay, đối soát | ERP không được làm hỏng checkout (R12) |
| Admin | Blade SSR | Inertia + Vue 3 + TS ([ADR-017](../19-adr/ADR-017-admin-ui-inertia.md)) | Màn hình vận hành nhiều bảng lọc, form sinh từ settings |
| Giỏ khách headless | Header id phiên cố định | Id công khai + token bí mật ([ADR-022](../19-adr/ADR-022-guest-cart-token.md)) | Không đoán được giỏ người khác |

## 4. Không áp dụng

| Khái niệm | Lý do không áp dụng |
|---|---|
| Wizard cài đặt qua web | Một Owner, triển khai bằng CI; cấu hình bằng `.env` + Settings |
| Trình dịch tự thử nhiều biến thể khoá ngôn ngữ | Che lỗi đặt sai khoá; VaniShop dùng namespace dịch rõ ràng cho plugin |
| Plugin nạp hook theo nhánh "admin/không admin" để tiết kiệm | Listener của VaniShop đã gắn plugin id và chỉ chạy khi plugin bật trong scope; chi phí đăng ký nhỏ. Xem lại khi đo được chi phí boot |
| Tích hợp mini-app nước ngoài | Ngoài thị trường; Zalo Mini App đi qua Storefront API |

## 5. Việc tiếp theo

1. Dựng theme `vani-base` theo [ADR-025](../19-adr/ADR-025-native-storefront-ssr-slots.md), khai báo slot storefront trong registry cùng lúc với view ([roadmap §3](../20-roadmap/roadmap.md)).
2. Viết thêm contract cho người viết plugin khi extension point tương ứng có plugin thật đầu tiên (`PromotionRule`, `NotificationChannel`, `Connector`).
3. Giữ [system-map](system-map.md) đúng với code: đếm lại số liệu khi thêm module.
