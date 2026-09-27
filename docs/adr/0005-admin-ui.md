# 0005 — Giao diện Admin bằng Inertia

- Trạng thái: Accepted
- Ngày: 2026-09-28
- Người quyết định: Owner

## Bối cảnh
Admin có nhiều màn hình CRUD, bảng lọc phức tạp, form động (settings schema của plugin), màn hình vận hành (đơn, tích hợp, tồn kho) cần trải nghiệm mượt như SPA.

## Quyết định
- Admin dùng **Inertia.js 2** với **Vue 3 + TypeScript** và Tailwind CSS 4, build bằng Vite.
- Routing, controller, Form Request, Policy vẫn là Laravel; không cần viết Admin API riêng cho màn hình.
- Trang của module đặt tại `modules/<M>/resources/js/Pages/`, plugin tại `custom/plugin/<P>/resources/js/Pages/`; resolver Inertia ánh xạ `'<Module>::<Trang>'` qua `import.meta.glob`.
- Storefront **không** dùng Inertia: Blade SSR + Alpine.js để tối ưu SEO và cache CDN.
- Package cần cài (đã được phê duyệt theo quyết định này): `inertiajs/inertia-laravel`, `@inertiajs/vue3`, `vue`, `@vitejs/plugin-vue`, `typescript`, `vue-tsc`.

## Hệ quả
- (+) Trải nghiệm SPA, tái sử dụng hệ component; bảo mật và phân quyền vẫn ở server.
- (−) Đội cần năng lực Vue/TypeScript; hai cách render (Inertia cho Admin, Blade cho storefront).

## Phương án đã cân nhắc
- **Livewire**: cùng stack PHP, nhưng Owner chọn Inertia.
- **Filament**: nhanh cho CRUD, khó tuỳ biến sâu luồng vận hành.
- **Inertia + React**: tương đương về năng lực; Owner chốt **Vue 3 + TypeScript**.
