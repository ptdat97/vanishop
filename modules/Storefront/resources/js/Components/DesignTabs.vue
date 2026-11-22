<script setup lang="ts">
import { t } from '@admin/i18n';
import { Link } from '@inertiajs/vue3';

/** Chuyển giữa các màn hình của Admin → Giao diện. */
defineProps<{ active: 'builder' | 'menus' | 'theme-settings' }>();

const tabs = [
    { key: 'builder', label: t('storefront::admin.design_tabs.builder'), route: 'builder/home' },
    { key: 'menus', label: 'Menu', route: 'menus' },
    { key: 'theme-settings', label: t('storefront::admin.design_tabs.header_footer'), route: 'theme-settings' },
] as const;
const base = window.location.pathname.replace(/\/(builder|menus|theme-settings)(\/.*)?$/, '');
</script>

<template>
    <nav class="mb-4 flex gap-1 border-b border-slate-200 text-sm" :aria-label="t('storefront::admin.design_tabs.storefront_design')">
        <Link
            v-for="tab in tabs"
            :key="tab.key"
            :href="`${base}/${tab.route}`"
            class="-mb-px border-b-2 px-3 py-2"
            :class="active === tab.key ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
