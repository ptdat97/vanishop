<script setup lang="ts">
import { getJson, HttpError, postJson } from '@admin/http';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { computed, onMounted, ref } from 'vue';

/**
 * Thư viện ảnh dùng chung: trang Admin → Thư viện ảnh và modal chọn ảnh (MediaPicker) ở các form.
 * `pick` bật chế độ chọn: nút "Chọn" phát `select` với các ảnh đã chọn; nhấp đúp một ảnh = chọn ngay.
 */
const props = withDefaults(defineProps<{ libraryUrl: string; pick?: boolean; multiple?: boolean }>(), { pick: false, multiple: true });
const emit = defineEmits<{ select: [items: MediaItem[]]; cancel: [] }>();

interface BrowseResponse {
    folders: string[];
    items: MediaItem[];
    meta: { page: number; last_page: number; total: number };
    can_manage: boolean;
}

const sorts = [
    { value: 'created_at:desc', label: 'Mới nhất' },
    { value: 'created_at:asc', label: 'Cũ nhất' },
    { value: 'original_name:asc', label: 'Tên A → Z' },
    { value: 'size_bytes:desc', label: 'Dung lượng lớn' },
];

const folders = ref<string[]>([]);
const folder = ref('');
const keyword = ref('');
const sort = ref(sorts[0].value);
const page = ref(1);
const items = ref<MediaItem[]>([]);
const meta = ref({ page: 1, last_page: 1, total: 0 });
const canManage = ref(false);
const selected = ref<Map<number, MediaItem>>(new Map());
const loading = ref(false);
const uploading = ref(false);
const dragging = ref(false);
const message = ref<{ type: 'ok' | 'error'; text: string } | null>(null);
const moveTarget = ref('');

const searching = computed(() => keyword.value.trim() !== '');
const selectedItems = computed(() => Array.from(selected.value.values()));
const folderName = (path: string) => (path === '' ? 'Tất cả (thư mục gốc)' : path.split('/').pop());
const depth = (path: string) => (path === '' ? 0 : path.split('/').length);

function url(path: string): string {
    return `${props.libraryUrl.replace(/\/$/, '')}/${path}`;
}

function fail(error: unknown): void {
    const errors = error instanceof HttpError ? Object.values(error.errors).flat() : [];
    message.value = { type: 'error', text: errors[0] ?? (error instanceof Error ? error.message : 'Có lỗi xảy ra.') };
}

async function load(): Promise<void> {
    loading.value = true;
    const [sortBy, order] = sort.value.split(':');
    try {
        const data = await getJson<BrowseResponse>(url('browse'), { folder: folder.value, keyword: keyword.value.trim(), sort: sortBy, order, page: page.value });
        folders.value = data.folders;
        items.value = data.items;
        meta.value = data.meta;
        canManage.value = data.can_manage;
    } catch (error) {
        fail(error);
    } finally {
        loading.value = false;
    }
}

function openFolder(path: string): void {
    folder.value = path;
    keyword.value = '';
    page.value = 1;
    moveTarget.value = path;
    load();
}

function search(): void {
    page.value = 1;
    load();
}

function goTo(target: number): void {
    page.value = target;
    load();
}

function toggle(item: MediaItem): void {
    const next = new Map(props.multiple ? selected.value : []);
    if (selected.value.has(item.id)) {
        next.delete(item.id);
    } else {
        next.set(item.id, item);
    }
    selected.value = next;
}

function pickNow(item: MediaItem): void {
    if (props.pick) {
        emit('select', [item]);
    }
}

async function run(action: () => Promise<{ message?: string } | unknown>, reload = true): Promise<void> {
    message.value = null;
    try {
        const result = (await action()) as { message?: string } | undefined;
        if (result && typeof result === 'object' && result.message) {
            message.value = { type: 'ok', text: result.message };
        }
        if (reload) {
            await load();
        }
    } catch (error) {
        fail(error);
    }
}

async function upload(files: File[]): Promise<void> {
    if (!files.length || !canManage.value) {
        return;
    }
    const body = new FormData();
    body.append('folder', folder.value);
    files.forEach((file) => body.append('images[]', file));
    uploading.value = true;
    await run(async () => {
        const result = await postJson<{ message: string; items: MediaItem[] }>(url('upload'), body);
        if (props.pick) {
            const next = new Map(props.multiple ? selected.value : []);
            (props.multiple ? result.items : result.items.slice(0, 1)).forEach((item) => next.set(item.id, item));
            selected.value = next;
        }
        if (searching.value) {
            keyword.value = '';
        }
        return result;
    });
    uploading.value = false;
}

function onFileInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    upload(Array.from(input.files ?? []));
    input.value = '';
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    upload(Array.from(event.dataTransfer?.files ?? []).filter((file) => file.type.startsWith('image/')));
}

function createFolder(): void {
    const name = prompt(folder.value ? `Tên thư mục con trong "${folder.value}":` : 'Tên thư mục mới:');
    if (name?.trim()) {
        run(async () => {
            const result = await postJson<{ path: string }>(url('folders'), { parent: folder.value, name });
            folder.value = result.path;
            moveTarget.value = result.path;
            return { message: `Đã tạo thư mục ${result.path}.` };
        });
    }
}

function renameFolder(): void {
    const name = prompt('Tên mới của thư mục:', folderName(folder.value));
    if (name?.trim()) {
        run(async () => {
            const result = await postJson<{ path: string }>(url('folders/rename'), { path: folder.value, name });
            folder.value = result.path;
            return { message: `Đã đổi thành ${result.path}.` };
        });
    }
}

function deleteFolder(): void {
    if (confirm(`Xoá thư mục "${folder.value}"? Chỉ xoá được thư mục rỗng.`)) {
        run(async () => {
            const result = await postJson<{ message: string }>(url('folders/delete'), { path: folder.value });
            folder.value = folder.value.includes('/') ? folder.value.slice(0, folder.value.lastIndexOf('/')) : '';
            return result;
        });
    }
}

function renameItem(): void {
    const [item] = selectedItems.value;
    const name = prompt('Tên hiển thị của ảnh:', item.name);
    if (name?.trim()) {
        run(() => postJson(url(`items/${item.id}/rename`), { name }));
    }
}

function moveItems(): void {
    const ids = selectedItems.value.map((item) => item.id);
    run(async () => {
        const result = await postJson<{ message: string }>(url('items/move'), { ids, folder: moveTarget.value });
        selected.value = new Map();
        return result;
    });
}

function deleteItems(): void {
    if (confirm(`Xoá vĩnh viễn ${selected.value.size} ảnh? Ảnh đang dùng ở sản phẩm/danh mục sẽ được giữ lại.`)) {
        run(async () => {
            const result = await postJson<{ message: string }>(url('items/delete'), { ids: selectedItems.value.map((item) => item.id) });
            selected.value = new Map();
            return result;
        });
    }
}

function size(bytes: number): string {
    return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

onMounted(load);
</script>

<template>
    <div class="flex h-full min-h-0 flex-col gap-3 lg:flex-row">
        <aside class="shrink-0 space-y-1 rounded-lg border border-slate-200 bg-white p-3 text-sm lg:w-60 lg:overflow-y-auto">
            <button
                v-for="path in ['', ...folders]"
                :key="path || '/'"
                type="button"
                class="block w-full truncate rounded px-2 py-1 text-left hover:bg-slate-100"
                :class="{ 'bg-indigo-50 font-medium text-indigo-700': !searching && folder === path }"
                :style="{ paddingLeft: `${0.5 + depth(path) * 0.75}rem` }"
                @click="openFolder(path)"
            >
                {{ path === '' ? '🗂' : '📁' }} {{ folderName(path) }}
            </button>
            <div v-if="canManage" class="flex flex-wrap gap-x-3 gap-y-1 border-t border-slate-100 pt-2 text-xs">
                <button type="button" class="text-indigo-600 hover:underline" @click="createFolder">+ Thư mục</button>
                <template v-if="folder && !searching">
                    <button type="button" class="text-indigo-600 hover:underline" @click="renameFolder">Đổi tên</button>
                    <button type="button" class="text-red-600 hover:underline" @click="deleteFolder">Xoá</button>
                </template>
            </div>
        </aside>

        <section class="flex min-h-0 flex-1 flex-col rounded-lg border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-3">
                <form class="flex flex-1 gap-2" @submit.prevent="search">
                    <input v-model="keyword" :class="inputClass" class="max-w-xs" type="search" placeholder="Tìm theo tên trong mọi thư mục…" />
                    <select v-model="sort" :class="inputClass" class="max-w-40" @change="search">
                        <option v-for="option in sorts" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </form>
                <label v-if="canManage" :class="primaryButton" class="cursor-pointer">
                    {{ uploading ? 'Đang tải lên…' : 'Tải ảnh lên' }}
                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" multiple :disabled="uploading" @change="onFileInput" />
                </label>
            </div>

            <p v-if="message" class="mx-3 mt-3 rounded px-3 py-2 text-sm" :class="message.type === 'ok' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">{{ message.text }}</p>
            <p class="px-3 pt-2 text-xs text-slate-500">
                {{ searching ? `Kết quả tìm "${keyword.trim()}"` : folderName(folder) }} · {{ meta.total }} ảnh
                <template v-if="canManage"> · kéo thả ảnh vào đây để tải lên thư mục này</template>
            </p>

            <div
                class="relative min-h-48 flex-1 overflow-y-auto p-3"
                :class="{ 'bg-indigo-50 ring-2 ring-indigo-300 ring-inset': dragging }"
                @dragover.prevent="dragging = canManage"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-6">
                    <button
                        v-for="item in items"
                        :key="item.id"
                        type="button"
                        class="group overflow-hidden rounded-md border text-left"
                        :class="selected.has(item.id) ? 'border-indigo-500 ring-2 ring-indigo-300' : 'border-slate-200 hover:border-slate-400'"
                        :title="item.name"
                        @click="toggle(item)"
                        @dblclick="pickNow(item)"
                    >
                        <div class="relative aspect-square bg-slate-100">
                            <img :src="item.thumb_url" :alt="item.name" loading="lazy" class="h-full w-full object-cover" />
                            <span v-if="item.usages_count" class="absolute top-1 left-1 rounded bg-emerald-600/90 px-1.5 text-[10px] text-white">Đang dùng {{ item.usages_count }}</span>
                            <span v-if="selected.has(item.id)" class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-xs text-white">✓</span>
                        </div>
                        <div class="p-1.5 text-xs">
                            <p class="truncate font-medium">{{ item.name }}</p>
                            <p class="text-slate-400">{{ item.width }}×{{ item.height }} · {{ size(item.size_bytes) }}</p>
                            <p v-if="searching" class="truncate text-slate-400">📁 {{ folderName(item.folder) }}</p>
                        </div>
                    </button>
                </div>
                <p v-if="!loading && !items.length" class="py-12 text-center text-sm text-slate-500">{{ searching ? 'Không tìm thấy ảnh.' : 'Thư mục chưa có ảnh.' }}</p>
                <p v-if="loading" class="absolute inset-x-0 top-2 text-center text-xs text-slate-400">Đang tải…</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 p-3 text-sm">
                <button type="button" :class="secondaryButton" :disabled="meta.page <= 1" @click="goTo(meta.page - 1)">←</button>
                <span class="text-slate-500">Trang {{ meta.page }}/{{ meta.last_page }}</span>
                <button type="button" :class="secondaryButton" :disabled="meta.page >= meta.last_page" @click="goTo(meta.page + 1)">→</button>

                <template v-if="canManage && selected.size">
                    <span class="ml-2 text-slate-500">Đã chọn {{ selected.size }}</span>
                    <button v-if="selected.size === 1" type="button" :class="secondaryButton" @click="renameItem">Đổi tên</button>
                    <select v-model="moveTarget" :class="inputClass" class="max-w-44">
                        <option v-for="path in ['', ...folders]" :key="path || '/'" :value="path">→ {{ path || 'Thư mục gốc' }}</option>
                    </select>
                    <button type="button" :class="secondaryButton" @click="moveItems">Chuyển</button>
                    <button type="button" :class="dangerButton" @click="deleteItems">Xoá</button>
                </template>
                <button v-else-if="selected.size" type="button" class="text-slate-500 hover:underline" @click="selected = new Map()">Bỏ chọn</button>

                <div v-if="pick" class="ml-auto flex gap-2">
                    <button type="button" :class="secondaryButton" @click="emit('cancel')">Huỷ</button>
                    <button type="button" :class="primaryButton" :disabled="!selected.size" @click="emit('select', selectedItems)">Chọn {{ selected.size || '' }} ảnh</button>
                </div>
            </div>
        </section>
    </div>
</template>
