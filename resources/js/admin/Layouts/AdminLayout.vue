<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FlashMessage from '../Components/FlashMessage.vue';
import type { NavigationItem, SharedProps } from '../types';

type NavigationEntry = { type: 'link'; item: NavigationItem } | { type: 'group'; key: string; label: string; items: NavigationItem[] };

const OPEN_GROUPS_KEY = 'vani.admin.navigation.open';

const page = usePage<SharedProps>();
const staff = computed(() => page.props.auth.staff);

/**
 * Sidebar dạng accordion: mỗi nhóm đứng ở vị trí mục đầu tiên của nó (server đã xếp mục theo thứ tự); nhóm chỉ có
 * một mục hiện như link thường.
 */
const entries = computed<NavigationEntry[]>(() => {
    const labels = Object.fromEntries((page.props.navigationGroups ?? []).map((group) => [group.key, group.label]));
    const result: NavigationEntry[] = [];
    const groups: Record<string, Extract<NavigationEntry, { type: 'group' }>> = {};
    for (const item of page.props.navigation) {
        if (item.group === null || labels[item.group] === undefined) {
            result.push({ type: 'link', item });
            continue;
        }
        if (groups[item.group] === undefined) {
            groups[item.group] = { type: 'group', key: item.group, label: labels[item.group], items: [] };
            result.push(groups[item.group]);
        }
        groups[item.group].items.push(item);
    }

    return result.map((entry) => (entry.type === 'group' && entry.items.length === 1 ? { type: 'link', item: entry.items[0] } : entry));
});

function readOpenGroups(): string[] {
    try {
        const stored = JSON.parse(window.localStorage.getItem(OPEN_GROUPS_KEY) ?? '[]');
        return Array.isArray(stored) ? stored.filter((key): key is string => typeof key === 'string') : [];
    } catch {
        return [];
    }
}

const openGroups = ref<string[]>(readOpenGroups());

function isGroupOpen(key: string): boolean {
    return openGroups.value.includes(key);
}

function toggleGroup(key: string): void {
    openGroups.value = isGroupOpen(key) ? openGroups.value.filter((open) => open !== key) : [...openGroups.value, key];
    try {
        window.localStorage.setItem(OPEN_GROUPS_KEY, JSON.stringify(openGroups.value));
    } catch {
        // Không lưu được (chế độ riêng tư) — vẫn mở/đóng trong phiên hiện tại.
    }
}

// Nhóm chứa trang đang xem luôn mở khi điều hướng tới.
watch(
    () => page.url,
    () => {
        for (const entry of entries.value) {
            if (entry.type === 'group' && !isGroupOpen(entry.key) && entry.items.some((item) => isCurrent(item.key, item.url))) {
                openGroups.value = [...openGroups.value, entry.key];
            }
        }
    },
    { immediate: true },
);

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
            <nav class="flex flex-col gap-1 px-3 pb-6">
                <template v-for="entry in entries" :key="entry.type === 'link' ? entry.item.key : `group-${entry.key}`">
                    <Link
                        v-if="entry.type === 'link'"
                        :href="entry.item.url"
                        class="rounded-md px-3 py-2 text-sm"
                        :class="isCurrent(entry.item.key, entry.item.url) ? 'bg-indigo-50 font-medium text-red-700' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        {{ entry.item.label }}
                    </Link>
                    <div v-else>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm"
                            :class="entry.items.some((item) => isCurrent(item.key, item.url)) ? 'font-medium text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
                            :aria-expanded="isGroupOpen(entry.key)"
                            :aria-controls="`nav-group-${entry.key}`"
                            @click="toggleGroup(entry.key)"
                        >
                            <span>{{ entry.label }}</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="isGroupOpen(entry.key) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="isGroupOpen(entry.key)" :id="`nav-group-${entry.key}`" class="mt-1 flex flex-col gap-1 border-l border-slate-200 pl-2 ml-4">
                            <Link
                                v-for="item in entry.items"
                                :key="item.key"
                                :href="item.url"
                                class="rounded-md px-3 py-1.5 text-sm"
                                :class="isCurrent(item.key, item.url) ? 'bg-indigo-50 font-medium text-red-700' : 'text-slate-600 hover:bg-slate-100'"
                            >
                                {{ item.label }}
                            </Link>
                        </div>
                    </div>
                </template>
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
