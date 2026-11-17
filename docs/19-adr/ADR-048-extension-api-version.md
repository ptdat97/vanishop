# ADR-048 — Version API cho plugin tách khỏi version Core; đánh dấu API cho plugin và mức ổn định

- Trạng thái: **Accepted** · Code: **Implemented** (Core 0.5.1, Extension API 1.0.0, 2026-11-17) · Người quyết định: Owner (2026-11-17, [architecture-consolidation §5](../20-roadmap/architecture-consolidation.md) câu 1–3)
- Liên quan: [extension-model §5](../04-extension/extension-model.md) (compatibility policy), [plugin-system §3, §6](../05-plugin/plugin-system.md), [architecture-rules R5](../01-principles/architecture-rules.md), [CHANGELOG-extension](../04-extension/CHANGELOG-extension.md).

## Context

- Version Core (`vanishop.version`) tăng theo **mỗi** thay đổi: 0.4.59 → 0.4.69 trong hai ngày. Plugin chỉ có một cách khai tương thích là `requires.vanishop`, và mọi plugin trong repo khai `^0.4`. Ràng buộc này không bảo vệ được plugin: 0.4.x từng có thay đổi phá vỡ ở bản vá.
- `Contracts/` và `Events/` phục vụ hai nhóm người dùng: module khác của Core và plugin. `public-api.snapshot` có 291 class, trong khi plugin trong repo dùng khoảng 130. Một thay đổi chỉ module dùng vẫn bị tính là "đổi API plugin".
- Điểm mở rộng mới (EP v3 X1–X7) vào thẳng mức ổn định: muốn sửa sau khi có plugin thật dùng thì phải coi là phá vỡ.

## Decision

1. **`vanishop.extension_api`** (semver, bắt đầu `1.0.0`) là version của **API cho plugin**, tách khỏi version Core. Chỉ tăng khi bề mặt trong `tests/Architecture/plugin-api.snapshot` đổi.
2. **Manifest khai `requires.extension_api`** (vd. `^1.0`). `requires.vanishop` thành **tuỳ chọn**, chỉ dùng khi plugin cần một bản sửa lỗi cụ thể của Core. Không thoả `requires.extension_api` → `incompatible_extension_api`: không cài/bật được; plugin đang bật thì không được nạp sau deploy và chuyển `failed` (giống `incompatible_core`). Plugin trong repo **phải** khai theo major hiện tại và không khai `requires.vanishop` (test P7).
3. **Thuộc tính `#[Modules\Shared\Contracts\PluginApi]`** đánh dấu class/interface/enum thuộc API cho plugin. Plugin chỉ được dùng class đánh dấu (cùng `PluginServiceProvider`, class trong `PLUGIN_PUBLIC_SUPPORT`, `Shared\Domain`, `Testing`) — R5 kiểm bằng arch test. `Contracts`/`Events` không đánh dấu là **API giữa module**, đổi theo version Core.
4. **Mức ổn định**: `#[PluginApi]` = `stable`; `#[PluginApi(PluginApi::EXPERIMENTAL)]` = `experimental` (hook dùng khoá `stability` sẵn có). Điểm mở rộng mới vào ở `experimental`, lên `stable` sau một vòng phát hành có plugin thật dùng. EP v3 X1–X7 bắt đầu ở `experimental`.
5. **Xếp loại tự động** khi ghi snapshot mới (`VANI_UPDATE_API_SNAPSHOT=1`): có dòng của khối `stable` bị xoá/đổi → bắt tăng **major**; còn lại (thêm, đổi phần `experimental`, `experimental` → `stable`) → tăng **minor**. CHANGELOG-extension phải có ghi chú `Extension API x.y.z`.

Bề mặt trong `plugin-api.snapshot`: class `#[PluginApi]`, `PluginServiceProvider` (public + protected), class hỗ trợ `PLUGIN_PUBLIC_SUPPORT`, helper `vani_*`, hook `public` (tên, loại, tham số, mức ổn định).

## Consequences

- Core đổi API giữa module (vd. chuyển contract, thêm method service contract chỉ module dùng) không còn ép nâng plugin.
- Đánh dấu ban đầu (195 class) tính tự động: class plugin trong repo dùng ∪ interface có `TAG` ∪ kiểu kéo theo trong chữ ký/docblock. Muốn mở thêm class cho plugin → gắn `#[PluginApi]` (tăng minor).
- `public-api.snapshot` vẫn giữ toàn bộ `Contracts`/`Events` theo version Core — để thấy thay đổi API giữa module khi review.
- Chưa làm (K3): log `vani.deprecation` khi plugin dùng điểm `@deprecated`, contract test cho các tag còn thiếu.
