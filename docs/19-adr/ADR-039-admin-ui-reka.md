# ADR-039 — Bộ component Admin trên Reka UI (headless) + Tailwind

- Trạng thái: Accepted · Ngày: 2026-11-04 · Người quyết định: Owner
- Bổ sung: [ADR-017](ADR-017-admin-ui-inertia.md) (Inertia + Vue 3 + TypeScript + Tailwind 4). Liên quan: R25 (dependency phải được duyệt), R23 ([clean-room](../01-principles/clean-room-license.md)), [ADR-030](ADR-030-extension-surface-v2.md) (mở rộng màn hình Admin bằng dữ liệu).

## Context
Admin có 59 trang (module + plugin) nhưng chưa có bộ component dùng chung, chỉ có chuỗi class trong `@admin/styles` và `FormField`. Đếm ngày 2026-11-04: 37 trang tự viết `<table>`, 22 trang dùng `confirm()`/`prompt()` của trình duyệt, 19 trang ghi cứng nhãn trạng thái, 10 trang tự định dạng tiền bằng `vi-VN`/`₫` (arch test chặn ghi cứng thị trường mới quét `.php`), phân trang chỉ có trước/sau, không có dialog, combobox, chọn ngày; `aria-*` có ở 3 trang. Trang plugin cũng import `@admin/*` nên các component này đã là API của plugin trên thực tế.

## Problem
Chọn nền tảng component cho Admin: đủ widget phức tạp (dialog, select, combobox, phân trang, toast, chọn ngày), truy cập được bằng bàn phím, không phá hệ style Tailwind và không khoá trang plugin vào một thư viện.

## Decision
1. **Reka UI** (`reka-ui`, headless, MIT) làm nền cho widget có hành vi phức tạp; style bằng Tailwind theo token hiện có (indigo/slate). Dependency được duyệt (R25): `reka-ui`, `@internationalized/date` (cho `DateInput`).
2. **Chỉ `resources/js/admin/Components` được import `reka-ui`.** Trang của module và plugin dùng bộ component `@admin/Components` — đây là API UI công khai cho plugin ([CHANGELOG-extension](../04-extension/CHANGELOG-extension.md)). Kiểm tra: `tests/Architecture/AdminUiTest.php`.
3. Bộ component: `DataTable` (cột, slot `head-<key>`/`cell-<key>`/`expanded`/`footer`, cột + chọn dòng của extension, `rowKey`, `bare`), `Pagination` (số trang), `Select` (chuỗi/số/boolean, lựa chọn rỗng), `MultiSelect`, `Combobox`, `DateInput` (ngày hoặc ngày giờ, cùng định dạng `datetime-local`), `StatusBadge`, `MoneyText`, `ConfirmDialog` + `confirmAction()`/`promptFields()` (thay `confirm()`/`prompt()`), `Toaster` + `toast(message, tone)` của `@admin/toast` (mọi thông báo — thành công, lỗi chung trong `GENERAL_ERROR_KEYS` như `errors.business`, cảnh báo — gắn trong `AdminLayout`; từ 0.4.32 trang không tự vẽ khung lỗi chung/flash, arch test chặn), `FullscreenDialog` (popup toàn màn hình, 0.4.31). Helper: `@admin/money` (`useMoney`), `@admin/datetime` (`useDateTime`), `@admin/options`.
4. **Không ghi cứng thị trường ở client.** Nhãn trạng thái của module Core do server dịch: mỗi module có lang `labels.php` (`<module>::labels`), controller gửi prop `labels`. Tiền định dạng theo `app.money` (`MoneyFormatter::rules()`), ngày giờ theo `app.clock` (múi giờ + mẫu của `StoreClock`) — cùng kết quả với server. Plugin giữ nhãn riêng trong plugin.
5. Lọc, sắp xếp, phân trang vẫn ở server (Inertia); `DataTable` không dùng thư viện bảng (TanStack Table) ở đợt này.

## Alternatives
- **Element Plus**: đủ component, nhân viên quen từ VaniCommerce; nhưng có hệ style riêng không nằm trong `@layer` (đè utility Tailwind 4), bundle thêm khoảng +195 KB gzip (đo 12 component, nạp theo nhu cầu), kéo theo `lodash`, `dayjs`, `async-validator`. Loại.
- **PrimeVue 5** (theme hoặc unstyled + Tailwind): nhiều component nhất (DataTable, CascadeSelect, TreeTable) nhưng nặng nhất (+214 KB gzip cùng bộ đo) và vừa lên major. Giữ làm phương án dự phòng nếu cần bảng phức tạp phía client.
- **Nuxt UI 4**: xây trên Reka + Tailwind nhưng kéo plugin Vite, tự import và nhiều peer dependency. Loại.
- **Tự viết toàn bộ không thư viện**: tốn công cho focus trap, điều khiển bàn phím, vị trí popover. Loại.
- **Element UI**: chỉ chạy Vue 2, đã ngừng phát triển. Loại.

### Hoàn thành (Core 0.3.56)
Mọi trang Admin của module và plugin đã dùng bộ component. `tests/Architecture/AdminUiTest.php` khoá: chỉ `@admin/Components` import `reka-ui`; không `vi-VN`/`₫`/`Intl.*`/`toLocale*()` ngoài helper; không `<select>`, `<table>`, `type="date|datetime-local"`, `confirm()`/`prompt()`/`alert()` trong trang và component của module/plugin; module Core không có bảng nhãn viết cứng.

### Bổ sung 2026-11-11 — Drag & Drop Engine (`Sortable`)
- Dependency được duyệt (R25, Owner chọn): **`vuedraggable` 4.1** (MIT, kéo theo `sortablejs` 1.14, MIT) — kéo-thả bằng chuột/cảm ứng, cuộn khi kéo sát mép, kéo giữa các danh sách lồng nhau.
- `@admin/Components/Sortable.vue` + `SortHandle.vue` là cơ chế sắp xếp duy nhất của Admin: danh sách dọc, lưới, cây nhiều cấp (`children-key`, `max-depth`). Thư viện không có phần nào thì component bổ sung: sắp xếp bằng bàn phím trên tay nắm (↑ ↓, lưới ← →, cây → thụt vào / ← thụt ra) kèm thông báo `aria-live`, và chặn thả vượt số cấp tối đa — phép toán thuần ở `resources/js/admin/dnd/tree.ts` (`npm run test:dnd`).
- Như `reka-ui`: **chỉ `Sortable.vue` được import `vuedraggable`/`sortablejs`**; trang module/plugin không tự viết `draggable=`, `@dragstart` hay nút Lên/Xuống. Kiểm tra: `tests/Architecture/AdminUiTest.php`.
- Đã chuyển: page builder trang chủ, menu (cây), bộ lọc danh sách, video sản phẩm, `MediaField` nhiều ảnh, sản phẩm trong bài viết (`vani.cms`), ảnh lookbook (`vani.lookbook`), khối bảng size (`vani.size-chart`).

## Consequences
- (+) Một hệ style; widget truy cập được bằng bàn phím; trang plugin độc lập với thư viện bên dưới.
- (+) Bỏ dần `confirm()`/`prompt()`, nhãn trạng thái và định dạng tiền ghi cứng ở từng trang.
- (−) Tự dựng và duy trì phần giao diện của component; Reka không có bảng dữ liệu, upload, cascader (dùng `MediaManager` sẵn có, hai `Combobox` nối nhau).
- (−) JS tăng: tổng toàn bộ build 194,7 → 289,5 KB gzip khi hoàn thành (đo 2026-11-04); `DateInput` (26,6 KB gzip) chỉ tải ở trang có ô ngày.
- (−) `FlashMessage` đã gỡ (0.4.32): thông báo thành công và lỗi chung đều hiện bằng `Toaster`.

## Trade-offs
Chi phí tự dựng phần giao diện của component để giữ một hệ style, bundle nhỏ và ranh giới API UI cho plugin.
