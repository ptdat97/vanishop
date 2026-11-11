<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { useDateTime } from '@admin/datetime';
import { dangerButton, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, provide, reactive, ref } from 'vue';
import DesignTabs from '../../Components/DesignTabs.vue';
import MenuItems from '../../Components/MenuItems.vue';
import { type LinkTypeOption, type MenuItem, menuEditorKey, newMenuItem } from '../../menu';

const props = defineProps<{
    baseUrl: string;
    searchUrl: string;
    locations: Array<{ code: string; label: string; depth: number }>;
    location: string | null;
    menu: null | { items: MenuItem[]; published_at: string | null; has_changes: boolean; versions: Array<{ published_at: string | null; count: number }> };
    linkTypes: LinkTypeOption[];
    linkLabels: Record<string, { label: string; exists: boolean }>;
    images: Record<number, string>;
}>();

const formatDate = useDateTime();
const current = computed(() => props.locations.find((item) => item.code === props.location) ?? null);
// Cây menu đệ quy: giữ bằng ref (kiểu của useForm không nhận kiểu đệ quy), gửi bằng router.
// Props của Inertia là Proxy phản ứng — structuredClone không chép được Proxy (DataCloneError); cây menu là JSON thuần.
const cloneItems = (): MenuItem[] => JSON.parse(JSON.stringify(props.menu?.items ?? [])) as MenuItem[];
const items = ref<MenuItem[]>(cloneItems());
const saved = ref(JSON.stringify(items.value));
const dirty = computed(() => JSON.stringify(items.value) !== saved.value);
const processing = ref(false);
const errors = computed(() => usePage().props.errors as Record<string, string | undefined>);
const locationUrl = (action = ''): string => `${props.baseUrl}/${props.location}${action}`;

provide(menuEditorKey, reactive({ linkTypes: props.linkTypes, searchUrl: props.searchUrl, linkLabels: { ...props.linkLabels }, images: { ...props.images } }));

function reload(): void {
    items.value = cloneItems();
    saved.value = JSON.stringify(items.value);
}

function add(): void {
    items.value.push(newMenuItem());
}

function save(onSuccess?: () => void): void {
    router.put(locationUrl(), { items: items.value } as never, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => {
            reload();
            onSuccess?.();
        },
    });
}

async function publish(): Promise<void> {
    if (dirty.value && (await confirmAction({ title: 'Có thay đổi chưa lưu', message: 'Lưu nháp rồi xuất bản?', confirmLabel: 'Lưu và xuất bản' })) === false) {
        return;
    }
    const send = (): void => router.post(locationUrl('/publish'), {}, { preserveScroll: true, onSuccess: reload });
    if (dirty.value) {
        save(send);
    } else {
        send();
    }
}

async function discard(): Promise<void> {
    if ((await confirmAction({ title: 'Bỏ thay đổi chưa xuất bản?', message: 'Nháp quay về bản đang hiển thị trên cửa hàng.', confirmLabel: 'Bỏ thay đổi', tone: 'danger' })) !== false) {
        router.post(locationUrl('/discard'), {}, { preserveScroll: true, onSuccess: reload });
    }
}

async function restore(version: number): Promise<void> {
    if ((await confirmAction({ title: 'Khôi phục phiên bản này vào nháp?', message: 'Nháp hiện tại bị thay thế. Cửa hàng chỉ đổi sau khi xuất bản.', confirmLabel: 'Khôi phục' })) !== false) {
        router.post(locationUrl(`/restore/${version}`), {}, { preserveScroll: true, onSuccess: reload });
    }
}
</script>

<template>
    <Head title="Menu" />
    <p class="mb-2 text-sm text-slate-500">Giao diện</p>
    <PageHeader title="Menu" subtitle="Menu theo vị trí theme khai báo. Lưu nháp, xuất bản để hiện trên cửa hàng (trang có cache đổi sau vài phút).">
        <template v-if="location">
            <button v-if="menu?.has_changes && menu.published_at" type="button" :class="dangerButton" @click="discard">Bỏ thay đổi</button>
            <button type="button" :class="secondaryButton" :disabled="processing" @click="save()">Lưu nháp</button>
            <button type="button" :class="primaryButton" :disabled="processing" @click="publish">Xuất bản</button>
        </template>
    </PageHeader>
    <DesignTabs active="menus" />

    <p v-if="!locations.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Theme đang dùng không khai báo vị trí menu nào.</p>

    <div v-else class="grid gap-6 lg:grid-cols-[14rem_1fr]">
        <nav class="space-y-1 text-sm" aria-label="Vị trí menu">
            <Link
                v-for="item in locations"
                :key="item.code"
                :href="`${baseUrl}?location=${item.code}`"
                class="block rounded-md px-3 py-2"
                :class="item.code === location ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-slate-600 hover:bg-slate-100'"
            >
                {{ item.label }}
                <span class="block font-mono text-xs text-slate-400">{{ item.code }} · {{ item.depth }} cấp</span>
            </Link>
        </nav>

        <section v-if="current && menu" class="space-y-4">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <StatusBadge v-if="!menu.published_at" label="Chưa xuất bản — cửa hàng dùng menu mặc định của theme" tone="warning" />
                <template v-else>
                    <StatusBadge label="Đã xuất bản" tone="success" />
                    <span class="text-slate-500">{{ formatDate(menu.published_at) }}</span>
                    <StatusBadge v-if="menu.has_changes || dirty" label="Có thay đổi chưa xuất bản" tone="warning" />
                </template>
            </div>
            <p v-if="errors.items" class="text-sm text-red-600">{{ errors.items }}</p>

            <MenuItems v-model:items="items" :max-depth="current.depth" :errors="errors" path="items" />
            <p v-if="!items.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có mục nào.</p>
            <button type="button" :class="secondaryButton" @click="add">+ Thêm mục</button>

            <div v-if="menu.versions.length" class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="mb-2 font-semibold">Phiên bản đã xuất bản trước</h2>
                <ul class="divide-y divide-slate-100">
                    <li v-for="(version, index) in menu.versions" :key="index" class="flex items-center justify-between py-1.5">
                        <span>{{ formatDate(version.published_at) }} · {{ version.count }} mục</span>
                        <button type="button" class="text-indigo-600 hover:underline" @click="restore(index)">Khôi phục vào nháp</button>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
