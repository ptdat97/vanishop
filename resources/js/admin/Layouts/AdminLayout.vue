<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FlashMessage from '../Components/FlashMessage.vue';
import type { SharedProps } from '../types';

const page = usePage<SharedProps>();
const staff = computed(() => page.props.auth.staff);
const navigation = computed(() => page.props.navigation);

function isCurrent(key: string, url: string): boolean {
    const path = new URL(url, window.location.origin).pathname;
    const current = page.url.split('?')[0];
    return current === path || (key !== 'dashboard' && current.startsWith(path + '/'));
}

function logout(): void {
    if (page.props.urls.logout) {
        router.post(page.props.urls.logout);
    }
}
</script>

<template>
    <div class="flex min-h-screen">
        <aside class="w-60 shrink-0 border-r border-slate-200 bg-white">
            <div class="px-5 py-4 text-lg font-semibold">{{ page.props.app.name }}</div>
            <nav class="flex flex-col gap-1 px-3">
                <Link
                    v-for="item in navigation"
                    :key="item.key"
                    :href="item.url"
                    class="rounded-md px-3 py-2 text-sm"
                    :class="isCurrent(item.key, item.url) ? 'bg-indigo-50 font-medium text-red-700' : 'text-slate-600 hover:bg-slate-100'"
                >
                    {{ item.label }}
                </Link>
            </nav>
        </aside>
        <div class="flex flex-1 flex-col">
            <header class="flex items-center justify-end gap-4 border-b border-slate-200 bg-white px-6 py-3 text-sm">
                <span class="text-slate-600">{{ staff?.name }}</span>
                <button type="button" class="text-slate-500 hover:text-slate-900" @click="logout">Đăng xuất</button>
            </header>
            <main class="flex-1 p-6">
                <FlashMessage />
                <slot />
            </main>
        </div>
    </div>
</template>
