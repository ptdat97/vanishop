<script setup lang="ts">
import { confirmAction } from '@admin/confirm';
import { t } from '@admin/i18n';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

/** Chuyển giữa các màn hình của Admin → Giao diện; nút "Xoá cache trang" (ADR-051) dùng chung cho các màn hình. */
defineProps<{ active: 'builder' | 'menus' | 'theme-settings' }>();

const tabs = [
    { key: 'builder', label: t('storefront::admin.design_tabs.builder'), route: 'builder/home' },
    { key: 'menus', label: 'Menu', route: 'menus' },
    { key: 'theme-settings', label: t('storefront::admin.design_tabs.header_footer'), route: 'theme-settings' },
] as const;
const base = window.location.pathname.replace(/\/(builder|menus|theme-settings)(\/.*)?$/, '');
const clearing = ref(false);

async function clearPageCache(): Promise<void> {
    const confirmed = await confirmAction({
        title: t('storefront::admin.design_tabs.clear_page_cache_confirm'),
        message: t('storefront::admin.design_tabs.clear_page_cache_message'),
        confirmLabel: t('storefront::admin.design_tabs.clear_page_cache'),
    });
    if (confirmed === false || clearing.value) return;

    clearing.value = true;
    router.post(`${base}/page-cache/clear`, {}, { preserveScroll: true, onFinish: () => (clearing.value = false) });
}
</script>

<template>
    <nav class="mb-4 flex gap-1 border-b border-slate-200 text-sm" :aria-label="t('storefront::admin.design_tabs.storefront_design')">
        <Link
            v-for="tab in tabs"
            :key="tab.key"
            :href="`${base}/${tab.route}`"
            class="-mb-px border-b-2 px-3 py-2"
            :class="active === tab.key ? 'border-slate-900 font-medium text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900'"
        >
            {{ tab.label }}
        </Link>
        <button
            type="button"
            class="ml-auto px-3 py-2 text-slate-600 hover:text-slate-900 disabled:opacity-50"
            :disabled="clearing"
            data-testid="clear-page-cache"
            @click="clearPageCache"
        >
            {{ t('storefront::admin.design_tabs.clear_page_cache') }}
        </button>
    </nav>
</template>
