# ADR-049 — Chuỗi dịch bằng khoá có namespace ở mọi nơi; Admin chọn ngôn ngữ, Vue đọc cùng file lang PHP

- Trạng thái: **Accepted** (Owner duyệt 2026-11-19) · Code: **Implemented** (Core 0.5.39–0.5.46, Extension API 1.11.0) · Ngày: 2026-11-19 · Người quyết định: Owner (2026-11-19, 4 câu trong [i18n-keyed-translations](../20-roadmap/i18n-keyed-translations.md))
- Liên quan: [ADR-038](ADR-038-localized-storefront-urls.md), [storefront §3a](../14-storefront/storefront.md), [plugin-system §10 bước 11](../05-plugin/plugin-system.md), [reference-parity §6b](../20-roadmap/reference-parity-refactor.md) L2, L9. Thay quy ước "chuỗi giao diện" của Core 0.3.63.

## Context

- Module Core dịch bằng khoá `module::file.key` (file `resources/lang/{vi,en}/*.php`, 584 lời gọi).
- Từ 0.3.63, view theme và plugin storefront dùng **câu tiếng Việt làm khoá**: `__('Huỷ đơn')`, bản dịch ở `lang/<locale>.json` (theme nạp bằng `addJsonPath` trong `UseActiveTheme`, plugin bằng `loadJsonTranslationsFrom`). Tiếng Việt không cần file. Quy mô: theme 380 lời gọi, 10 plugin ~87, module `Storefront`/`Customer` ~11 (bản `en` của chúng nằm trong file của theme).
- ~1.800 chuỗi tiếng Việt viết cứng trong PHP không qua `__()`; ~2.180 dòng tiếng Việt viết cứng trong 122 trang Vue Admin. Ngôn ngữ Admin riêng (`staff_users.locale`, 0.3.73) chỉ đổi chuỗi từ server (L9).

## Problem

1. Khoá JSON là **toàn cục**: theme và mọi plugin chung một không gian; cùng câu dịch khác nhau → file nạp sau thắng.
2. Sửa câu tiếng Việt **là đổi khoá**: bản dịch mất không báo (arch test chỉ kiểm view Blade).
3. Core phụ thuộc file dịch của theme — đổi theme thì chuỗi của Core mất tiếng Anh.
4. Hai quy ước song song; plugin mới không biết theo cái nào (4 plugin dùng khoá, 10 plugin dùng câu).
5. Admin chọn được ngôn ngữ nhưng giao diện Vue không đổi.

## Decision

1. **Mọi chuỗi hiển thị dùng khoá `namespace::nhóm.khoá`** (snake_case, đặt theo nghĩa), có cả file `vi/*.php` và `en/*.php`. Không dùng câu làm khoá. Áp dụng cho module Core, theme, plugin, Vue Admin.
2. **Theme** có namespace dịch `theme::` (`custom/theme/<theme>/lang/{vi,en}/<nhóm>.php`). `ThemeTranslationLoader` gộp theo chuỗi theme cha → con, theme con ghi đè từng khoá.
3. **Plugin** đăng ký bằng `PluginServiceProvider::translations($path, $namespace)`, namespace = id plugin đổi `.` → `-`.
4. **Bỏ bản dịch JSON ngay**: xoá `addJsonPath` trong `UseActiveTheme`, `loadJsonTranslationsFrom` trong plugin, mọi `lang/*.json`. Không giữ tương thích cho theme/plugin ngoài repo (giai đoạn dev — [ADR-048 sửa đổi 2026-11-18](ADR-048-extension-api-version.md)); tăng minor Extension API, ghi CHANGELOG-extension.
5. **Admin chọn ngôn ngữ** bằng dropdown ở thanh trên (lưu `staff_users.locale`, `PUT /admin/profile/locale`) và ở trang khách (session `admin_locale`). Chữ của Vue ở nhóm `admin.php` của từng module/plugin; server gửi các nhóm `*::admin` của ngôn ngữ đang chọn bằng **once prop** của Inertia v3; `resources/js/admin/i18n.ts` có `t()`/`tc()` cùng cú pháp `__()`/`trans_choice`. Không thêm dependency.
6. **Chuỗi PHP viết cứng** mà người dùng (khách, nhân viên) nhìn thấy chuyển sang khoá. Không dịch: mô tả lệnh CLI, log, health-check, test, migration, seeder demo.
7. Arch test `TranslationKeysTest` (thay `StorefrontI18nTest`): đối số literal của hàm dịch phải là khoá có namespace; khoá tĩnh có ở `vi` và `en`; `vi`/`en` cùng tập khoá và tham số; cấm JSON; không còn tiếng Việt ngoài hàm dịch (view, `.vue`, PHP ngoài vùng không dịch). Danh sách ngoại lệ chỉ được giảm trong lúc chuyển.

## Alternatives

- **Giữ câu tiếng Việt làm khoá, chỉ thêm kiểm tra** — không giải quyết khoá toàn cục và đổi câu = đổi khoá.
- **`vue-i18n` cho Admin** — thêm dependency, hai nguồn bản dịch (PHP + JS). Bị loại: Vue đọc chính file lang PHP qua prop là đủ.
- **Endpoint JSON bản dịch có hash** — trình duyệt cache lâu hơn nhưng thêm request và route; once prop đơn giản hơn.
- **Giữ JSON một phiên bản (deprecated)** — Owner chọn bỏ ngay.

## Consequences

- Một quy ước cho mọi nơi; test bắt khoá thiếu, khoá thừa, lệch tham số.
- Tiếng Việt có file → rà được, theme con ghi đè được.
- Admin đổi được toàn bộ giao diện sang `en` khi xong đợt A3.
- Khối lượng lớn (theme 380 + plugin 87 + PHP ~1.800 + Vue ~2.180 chỗ) — chia đợt, danh sách ngoại lệ giảm dần.
- Theme/plugin ngoài repo đang dùng JSON mất bản `en` cho đến khi chuyển.

## Trade-offs

- Đọc view khó hơn một chút (khoá thay câu) — bù bằng khoá đặt theo nghĩa và nhóm theo màn hình.
- Once prop tăng kích thước phản hồi Inertia đầu tiên — chỉ gửi nhóm `admin`; đo ở đợt A1.
