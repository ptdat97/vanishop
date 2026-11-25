<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { t } from '@admin/i18n';
import ConfirmDialog from '../Components/ConfirmDialog.vue';
import LocaleSwitcher from '../Components/LocaleSwitcher.vue';
import Toaster from '../Components/Toaster.vue';
import type { NavigationItem, SharedProps } from '../types';

type NavigationEntry = { type: 'link'; item: NavigationItem } | { type: 'group'; key: string; label: string; items: NavigationItem[] };

const OPEN_GROUPS_KEY = 'vani.admin.navigation.open';

const page = usePage<SharedProps>();
const staff = computed(() => page.props.auth.staff);
const mobileNavigationOpen = ref(false);
const isMobileViewport = ref(false);
const mobileNavigationButton = ref<HTMLButtonElement | null>(null);
const navigationAside = ref<HTMLElement | null>(null);
let mobileViewportQuery: MediaQueryList | null = null;

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

async function openMobileNavigation(): Promise<void> {
    mobileNavigationOpen.value = true;
    await nextTick();
    navigationAside.value?.querySelector<HTMLElement>('a, button')?.focus();
}

function closeMobileNavigation(): void {
    if (!mobileNavigationOpen.value) {
        return;
    }

    mobileNavigationOpen.value = false;
    void nextTick(() => mobileNavigationButton.value?.focus());
}

function handleViewportChange(event: MediaQueryListEvent): void {
    isMobileViewport.value = event.matches;
    if (!event.matches) {
        mobileNavigationOpen.value = false;
    }
}

function trapMobileNavigationFocus(event: KeyboardEvent): void {
    if (!mobileNavigationOpen.value || event.key !== 'Tab') {
        return;
    }

    const focusable = navigationAside.value?.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
    const visibleFocusable = [...(focusable ?? [])].filter((element) => element.offsetParent !== null);
    const first = visibleFocusable[0];
    const last = visibleFocusable[visibleFocusable.length - 1];

    if (!first || !last) {
        event.preventDefault();
        return;
    }

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

onMounted(() => {
    mobileViewportQuery = window.matchMedia('(max-width: 767px)');
    isMobileViewport.value = mobileViewportQuery.matches;
    mobileViewportQuery.addEventListener('change', handleViewportChange);
});

onBeforeUnmount(() => {
    mobileViewportQuery?.removeEventListener('change', handleViewportChange);
});

// Nhóm chứa trang đang xem luôn mở khi điều hướng tới.
watch(
    () => page.url,
    () => {
        closeMobileNavigation();
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

    return current === path
        || (key !== 'dashboard' && key !== 'plugins' && current.startsWith(path + '/'));
}

function logout(): void {
    if (page.props.urls.logout) {
        router.post(page.props.urls.logout);
    }
}
</script>

<template>
    <div class="flex min-h-screen">
        <button
            v-if="mobileNavigationOpen"
            type="button"
            class="fixed inset-0 z-40 bg-slate-950/50 md:hidden"
            :aria-label="t('shared::admin.layout.close_navigation')"
            @click="closeMobileNavigation"
        />
        <aside
            id="admin-navigation"
            ref="navigationAside"
            class="fixed inset-y-0 left-0 z-50 h-dvh w-60 shrink-0 overflow-y-auto bg-slate-900 text-slate-300 transition-transform md:sticky md:top-0 md:translate-x-0"
            :class="mobileNavigationOpen ? 'translate-x-0' : '-translate-x-full'"
            :role="isMobileViewport && mobileNavigationOpen ? 'dialog' : undefined"
            :aria-modal="isMobileViewport && mobileNavigationOpen ? 'true' : undefined"
            :aria-hidden="isMobileViewport && !mobileNavigationOpen ? 'true' : undefined"
            :inert="isMobileViewport && !mobileNavigationOpen"
            :aria-label="t('shared::admin.layout.navigation')"
            @keydown.esc.stop.prevent="closeMobileNavigation"
            @keydown.tab="trapMobileNavigationFocus"
        >
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <div class="font-display text-lg tracking-[0.14em] text-white uppercase">{{ page.props.app.name }}</div>
                <button v-if="mobileNavigationOpen" type="button" class="rounded-md px-2 py-1 text-slate-400 hover:bg-white/10 hover:text-white md:hidden" 
                    :aria-label="t('shared::admin.layout.close_menu')" @click="closeMobileNavigation">
                    {{ t('shared::admin.layout.close') }}
                </button>
            </div>
            <nav class="flex flex-col gap-0.5 px-3 py-4">
                <template v-for="entry in entries" :key="entry.type === 'link' ? entry.item.key : `group-${entry.key}`">
                    <Link
                        v-if="entry.type === 'link'"
                        :href="entry.item.url"
                        class="border-l-2 px-3 py-2 text-sm transition-colors"
                        :class="isCurrent(entry.item.key, entry.item.url) ? 'border-indigo-300 bg-white/10 font-medium text-white' : 'border-transparent text-slate-300 hover:bg-white/5 hover:text-white'"
                        :aria-current="isCurrent(entry.item.key, entry.item.url) ? 'page' : undefined"
                        @click="closeMobileNavigation"
                    >
                        {{ entry.item.label }}
                    </Link>
                    <div v-else>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between border-l-2 border-transparent px-3 py-2 text-left text-sm transition-colors"
                            :class="entry.items.some((item) => isCurrent(item.key, item.url)) ? 'font-medium text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'"
                            :aria-expanded="isGroupOpen(entry.key)"
                            :aria-controls="`nav-group-${entry.key}`"
                            @click="toggleGroup(entry.key)"
                        >
                            <span>{{ entry.label }}</span>
                            <svg class="h-4 w-4 text-slate-500 transition-transform" :class="isGroupOpen(entry.key) ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="isGroupOpen(entry.key)" :id="`nav-group-${entry.key}`" class="mt-0.5 mb-1 ml-4 flex flex-col gap-0.5 border-l border-white/10 pl-2">
                            <Link
                                v-for="item in entry.items"
                                :key="item.key"
                                :href="item.url"
                                class="border-l-2 px-3 py-1.5 text-sm transition-colors"
                                :class="isCurrent(item.key, item.url) ? 'border-indigo-300 bg-white/10 font-medium text-white' : 'border-transparent text-slate-400 hover:bg-white/5 hover:text-white'"
                                :aria-current="isCurrent(item.key, item.url) ? 'page' : undefined"
                                @click="closeMobileNavigation"
                            >
                                {{ item.label }}
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>
        </aside>
        <div class="flex flex-1 flex-col">
            <header class="sticky top-0 z-30 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 py-3 text-sm backdrop-blur sm:px-8 md:justify-end">
                <button
                    ref="mobileNavigationButton"
                    type="button"
                    class="rounded-md border border-slate-300 px-3 py-2 text-slate-800 hover:border-slate-900 md:hidden"
                    aria-controls="admin-navigation"
                    :aria-expanded="mobileNavigationOpen"
                    @click="openMobileNavigation"
                >
                    {{ t('shared::admin.layout.open_menu') }}
                </button>
                <div class="flex items-center justify-end gap-4">
                    <LocaleSwitcher />
                    <a href="/" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-slate-900">{{ t('shared::admin.layout.view_store') }}</a>
                    <Link v-if="page.props.urls.profile" :href="page.props.urls.profile" class="text-slate-600 hover:text-slate-900">{{ staff?.name }}</Link>
                    <span v-else class="text-slate-600">{{ staff?.name }}</span>
                    <button type="button" class="text-slate-500 hover:text-slate-900" @click="logout">{{ t('shared::admin.layout.sign_out') }}</button>
                </div>
            </header>
            <main class="mx-auto w-full max-w-[1440px] flex-1 px-4 py-6 sm:px-8 sm:py-8">
                <slot />
            </main>
        </div>
        <Toaster />
        <ConfirmDialog />
    </div>
</template>
