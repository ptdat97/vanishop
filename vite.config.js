import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
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
