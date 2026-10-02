# ADR-031 — Cú pháp hook ngắn và điểm mở rộng tự động, giữ kiểm soát của HookManager

- Trạng thái: Accepted · Ngày: 2026-10-02 · Người quyết định: Owner
- Mở rộng: [ADR-004](ADR-004-extension-points.md), [ADR-030](ADR-030-extension-surface-v2.md). Nghiên cứu: BeikeShop v3.0.0.11 (chỉ khái niệm, [clean-room](../01-principles/clean-room-license.md)).

## Context
Hệ tham chiếu dùng helper toàn cục mỏng bọc thư viện hook (gọi/đăng ký filter, action) — ngắn, gọi ở bất cứ đâu, và Core rải rất nhiều điểm (mọi màn hình quản trị, API). Đổi lại: hook không khai báo (gõ sai tên thì im lặng), phải khai số tham số, không biết listener của plugin nào, không kiểm kiểu, lỗi listener làm hỏng trang. VaniShop đã có HookManager (khai báo, kiểm kiểu, gắn plugin, bật/tắt tức thời, quy tắc lỗi, đo thời gian) nhưng cú pháp dài hơn và chỉ có điểm Core khai báo từng cái.

## Decision
1. **Helper ngắn** `vani_filter()`, `vani_action()`, `vani_add_filter()`, `vani_add_action()` (`modules/Extension/helpers.php`, autoload Composer). Đi qua HookManager nên giữ mọi bảo đảm; không cần số tham số. `vani_add_*` suy ra **plugin sở hữu từ vị trí file gọi** (thư mục plugin) → listener chỉ chạy khi plugin bật. Tiền tố `vani_` (không dùng tên helper của hệ tham chiếu — clean-room).
2. **Khai báo theo mẫu** `<tiền tố>.*` trong `hooks.php`: một khai báo bao cả họ hook; vẫn bắt buộc khai báo (gõ sai tiền tố → `HookNotDeclared`).
3. **Điểm mở rộng tự động** (`stability: experimental`, `on_error: skip`, chỉ chạy khi có listener):
   - `vani.admin.page.<component>` — props của **mọi** trang Admin (Inertia).
   - `vani.storefront.view.<view>` — dữ liệu **mọi** view của theme storefront.
   - `vani.api.storefront.<route>` — body **mọi** phản hồi JSON thành công của Storefront API (gồm route plugin).
4. Hai mức ổn định: `stable` (public API theo compatibility policy, snapshot) và `experimental` (điểm tự động gắn với tên component/route/view — có thể đổi ở bản minor, ghi CHANGELOG). Plugin nên ưu tiên điểm `stable`/contract (StorefrontEnricher, registry Admin) khi có.
5. Không mở rộng tới invariant: điểm tự động nằm ở luồng đọc/hiển thị; checkout, giữ hàng, máy trạng thái vẫn chỉ qua hook/contract khai báo riêng.

## Alternatives
- **Helper toàn cục không qua HookManager**: mất khai báo, kiểm kiểu, sở hữu plugin. Loại.
- **Khai báo từng hook cho từng màn hình**: an toàn nhất nhưng chậm mở rộng (mỗi điểm một PR Core). Thay bằng mẫu + điểm tự động.

## Consequences
- (+) Gần như mọi màn hình Admin, view storefront, endpoint API đều mở cho plugin mà không sửa Core.
- (+) Viết plugin ngắn như hệ tham chiếu, vẫn có bảo đảm của VaniShop.
- (−) Điểm `experimental` gắn với tên component/route: đổi tên màn hình có thể làm plugin mất tác dụng (không lỗi). Giảm thiểu: `vani:plugin:hooks` liệt kê, CHANGELOG ghi đổi tên.
- (−) Suy ra sở hữu bằng backtrace: chỉ đúng khi gọi từ file trong thư mục plugin (đăng ký trong `boot()` của plugin).
