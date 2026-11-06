import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny, local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.ts', 'custom/theme/vani-base/css/app.css', 'custom/theme/vani-base/js/app.js'],
            refresh: ['resources/views/**', 'modules/**/resources/**', 'custom/**'],
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // Storefront (theme vani-base, token `font-sans`) và Admin: `@fonts('anivele')` trong layout theme và resources/views/admin.blade.php.
                local('Anivele', {
                    alias: 'anivele',
                    optimizedFallbacks: false,
                    variants: [
                        { src: ['public/fonts/anivele/fe7027e3d2f79c7174b0-Regular.woff2', 'public/fonts/anivele/fe7027e3d2f79c7174b0-Regular.woff'], weight: 400 },
                        { src: ['public/fonts/anivele/fe7027e3d2f79c7174b0-SemiBold.woff2', 'public/fonts/anivele/fe7027e3d2f79c7174b0-SemiBold.woff'], weight: 600 },
                    ],
                }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@admin': fileURLToPath(new URL('./resources/js/admin', import.meta.url)),
        },
    },
    // Trang Admin nạp lười (import.meta.glob) nên Vite chỉ thấy reka-ui khi mở trang đầu tiên dùng nó, rồi tối ưu lại
    // giữa phiên → trình duyệt nhận 504 "Outdated Optimize Dep". Khai báo trước để tối ưu ngay khi khởi động.
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3', 'reka-ui', '@internationalized/date'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
