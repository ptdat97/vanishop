<script setup lang="ts">
import { secondaryButton } from '@admin/styles';
import type { MediaItem, SharedProps } from '@admin/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Sortable from '../Sortable.vue';
import SortHandle from '../SortHandle.vue';
import MediaPicker from './MediaPicker.vue';

/**
 * Ô chọn ảnh dùng chung (0.3.87) — dùng lại ảnh của Thư viện ảnh ở mọi form: v-model là id ảnh (`multiple`: danh sách
 * id có thứ tự). Xem trước tự tải theo id (`/admin/media/items`), không cần trang truyền URL. Lưu id, không lưu URL: URL
 * bản thu nhỏ đổi theo cấu hình Kích thước ảnh / định dạng.
 *
 *     <MediaField v-model="form.cover_media_id" />
 *     <MediaField v-model="form.media_ids" multiple :max="8" />
 *     <MediaField v-model="form.video_media_id" kind="video" label="video" />   (0.3.91)
 */
const props = withDefaults(defineProps<{ multiple?: boolean; max?: number; disabled?: boolean; label?: string; kind?: 'image' | 'video' }>(), {
    multiple: false,
    max: 50,
    disabled: false,
    label: 'ảnh',
    kind: 'image',
});
const model = defineModel<number | null | number[]>({ default: null });

const libraryUrl = computed(() => usePage<SharedProps>().props.urls.media ?? null);
const previews = ref<Record<number, MediaItem>>({});
const picking = ref(false);
const ids = computed<number[]>(() => (Array.isArray(model.value) ? model.value : model.value ? [model.value] : []));

/** Tải xem trước cho id chưa có (giá trị đã lưu từ server). */
async function loadPreviews(list: number[]): Promise<void> {
    const missing = list.filter((id) => !previews.value[id]);
    if (!missing.length || !libraryUrl.value) return;
    const query = new URLSearchParams(missing.map((id) => ['ids[]', String(id)]));
    try {
        const response = await fetch(`${libraryUrl.value}/items?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) return;
        const data = (await response.json()) as { items: MediaItem[] };
        for (const item of data.items) previews.value[item.id] = item;
    } catch {
        // Không tải được xem trước: vẫn giữ id, hiện ô trống.
    }
}
watch(ids, (list) => void loadPreviews(list), { immediate: true });

function pick(items: MediaItem[]): void {
    for (const item of items) previews.value[item.id] = item;
    if (props.multiple) {
        const next = [...ids.value];
        for (const item of items) if (!next.includes(item.id)) next.push(item.id);
        model.value = next.slice(0, props.max);
    } else {
        model.value = items[0]?.id ?? model.value;
    }
    picking.value = false;
}

function remove(id: number): void {
    model.value = props.multiple ? ids.value.filter((value) => value !== id) : null;
}

</script>

<template>
    <div class="space-y-2" data-media-field>
        <Sortable
            v-if="ids.length"
            v-slot="{ item: id, handle }"
            :model-value="ids"
            layout="grid"
            tag="ul"
            item-tag="li"
            :list-class="multiple ? 'grid grid-cols-3 gap-2 sm:grid-cols-4' : ''"
            :item-class="`group relative overflow-hidden rounded border border-slate-200 bg-slate-50 ${multiple ? 'aspect-square' : 'max-w-xs'}`"
            :item-label="(id) => previews[id]?.name ?? `${label} #${id}`"
            :disabled="disabled || !multiple"
            @update:model-value="(next) => (model = next)"
        >
                <video v-if="previews[id]?.kind === 'video'" :src="previews[id].url" muted playsinline controls preload="metadata" class="h-full w-full object-cover" :class="multiple ? '' : 'max-h-48'" />
                <img v-else-if="previews[id]" :src="previews[id].thumb_url ?? previews[id].url" :alt="previews[id].name" class="h-full w-full object-cover" :class="multiple ? '' : 'max-h-48'" />
                <div v-else class="flex h-24 items-center justify-center text-xs text-slate-400">#{{ id }}</div>
                <div v-if="!disabled" class="absolute inset-x-0 bottom-0 flex items-center justify-end gap-1 bg-white/80 p-1 text-xs opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                    <SortHandle v-if="multiple" v-bind="handle" class="mr-auto" />
                    <button type="button" class="px-1 text-red-600" :aria-label="`Bỏ ${label}`" @click="remove(id)">Bỏ</button>
                </div>
        </Sortable>
        <p v-else class="text-xs text-slate-500">Chưa chọn {{ label }}.</p>
        <button v-if="!disabled && libraryUrl && (!multiple || ids.length < max)" type="button" :class="secondaryButton" @click="picking = true">
            {{ multiple ? `Thêm ${label}` : ids.length ? `Đổi ${label}` : `Chọn ${label}` }}
        </button>
        <p v-if="!libraryUrl" class="text-xs text-slate-500">Cần quyền xem Thư viện ảnh để chọn {{ label }}.</p>
        <MediaPicker v-model:open="picking" :title="`Chọn ${label} từ thư viện`" :multiple="multiple" :kind="kind" @select="pick" />
    </div>
</template>
