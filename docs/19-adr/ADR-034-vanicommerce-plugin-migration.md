# ADR-034 — VaniShop thay VaniCommerce; plugin của Owner chuyển sang bằng đặc tả rồi viết lại

- Trạng thái: Accepted · Ngày: 2026-10-14 · Người quyết định: Owner
- Liên quan: [clean-room-license](../01-principles/clean-room-license.md), [ADR-003](ADR-003-plugin-architecture.md), [ADR-029](ADR-029-commerce-microkernel.md), [ADR-033](ADR-033-out-of-scope.md). Kế hoạch: [vanicommerce-plugin-migration](../05-plugin/vanicommerce-plugin-migration.md).

## Context
Owner đang vận hành và phát triển plugin trên **VaniCommerce** (`~/Ecommerce/VaniCommerce`). Lõi VaniCommerce dẫn xuất từ BeikeShop, chịu OSL-3.0 và điều khoản thương mại bổ sung của bên giữ bản quyền ([clean-room-license §1](../01-principles/clean-room-license.md)): không tự do bán, cho thuê, đóng mã hay gỡ thông tin bản quyền của sản phẩm phái sinh. Owner muốn phát triển độc lập, toàn quyền với mã nguồn.

`VaniCommerce/plugins/` có 43 plugin. Owner xác nhận (2026-10-14) **toàn bộ thuộc quyền của Owner**, kể cả các plugin ghi tác giả là công ty giữ bản quyền BeikeShop (Bestseller, ExtraContent, FlatShipping, GdCashOnDeliver, OfflinePay, Paypal, SizeChart, Social, Stripe) và các plugin ghi tác giả khác (AkClear, TwilioVerifyLogin). Các plugin này viết trên API, hook và cấu trúc thư mục của lõi VaniCommerce.

## Problem
Làm sao mang nghiệp vụ mà Owner đã đầu tư trong các plugin sang VaniShop mà không kéo theo mã, tên hay cấu trúc dẫn xuất từ BeikeShop, và vẫn giữ cảm giác quen thuộc cho người đã viết plugin trên VaniCommerce?

## Decision
1. **VaniShop là bản thay thế VaniCommerce**: viết mới hoàn toàn (clean-room), không phải bản nâng cấp hay fork. VaniCommerce ngừng phát triển tính năng mới; tính năng mới làm trên VaniShop.
2. **Gần gũi với người viết plugin VaniCommerce** ở mức khái niệm: plugin vẫn là thư mục tự chứa (`custom/plugin/<Tên>`), có manifest, cấu hình, Admin, storefront, bật/tắt độc lập; hook/filter dùng cú pháp ngắn `vani_*` ([ADR-031](ADR-031-short-hook-syntax-auto-points.md)). Cấu trúc file, tên hook, tên bảng **không** giống VaniCommerce/BeikeShop.
3. **Lõi VaniCommerce**: giữ nguyên quy tắc clean-room như BeikeShop.
4. **Plugin trong `VaniCommerce/plugins/`** (của Owner) được chuyển theo hai bước tách biệt:
   1. **Đặc tả**: đọc plugin cũ, viết đặc tả nghiệp vụ bằng lời (hành vi, dữ liệu, cấu hình, quyền, màn hình, trường hợp lỗi) vào `docs/05-plugin/specs/`. Không chép code, tên hook/bảng/class hay cấu trúc file.
   2. **Viết lại**: code plugin VaniShop **chỉ từ đặc tả**, trên extension point của VaniShop, không mở plugin cũ trong lúc code.
   
   Dữ liệu (danh mục, bảng tra), nội dung và chuỗi hiển thị **do Owner tự viết** được dùng lại. Không mang theo header bản quyền, comment hay chuỗi của bên thứ ba.
5. Chỉ chuyển **tính năng**, không chuyển dữ liệu vận hành (điểm, số dư, đánh giá…) từ DB VaniCommerce.
6. Plugin trùng với thứ VaniShop đã có (Core hoặc plugin) **không** chuyển; chỉ đối chiếu để bổ sung phần còn thiếu vào thiết kế hiện có. Plugin rơi vào [ADR-033](ADR-033-out-of-scope.md) không chuyển.

## Alternatives
- **Nâng cấp VaniCommerce thay vì làm mới**: vẫn là sản phẩm phái sinh BeikeShop. Loại.
- **Copy plugin rồi sửa phần gọi API**: nhanh, nhưng mang theo tên, cấu trúc và cách gọi kiểu BeikeShop vào repo VaniShop, phá lập luận "phát triển độc lập". Loại.
- **Bỏ toàn bộ plugin cũ, thiết kế lại từ đầu**: mất kinh nghiệm nghiệp vụ Owner đã tích luỹ. Loại.

## Consequences
- (+) Mã VaniShop độc lập, Owner toàn quyền; lịch sử git + đặc tả là bằng chứng phát triển độc lập.
- (+) Mỗi plugin chuyển sang là implementation tham chiếu cho extension point của VaniShop (R26); thiếu extension point thì thêm vào Core theo quy trình [commerce-kernel §9](../02-architecture/commerce-kernel.md).
- (−) Chậm hơn copy: mỗi plugin tốn thêm bước đặc tả.
- (−) Người viết đặc tả được đọc plugin cũ; người code thì không — nhóm nhỏ phải tách thời điểm (xong đặc tả mới code).

## Trade-offs
Thêm một bước đặc tả cho mỗi plugin, đổi lấy mã sạch về bản quyền và plugin dựng đúng trên bề mặt mở rộng của VaniShop.
