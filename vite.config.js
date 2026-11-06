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
                // Admin: Inter (SIL OFL 1.1) — `@fonts('inter')` trong resources/views/admin.blade.php. Bunny tải về lúc build,
                // phục vụ từ public/build (trình duyệt không gọi ra ngoài). Chỉ preload bản thường; các độ đậm khác tải khi cần.
                bunny('Inter', {
                    alias: 'inter',
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext', 'vietnamese'],
                    preload: [{ weight: 400, style: 'normal' }],
                    optimizedFallbacks: false,
                }),
                // Storefront (theme vani-base, token `font-sans`): `@fonts('anivele')` trong layout theme.
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
