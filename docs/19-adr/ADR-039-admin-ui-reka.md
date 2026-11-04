# ADR-039 — Bộ component Admin trên Reka UI (headless) + Tailwind

- Trạng thái: Accepted · Ngày: 2026-11-04 · Người quyết định: Owner
- Bổ sung: [ADR-017](ADR-017-admin-ui-inertia.md) (Inertia + Vue 3 + TypeScript + Tailwind 4). Liên quan: R25 (dependency phải được duyệt), R23 ([clean-room](../01-principles/clean-room-license.md)), [ADR-030](ADR-030-extension-surface-v2.md) (mở rộng màn hình Admin bằng dữ liệu).

## Context
Admin có 59 trang (module + plugin) nhưng chưa có bộ component dùng chung, chỉ có chuỗi class trong `@admin/styles` và `FormField`. Đếm ngày 2026-11-04: 37 trang tự viết `<table>`, 22 trang dùng `confirm()`/`prompt()` của trình duyệt, 19 trang ghi cứng nhãn trạng thái, 10 trang tự định dạng tiền bằng `vi-VN`/`₫` (arch test chặn ghi cứng thị trường mới quét `.php`), phân trang chỉ có trước/sau, không có dialog, combobox, chọn ngày; `aria-*` có ở 3 trang. Trang plugin cũng import `@admin/*` nên các component này đã là API của plugin trên thực tế.

## Problem
Chọn nền tảng component cho Admin: đủ widget phức tạp (dialog, select, combobox, phân trang, toast, chọn ngày), truy cập được bằng bàn phím, không phá hệ style Tailwind và không khoá trang plugin vào một thư viện.

## Decision
1. **Reka UI** (`reka-ui`, headless, MIT) làm nền cho widget có hành vi phức tạp; style bằng Tailwind theo token hiện có (indigo/slate). Dependency được duyệt (R25): `reka-ui` và `@internationalized/date` (khai báo trực tiếp khi làm `DateInput`; hiện đi kèm `reka-ui`).
2. **Chỉ `resources/js/admin/Components` được import `reka-ui`.** Trang của module và plugin dùng bộ component `@admin/Components` — đây là API UI công khai cho plugin ([CHANGELOG-extension](../04-extension/CHANGELOG-extension.md)). Kiểm tra: `tests/Architecture/AdminUiTest.php`.
3. Bộ component đợt đầu (thử trên trang Đơn hàng): `DataTable` (cột, slot `cell-<key>`, cột + chọn dòng của extension), `Pagination` (số trang), `Select`, `Combobox`, `StatusBadge`, `MoneyText` + `useMoney()`, `ConfirmDialog` + `confirmAction()` (thay `confirm()`/`prompt()`), `Toaster` (thông báo thành công, gắn trong `AdminLayout`). `DateInput` làm khi có trang cần.
4. **Không ghi cứng thị trường ở client.** Nhãn trạng thái do server dịch (lang của module) và gửi trong props; tiền định dạng theo `app.money` (shared props, `MoneyFormatter::rules()`), cùng thuật toán với server. Arch test chặn `vi-VN`/`₫` mới trong `.vue`/`.ts`; trang chưa chuyển nằm trong danh sách chờ của test và giảm dần.
5. Lọc, sắp xếp, phân trang vẫn ở server (Inertia); `DataTable` không dùng thư viện bảng (TanStack Table) ở đợt này.

## Alternatives
- **Element Plus**: đủ component, nhân viên quen từ VaniCommerce; nhưng có hệ style riêng không nằm trong `@layer` (đè utility Tailwind 4), bundle thêm khoảng +195 KB gzip (đo 12 component, nạp theo nhu cầu), kéo theo `lodash`, `dayjs`, `async-validator`. Loại.
- **PrimeVue 5** (theme hoặc unstyled + Tailwind): nhiều component nhất (DataTable, CascadeSelect, TreeTable) nhưng nặng nhất (+214 KB gzip cùng bộ đo) và vừa lên major. Giữ làm phương án dự phòng nếu cần bảng phức tạp phía client.
- **Nuxt UI 4**: xây trên Reka + Tailwind nhưng kéo plugin Vite, tự import và nhiều peer dependency. Loại.
- **Tự viết toàn bộ không thư viện**: tốn công cho focus trap, điều khiển bàn phím, vị trí popover. Loại.
- **Element UI**: chỉ chạy Vue 2, đã ngừng phát triển. Loại.

## Consequences
- (+) Một hệ style; widget truy cập được bằng bàn phím; trang plugin độc lập với thư viện bên dưới.
- (+) Bỏ dần `confirm()`/`prompt()`, nhãn trạng thái và định dạng tiền ghi cứng ở từng trang.
- (−) Tự dựng và duy trì phần giao diện của component; Reka không có bảng dữ liệu, upload, cascader (dùng `MediaManager` sẵn có, hai `Combobox` nối nhau).
- (−) JS của Admin tăng: tổng toàn bộ build 194,7 → 257,3 KB gzip sau đợt đầu (đo 2026-11-04).
- (−) `FlashMessage` không còn hiện thông báo thành công (chuyển sang `Toaster`); trang plugin tự đặt `FlashMessage` chỉ còn hiện lỗi chung.

## Trade-offs
Chi phí tự dựng phần giao diện của component để giữ một hệ style, bundle nhỏ và ranh giới API UI cho plugin.
