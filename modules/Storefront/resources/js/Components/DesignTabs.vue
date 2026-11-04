<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/** Chuyển giữa các màn hình của Admin → Giao diện. */
defineProps<{ active: 'home' | 'menus' | 'theme-settings' }>();

const tabs = [
    { key: 'home', label: 'Trang chủ', route: 'home-blocks' },
    { key: 'menus', label: 'Menu', route: 'menus' },
    { key: 'theme-settings', label: 'Đầu & chân trang', route: 'theme-settings' },
] as const;
const base = window.location.pathname.replace(/\/(home-blocks|menus|theme-settings)(\/.*)?$/, '');
</script>

<template>
    <nav class="mb-4 flex gap-1 border-b border-slate-200 text-sm" aria-label="Giao diện">
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
