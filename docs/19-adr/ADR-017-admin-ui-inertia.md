# ADR-017 — Admin UI: Inertia + Vue 3 + TypeScript

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner
- Bổ sung: [ADR-039](ADR-039-admin-ui-reka.md) (bộ component Admin trên Reka UI).

## Context
Admin có nhiều CRUD, bảng lọc phức tạp, form động từ settings schema của plugin, màn hình vận hành.

## Problem
Chọn công nghệ UI Admin.

## Decision
Inertia (bản cài đặt: **v3**) + Vue 3 + TypeScript + Tailwind 4. Routing/controller/Form Request/Policy vẫn là Laravel. Trang của module ở `modules/<M>/resources/js/Pages`, của plugin ở `custom/plugin/<P>/Resources/js/Pages`, resolver `'<Module>::<Trang>'`. Storefront không dùng Inertia. Dependency được phê duyệt: `inertiajs/inertia-laravel`, `@inertiajs/vue3`, `vue`, `@vitejs/plugin-vue`, `typescript`, `vue-tsc`.

Ghi chú triển khai (2026-09-28): TypeScript được ghim `^5.9` vì `vue-tsc` chưa hỗ trợ TypeScript 7. Trang lõi của Admin nằm ở `resources/js/admin/Pages`, layout ở `resources/js/admin/Layouts` (alias `@admin`).

Ghi chú giao diện (2026-11-25, 0.5.80): Admin dùng phong cách **Flat Fashion Luxury**, đồng bộ storefront `vani-base`. Token ở `resources/css/admin.css` đổi nghĩa class Tailwind thường: `slate` → trung tính ấm (nền ngà, chữ mực `#1f1f1d`), `indigo` → màu nhấn đồng, `emerald`/`amber`/`red` → xanh rêu, vàng đất, đỏ rượu; bo góc 2–3px; không đổ bóng, trừ lớp nổi (hộp thoại, toast). Tiêu đề dùng font thương hiệu Anivele (`font-display`), nội dung dùng Inter. Nút và ô nhập lấy từ `@admin/styles`: nút chính màu mực, nút phụ viền mảnh. Trang của module và plugin viết class `slate`/`indigo` như cũ và tự nhận phong cách mới; trạng thái đang chọn của tab và bộ lọc dùng `border-slate-900 text-slate-900`. Chữ trên nền trắng từ `slate-500` và `indigo-600` trở lên đạt tương phản AA.

## Alternatives
Livewire, Filament, Inertia + React.

## Consequences
- (+) Trải nghiệm SPA; phân quyền vẫn ở server.
- (−) Cần năng lực Vue/TS; hai cách render (Inertia/Blade).

## Trade-offs
Thêm stack JS cho Admin để đổi lấy trải nghiệm vận hành tốt.
