<script setup lang="ts">
import { toast } from '@admin/toast';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { getJson, HttpError, postJson, uploadJson } from '@admin/http';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { checkFile, formatSize } from '@admin/upload';
import { computed, onMounted, ref } from 'vue';

/**
 * Thư viện ảnh dùng chung: trang Admin → Thư viện ảnh và modal chọn ảnh (MediaPicker) ở các form.
 * `pick` bật chế độ chọn: nút "Chọn" phát `select` với các ảnh đã chọn; nhấp đúp một ảnh = chọn ngay.
 * `kind` (0.3.91): `image` | `video` lọc loại tệp được duyệt/tải lên; `any` (mặc định, trang Thư viện) hiện cả hai.
 * Tải lên: kiểm loại/dung lượng theo giới hạn của server trước khi gửi; mỗi tệp một yêu cầu có thanh tiến độ, tệp lỗi giữ lại
 * trong hàng đợi với nút "Thử lại".
 */
const props = withDefaults(defineProps<{ libraryUrl: string; pick?: boolean; multiple?: boolean; kind?: 'image' | 'video' | 'any' }>(), { pick: false, multiple: true, kind: 'any' });
const emit = defineEmits<{ select: [items: MediaItem[]]; cancel: [] }>();

interface BrowseResponse {
    folders: string[];
    items: MediaItem[];
    meta: { page: number; last_page: number; total: number };
    can_manage: boolean;
    limits?: { image_kb: number; video_kb: number };
}

const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const VIDEO_TYPES = ['video/mp4', 'video/webm'];
const acceptedTypes = computed(() => (props.kind === 'image' ? IMAGE_TYPES : props.kind === 'video' ? VIDEO_TYPES : [...IMAGE_TYPES, ...VIDEO_TYPES]));
const noun = computed(() => (props.kind === 'image' ? 'ảnh' : props.kind === 'video' ? 'video' : 'ảnh/video'));
const limits = ref<{ image_kb: number; video_kb: number } | null>(null);

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
interface UploadItem {
    key: number;
    file: File;
    folder: string;
    progress: number;
    status: 'waiting' | 'uploading' | 'error';
    error: string | null;
}
const queue = ref<UploadItem[]>([]);
let nextUploadKey = 0;
let draining = false;
const uploading = computed(() => queue.value.some((item) => item.status !== 'error'));
const dragging = ref(false);
const moveTarget = ref('');

const searching = computed(() => keyword.value.trim() !== '');
const selectedItems = computed(() => Array.from(selected.value.values()));
const folderName = (path: string) => (path === '' ? 'Tất cả (thư mục gốc)' : path.split('/').pop());
const depth = (path: string) => (path === '' ? 0 : path.split('/').length);

function url(path: string): string {
    return `${props.libraryUrl.replace(/\/$/, '')}/${path}`;
}

function fail(error: unknown): void {
    toast(errorMessage(error), 'error');
}

async function load(): Promise<void> {
    loading.value = true;
    const [sortBy, order] = sort.value.split(':');
    try {
        const query: Record<string, string | number> = { folder: folder.value, keyword: keyword.value.trim(), sort: sortBy, order, page: page.value };
        if (props.kind !== 'any') query.kind = props.kind;
        const data = await getJson<BrowseResponse>(url('browse'), query);
        limits.value = data.limits ?? null;
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
    try {
        const result = (await action()) as { message?: string } | undefined;
        if (result && typeof result === 'object' && result.message) {
            toast(result.message);
        }
        if (reload) {
            await load();
        }
    } catch (error) {
        fail(error);
    }
}

function errorMessage(error: unknown): string {
    const errors = error instanceof HttpError ? Object.values(error.errors).flat() : [];
    return errors[0] ?? (error instanceof Error ? error.message : 'Có lỗi xảy ra.');
}

function upload(files: File[]): void {
    if (!files.length || !canManage.value) {
        return;
    }
    const rejected: string[] = [];
    for (const file of files) {
        const maxKb = limits.value ? (file.type.startsWith('video/') ? limits.value.video_kb : limits.value.image_kb) : null;
        const error = checkFile(file, { accept: acceptedTypes.value, maxKb });
        if (error) {
            rejected.push(error);
        } else {
            queue.value.push({ key: ++nextUploadKey, file, folder: folder.value, progress: 0, status: 'waiting', error: null });
        }
    }
    if (rejected.length) {
        toast(rejected.join('\n'), 'error');
    }
    drainQueue();
}

/** Gửi lần lượt từng tệp đang chờ; xong cả lượt thì chọn sẵn tệp vừa tải (chế độ chọn) và tải lại danh sách. */
async function drainQueue(): Promise<void> {
    if (draining) {
        return;
    }
    draining = true;
    const uploaded: MediaItem[] = [];
    let item: UploadItem | undefined;
    while ((item = queue.value.find((entry) => entry.status === 'waiting'))) {
        const current = item;
        current.status = 'uploading';
        current.progress = 0;
        const body = new FormData();
        body.append('folder', current.folder);
        body.append('images[]', current.file);
        try {
            const result = await uploadJson<{ items: MediaItem[] }>(url('upload'), body, (percent) => (current.progress = percent));
            uploaded.push(...result.items);
            queue.value = queue.value.filter((entry) => entry.key !== current.key);
        } catch (error) {
            current.status = 'error';
            current.error = errorMessage(error);
        }
    }
    draining = false;
    if (!uploaded.length) {
        return;
    }
    if (props.pick) {
        const next = new Map(props.multiple ? selected.value : []);
        const matching = uploaded.filter((media) => props.kind === 'any' || (media.kind ?? 'image') === props.kind);
        (props.multiple ? matching : matching.slice(0, 1)).forEach((media) => next.set(media.id, media));
        selected.value = next;
    }
    if (searching.value) {
        keyword.value = '';
    }
    toast(`Đã tải lên ${uploaded.length} ${noun.value}.`);
    await load();
}

function retryUpload(entry: UploadItem): void {
    entry.status = 'waiting';
    entry.error = null;
    drainQueue();
}

function dismissUpload(entry: UploadItem): void {
    queue.value = queue.value.filter((item) => item.key !== entry.key);
}

function onFileInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    upload(Array.from(input.files ?? []));
    input.value = '';
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    upload(Array.from(event.dataTransfer?.files ?? []));
}

async function createFolder(): Promise<void> {
    const name = await confirmAction({ title: folder.value ? `Tạo thư mục con trong "${folder.value}"` : 'Tạo thư mục mới', confirmLabel: 'Tạo', reason: { label: 'Tên thư mục', required: true } });
    if (name) {
        run(async () => {
            const result = await postJson<{ path: string }>(url('folders'), { parent: folder.value, name });
            folder.value = result.path;
            moveTarget.value = result.path;
            return { message: `Đã tạo thư mục ${result.path}.` };
        });
    }
}

async function renameFolder(): Promise<void> {
    const name = await confirmAction({ title: 'Đổi tên thư mục', confirmLabel: 'Đổi tên', reason: { label: 'Tên mới', required: true, value: folderName(folder.value) } });
    if (name) {
        run(async () => {
            const result = await postJson<{ path: string }>(url('folders/rename'), { path: folder.value, name });
            folder.value = result.path;
            return { message: `Đã đổi thành ${result.path}.` };
        });
    }
}

async function deleteFolder(): Promise<void> {
    if ((await confirmAction({ title: `Xoá thư mục "${folder.value}"?`, message: 'Chỉ xoá được thư mục rỗng.', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        run(async () => {
            const result = await postJson<{ message: string }>(url('folders/delete'), { path: folder.value });
            folder.value = folder.value.includes('/') ? folder.value.slice(0, folder.value.lastIndexOf('/')) : '';
            return result;
        });
    }
}

async function renameItem(): Promise<void> {
    const [item] = selectedItems.value;
    const name = await confirmAction({ title: `Đổi tên ${noun.value}`, confirmLabel: 'Đổi tên', reason: { label: `Tên hiển thị của ${noun.value}`, required: true, value: item.name } });
    if (name) {
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

async function deleteItems(): Promise<void> {
    if ((await confirmAction({ title: `Xoá vĩnh viễn ${selected.value.size} ${noun.value}?`, message: 'Tệp đang được dùng (sản phẩm, danh mục, khối trang chủ…) sẽ được giữ lại.', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        run(async () => {
            const result = await postJson<{ message: string }>(url('items/delete'), { ids: selectedItems.value.map((item) => item.id) });
            selected.value = new Map();
            return result;
        });
    }
}

const size = formatSize;

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
                    <div class="w-40"><Select v-model="sort" :options="sorts" aria-label="Sắp xếp" @change="search" /></div>
                </form>
                <label v-if="canManage" :class="primaryButton" class="cursor-pointer">
                    {{ uploading ? 'Đang tải lên…' : `Tải ${noun} lên` }}
                    <input type="file" class="hidden" :accept="acceptedTypes.join(',')" multiple @change="onFileInput" />
                </label>
            </div>

            <p class="px-3 pt-2 text-xs text-slate-500">
                {{ searching ? `Kết quả tìm "${keyword.trim()}"` : folderName(folder) }} · {{ meta.total }} {{ noun }}
                <template v-if="canManage"> · kéo thả {{ noun }} vào đây để tải lên thư mục này</template>
                <template v-if="canManage && limits && kind !== 'video'"> · ảnh JPG/PNG/WebP tối đa {{ size(limits.image_kb * 1024) }}</template>
                <template v-if="canManage && limits && kind !== 'image'"> · video MP4/WebM tối đa {{ size(limits.video_kb * 1024) }}</template>
            </p>
            <ul v-if="queue.length" class="space-y-1 px-3 pt-2 text-xs" data-upload-queue>
                <li v-for="entry in queue" :key="entry.key" class="flex items-center gap-2">
                    <span class="w-48 shrink-0 truncate" :title="entry.file.name">{{ entry.file.name }}</span>
                    <template v-if="entry.status === 'error'">
                        <span class="min-w-0 flex-1 truncate text-red-600" :title="entry.error ?? ''">{{ entry.error }}</span>
                        <button type="button" class="text-indigo-600 hover:underline" @click="retryUpload(entry)">Thử lại</button>
                        <button type="button" class="text-slate-500 hover:underline" @click="dismissUpload(entry)">Bỏ</button>
                    </template>
                    <template v-else>
                        <div class="h-1.5 flex-1 overflow-hidden rounded bg-slate-200" role="progressbar" :aria-valuenow="entry.progress" aria-valuemin="0" aria-valuemax="100" :aria-label="entry.file.name">
                            <div class="h-full bg-indigo-600 transition-all" :style="{ width: `${entry.progress}%` }" />
                        </div>
                        <span class="w-10 text-right text-slate-500">{{ entry.status === 'waiting' ? 'Chờ' : `${entry.progress}%` }}</span>
                    </template>
                </li>
            </ul>

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
                            <video v-if="item.kind === 'video'" :src="`${item.url}#t=0.5`" muted playsinline preload="metadata" class="h-full w-full object-cover" />
                            <img v-else :src="item.thumb_url ?? item.url" :alt="item.name" loading="lazy" class="h-full w-full object-cover" />
                            <span v-if="item.kind === 'video'" class="absolute bottom-1 left-1 rounded bg-slate-900/80 px-1.5 text-[10px] text-white">▶ Video</span>
                            <span v-if="item.usages_count" class="absolute top-1 left-1 rounded bg-emerald-600/90 px-1.5 text-[10px] text-white">Đang dùng {{ item.usages_count }}</span>
                            <span v-if="selected.has(item.id)" class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-xs text-white">✓</span>
                        </div>
                        <div class="p-1.5 text-xs">
                            <p class="truncate font-medium">{{ item.name }}</p>
                            <p class="text-slate-400"><template v-if="item.width">{{ item.width }}×{{ item.height }} · </template>{{ size(item.size_bytes) }}</p>
                            <p v-if="searching" class="truncate text-slate-400">📁 {{ folderName(item.folder) }}</p>
                        </div>
                    </button>
                </div>
                <p v-if="!loading && !items.length" class="py-12 text-center text-sm text-slate-500">{{ searching ? `Không tìm thấy ${noun}.` : `Thư mục chưa có ${noun}.` }}</p>
                <p v-if="loading" class="absolute inset-x-0 top-2 text-center text-xs text-slate-400">Đang tải…</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 p-3 text-sm">
                <button type="button" :class="secondaryButton" :disabled="meta.page <= 1" @click="goTo(meta.page - 1)">←</button>
                <span class="text-slate-500">Trang {{ meta.page }}/{{ meta.last_page }}</span>
                <button type="button" :class="secondaryButton" :disabled="meta.page >= meta.last_page" @click="goTo(meta.page + 1)">→</button>

                <template v-if="canManage && selected.size">
                    <span class="ml-2 text-slate-500">Đã chọn {{ selected.size }}</span>
                    <button v-if="selected.size === 1" type="button" :class="secondaryButton" @click="renameItem">Đổi tên</button>
                    <div class="w-44">
                        <Select v-model="moveTarget" :options="['', ...folders].map((path) => ({ value: path, label: `→ ${path || 'Thư mục gốc'}` }))" aria-label="Chuyển tới thư mục" />
                    </div>
                    <button type="button" :class="secondaryButton" @click="moveItems">Chuyển</button>
                    <button type="button" :class="dangerButton" @click="deleteItems">Xoá</button>
                </template>
                <button v-else-if="selected.size" type="button" class="text-slate-500 hover:underline" @click="selected = new Map()">Bỏ chọn</button>

                <div v-if="pick" class="ml-auto flex gap-2">
                    <button type="button" :class="secondaryButton" @click="emit('cancel')">Huỷ</button>
                    <button type="button" :class="primaryButton" :disabled="!selected.size" @click="emit('select', selectedItems)">Chọn {{ selected.size || '' }} {{ noun }}</button>
                </div>
            </div>
        </section>
    </div>
</template>
