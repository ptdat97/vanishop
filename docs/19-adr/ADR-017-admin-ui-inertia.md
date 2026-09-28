# ADR-017 — Admin UI: Inertia + Vue 3 + TypeScript

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner

## Context
Admin có nhiều CRUD, bảng lọc phức tạp, form động từ settings schema của plugin, màn hình vận hành.

## Problem
Chọn công nghệ UI Admin.

## Decision
Inertia.js 2 + Vue 3 + TypeScript + Tailwind 4. Routing/controller/Form Request/Policy vẫn là Laravel. Trang của module ở `modules/<M>/resources/js/Pages`, của plugin ở `custom/plugin/<P>/Resources/js/Pages`, resolver `'<Module>::<Trang>'`. Storefront không dùng Inertia. Dependency được phê duyệt: `inertiajs/inertia-laravel`, `@inertiajs/vue3`, `vue`, `@vitejs/plugin-vue`, `typescript`, `vue-tsc`.

## Alternatives
Livewire, Filament, Inertia + React.

## Consequences
- (+) Trải nghiệm SPA; phân quyền vẫn ở server.
- (−) Cần năng lực Vue/TS; hai cách render (Inertia/Blade).

## Trade-offs
Thêm stack JS cho Admin để đổi lấy trải nghiệm vận hành tốt.
