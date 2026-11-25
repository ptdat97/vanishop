import '../css/admin.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';
import AdminLayout from './admin/Layouts/AdminLayout.vue';
import { installBackGuard } from './admin/forms';

type PageModule = { default: DefineComponent & { layout?: unknown } };


const corePages = import.meta.glob<PageModule>('./admin/Pages/**/*.vue');
const modulePages = import.meta.glob<PageModule>('../../modules/*/resources/js/Pages/**/*.vue');
const pluginPages = import.meta.glob<PageModule>('../../custom/plugin/*/Resources/js/Pages/**/*.vue');
const pages = { ...corePages, ...modulePages, ...pluginPages };

/**
 * 'Dashboard' → trang lõi; '<Namespace>::<Trang>' → trang của module (modules/<Namespace>)
 * hoặc plugin (custom/plugin/<Namespace>).
 */
async function resolvePage(name: string): Promise<DefineComponent> {
    const [namespace, page] = name.includes('::') ? name.split('::', 2) : [null, name];
    const candidates =
        namespace === null
            ? [`./admin/Pages/${page}.vue`]
            : [`../../modules/${namespace}/resources/js/Pages/${page}.vue`, `../../custom/plugin/${namespace}/Resources/js/Pages/${page}.vue`];

    for (const path of candidates) {
        const loader = pages[path];
        if (loader) {
            const module = await loader();
            if (module.default.layout === undefined) {
                module.default.layout = AdminLayout;
            }
            return module.default;
        }
    }

    throw new Error(`Không tìm thấy trang Inertia: ${name}`);
}

// Trước createInertiaApp: bộ nghe nút Back phải chạy trước bộ nghe popstate của Inertia (forms.ts).
installBackGuard();

createInertiaApp({
    title: (title) => (title ? `${title} · VaniShop Admin` : 'VaniShop Admin'),
    resolve: resolvePage,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#9c7a3a' },
});
