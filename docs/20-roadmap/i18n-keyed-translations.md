# Dịch bằng khoá có namespace + chọn ngôn ngữ ở Admin — kế hoạch (2026-11)

> Trạng thái: **Accepted** (Owner giao và duyệt [ADR-049](../19-adr/ADR-049-keyed-translations.md) 2026-11-19, Core 0.5.38). Mỗi đợt một commit, tăng version Core; cập nhật cột "Trạng thái" khi xong.
>
> Liên quan: [ADR-038](../19-adr/ADR-038-localized-storefront-urls.md) (URL theo ngôn ngữ), [storefront §3a](../14-storefront/storefront.md) (chuỗi giao diện), [plugin-system §10 bước 11](../05-plugin/plugin-system.md), [reference-parity §6b](reference-parity-refactor.md) L2, L9.

Quyết định của Owner (2026-11-19):

1. Mọi chuỗi dịch theo kiểu `__('catalog::messages.media_folder_exists')`, **không** dùng `__('Huỷ đơn')`.
2. Phạm vi **đầy đủ**: bỏ khoá câu tiếng Việt (I0–I4), chọn ngôn ngữ ở Admin (A1–A4), và chuỗi tiếng Việt viết cứng trong PHP mà người dùng nhìn thấy (I5).
3. **Bỏ ngay** bản dịch JSON (`lang/<locale>.json`, `loadJsonTranslationsFrom`, `addJsonPath`) — không giữ tương thích cho theme/plugin ngoài repo.
4. Vue Admin nhận bản dịch qua **once prop** của Inertia v3.

Ràng buộc "chỉ tiếng Việt nhưng giữ cơ chế i18n" trong [stabilization-2026-11](stabilization-2026-11.md) được thay bằng kế hoạch này: Admin và storefront có đủ bản `en`.

## 1. Hiện trạng (khảo sát 2026-11-19)

| Nơi | Cơ chế | Quy mô |
|---|---|---|
| 16 module Core | `module::file.key`, file `resources/lang/{vi,en}/*.php` | 584 lời gọi — đúng chuẩn |
| `Storefront`, `Customer` | Câu thô `__('Trang chủ')`; bản `en` nằm trong `en.json` của theme | ~11 chỗ (`Breadcrumbs`, `ProductPage`, `LayoutComposer`, `CustomerSessionState`, API `Brand`/`Category`/`ProductController`, `CatalogController`, `AddressRequest`) |
| Theme `vani-base` | Câu thô, `lang/en.json` nạp bằng `addJsonPath` (`UseActiveTheme`) | 380 lời gọi / 48 view; `en.json` 265 dòng; chuỗi JS `data-vani-i18n` |
| 10 plugin | Câu thô, `Resources/lang/en.json` nạp bằng `loadJsonTranslationsFrom` | ~87 lời gọi: Cms, Wishlist, Lookbook, SizeChart, PhoneOtpLogin, StorefrontExtras, ScarcitySignals, TrackingPixels, Preorder, HelloWorld |
| 4 plugin | `translations($path, 'vani-…')` | Đúng chuẩn: BankTransfer, Cod, ShippingFlatRate, Warehouse |
| PHP viết cứng | Chuỗi tiếng Việt không qua `__()` | ~1.800 — Extension 225, Storefront 157, Reports 112, Catalog 108, Ordering 105, Fulfillment 102, … |
| Vue Admin | Viết cứng | ~2.180 dòng có tiếng Việt / 122 file `.vue` (L9) |

Ngôn ngữ Admin hiện có: `staff_users.locale`, chọn ở trang Hồ sơ; `ResolveStaffContext` gọi `App::setLocale()` — chỉ đổi chuỗi từ server.

Vấn đề của khoá = câu tiếng Việt: khoá JSON toàn cục (theme + 10 plugin cùng không gian, câu trùng thì file nạp sau thắng); sửa câu tiếng Việt là đổi khoá, bản `en` mất không báo; Core phụ thuộc file dịch của theme; tiếng Việt không có file để rà hay ghi đè.

## 2. Thiết kế đích

- **Khoá**: `namespace::nhóm.khoá`, snake_case, đặt theo nghĩa (`theme::cart.remove_line`). Mọi namespace có cả `vi/*.php` và `en/*.php`. Số nhiều: `trans_choice('theme::catalog.product_count', $n)`.
- **Core**: `<module>::` như hiện nay.
- **Theme**: namespace dịch `theme::`, file `custom/theme/<theme>/lang/{vi,en}/<nhóm>.php`. Nhóm: `common`, `layout`, `catalog`, `product`, `cart`, `checkout`, `account`, `order`, `js`. `ThemeTranslationLoader` (bọc `Loader` của translator) gộp file của chuỗi theme cha → con (`array_replace_recursive`), con ghi đè từng khoá.
- **Plugin**: `$this->translations($this->pluginPath('Resources/lang'), '<namespace>')`, namespace = id plugin đổi `.` thành `-` (`vani.wishlist` → `vani-wishlist`), như Warehouse.
- **Chuỗi JS storefront**: giữ id trong `data-vani-i18n`, giá trị `__('theme::js.wards_loading')`.
- **Admin**:
  - Dropdown ngôn ngữ ở thanh trên (`AdminLayout`, cạnh tên nhân viên), danh sách `app.locales`. Chọn → `PUT /admin/ngon-ngu` lưu `staff_users.locale` (audit) → tải lại trang. Ô ở trang Hồ sơ giữ, cùng giá trị.
  - Trang khách (đăng nhập, quên mật khẩu, nhận lời mời): dropdown trong `GuestLayout`, lưu session `admin_locale`, middleware trang khách đọc.
  - Chữ của Vue nằm ở nhóm `admin.php` của từng module/plugin (`vi` + `en`). Server gửi bản dịch các nhóm `*::admin` (+ `shared::admin`) của ngôn ngữ đang chọn bằng once prop, khoá theo ngôn ngữ (đổi ngôn ngữ → gửi lại). Tra tài liệu Inertia v3 (`search-docs`) để dùng đúng API khi làm A1.
  - `resources/js/admin/i18n.ts`: `t(key, params)` và `tc(key, count, params)` — cú pháp khoá, `:name`, số nhiều `{0}…|{1}…|[2,*]…` như `__()`/`trans_choice`. Không thêm dependency. Thiếu khoá → trả khoá, `console.warn` ở dev.
- **Không dịch**: mô tả lệnh CLI, log, health-check, comment, chuỗi trong test/migration/seeder demo (người đọc là dev/vận hành).

## 3. Kiểm soát (arch test)

Thay `StorefrontI18nTest` bằng `TranslationKeysTest`:

1. `__()`, `trans()`, `trans_choice()`, `@lang`, `@choice`, `t()`, `tc()` có đối số literal phải khớp `^[a-z0-9-]+::[a-z0-9_]+(\.[a-z0-9_]+)+$` (khoá ghép động: phần tĩnh phải có `namespace::nhóm.`) — trong `modules/`, `custom/`, `app/`, `resources/`.
2. Mọi khoá tĩnh có ở cả `vi` và `en` của namespace sở hữu.
3. `vi` và `en` của mỗi thư mục lang cùng tập khoá, cùng tham số `:name`.
4. Cấm `loadJsonTranslationsFrom`, `addJsonPath`, file `lang/*.json` trong repo.
5. Không còn chữ tiếng Việt ngoài lời gọi dịch: view Blade (theme, plugin, module), `.vue` của Admin, và PHP trừ vùng "không dịch" ở §2.

Luật 1, 5 có **danh sách ngoại lệ chỉ được giảm** (theo file), xoá khi hết đợt cuối.

**Không đổi output**: mỗi đợt storefront render bộ trang mẫu (trang chủ, danh mục, PDP, giỏ, checkout, tài khoản, trang 404/410) bằng `vi` và `en`, trước/sau, so HTML (trang có cache trang/CDN). Khoá thiếu in ra chính khoá → luật 2 bắt khoá tĩnh; khoá động cần feature test.

**Công cụ chuyển**: script một lần ở scratchpad (không vào repo) — đổi `__('Câu')` → `__('theme::<nhóm>.<khoá>')`, nhóm theo thư mục view, khoá gợi ý từ bản `en`, sinh `vi`/`en`; rà tay khoá. Câu trùng trong một nhóm dùng chung khoá trừ khi khác ngữ cảnh.

## 4. Các đợt

| Đợt | Việc | Kiểm chứng | Trạng thái |
|---|---|---|---|
| **I0** | ADR-049; kế hoạch này; ghi chú trong storefront.md, plugin-system.md | Owner duyệt ADR | ✅ 2026-11-19 (ADR-049 Accepted) |
| **I1** | `ThemeTranslationLoader` + namespace `theme`; `TranslationKeysTest` (luật 1–5) kèm danh sách ngoại lệ; bỏ `addJsonPath` trong `UseActiveTheme` sau I3 | Unit test loader: cha/con ghi đè, đổi theme giữa request | ✅ 0.5.39 (2026-11-19): mốc ban đầu luật 1: 469 lời gọi/90 file, luật 5: 3.393 chỗ/391 file, luật 4: 23 file; luật 2–3 phát hiện 25 khoá thiếu `en` (Customer, Promotion, Storefront) — đã bổ sung |
| **I2** | 11 câu thô ở `Storefront`, `Customer` → `storefront::`/`customer::`; gỡ khỏi `en.json` theme | So HTML trước/sau | ✅ 0.5.40 (2026-11-19): HTML 55 trang vi/en không đổi; câu trùng còn dùng trong view theme giữ trong `en.json` tới I3 |
| **I3** | Theme `vani-base`: 380 lời gọi, `data-vani-i18n`; xoá `en.json`, bỏ nạp JSON (2 commit: layouts + partials, pages) | So HTML; luật 2–4 xanh cho theme | ✅ 0.5.41 (2026-11-19, một commit): 252 câu → 9 nhóm `theme::`; `AccountMenuComposer` → `storefront::labels.account_*`; bỏ `addJsonPath`, xoá `en.json`; HTML 55 trang giống hệt trừ sửa lỗi: trang vi hiện "1 product"/"No products" (chuỗi số nhiều JSON rơi về `en`) → "1 sản phẩm" |
| **I4** | 10 plugin JSON → `translations()`; xoá `loadJsonTranslationsFrom`; tăng patch từng plugin; CHANGELOG-extension (Extension API minor — đổi quy ước theme/plugin) | Test của từng plugin; luật 4 không còn ngoại lệ | ✅ 0.5.42 (2026-11-19, Extension API 1.11.0): 62 câu; nhãn `accountPage()` là khoá; bỏ `StorefrontI18nTest`; luật 1 và 4 về 0 |
| **A1** | Dropdown Admin + `PUT /admin/profile/locale` + ngôn ngữ trang khách (session); once prop bản dịch; `i18n.ts` + test (`npm run test:admin`); `AdminLayout`, `GuestLayout` sang khoá | Feature test lưu locale, prop đổi theo locale; test JS `t`/`tc` | ✅ 0.5.43 (2026-11-19): route `PUT /admin/ngon-ngu` (một endpoint cho khách và nhân viên); `UseAdminLocale` đặt ngôn ngữ trước `HandleInertiaRequests` (sửa lỗi props dùng chung luôn theo ngôn ngữ mặc định); chuỗi vỏ Admin ở `shared::admin` |
| **A2** | Chuỗi PHP Admin hiển thị: nhãn menu `AdminNavigation`, quyền, `SettingDefinition`/`FieldDefinition` (Core + plugin), flash message | Luật 5 cho các file này | |
| **A3** | Trang Vue theo module (1–2 module/commit): Ordering, Catalog, Inventory, Customer, Fulfillment, Payment, Returns, Promotion, Pricing, Identity, Extension, Notification, Integration, Storefront, rồi plugin | Danh sách ngoại lệ luật 5 giảm dần | |
| **A4** | Đóng L9; plugin-system: plugin có trang Admin phải có `admin.php` `vi` + `en` | | |
| **I5** | Chuỗi khách nhìn thấy còn viết cứng: lỗi API/storefront, exception trả ra người dùng, nhãn trạng thái, email (mẫu tin mặc định `en` — L2), PDF/phiếu in | Luật 5 cho phần còn lại; L2 đóng | |

Thứ tự: I0 → I1 → I2 → I3 → I4; A1 sau I1; A2–A3 xen kẽ I3–I4; I5 cuối. Xong đợt cuối: xoá danh sách ngoại lệ trong `TranslationKeysTest`.

## 5. Rủi ro

- **Khối lượng**: A3 (~2.180 dòng Vue) và I5 lớn nhất — chia nhỏ theo module, mỗi commit giữ test xanh.
- **Theme/plugin ngoài repo** dùng JSON mất bản `en` ngay (Owner chấp nhận — giai đoạn dev, chưa có plugin bên thứ ba).
- **Kích thước once prop**: chỉ gửi nhóm `admin`, không gửi `labels`/`messages` (trang cần thì nhận qua prop như hiện nay); đo kích thước JSON ở A1.
- **Cache trang/CDN storefront**: chuỗi đổi khoá nhưng output phải y hệt — so HTML mỗi đợt.
