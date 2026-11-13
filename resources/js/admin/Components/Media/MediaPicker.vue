<script setup lang="ts">
import type { MediaItem, SharedProps } from '@admin/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Dialog from '../Dialog.vue';
import MediaManager from './MediaManager.vue';

/**
 * Modal chọn ảnh từ Thư viện ảnh (như file manager popup của VaniCommerce). Dùng ở form bất kỳ:
 *   <MediaPicker v-model:open="picking" :multiple="true" @select="(items) => …" />
 * Ảnh tải lên trong modal được chọn sẵn. `kind` (0.3.91): mặc định chỉ ảnh; `video` để chọn video, `any` cả hai.
 */
withDefaults(defineProps<{ title?: string; multiple?: boolean; kind?: 'image' | 'video' | 'any' }>(), { title: 'Chọn ảnh từ thư viện', multiple: true, kind: 'image' });
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ select: [items: MediaItem[]] }>();

const libraryUrl = computed(() => usePage<SharedProps>().props.urls.media ?? null);

function choose(items: MediaItem[]): void {
    emit('select', items);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open" :title="title" size="large">
        <MediaManager v-if="libraryUrl" class="min-h-0 flex-1" :library-url="libraryUrl" pick :multiple="multiple" :kind="kind" @select="choose" @cancel="open = false" />
        <p v-else class="p-6 text-sm text-slate-600">Bạn chưa có quyền xem Thư viện ảnh (media.view). Hãy nhờ quản trị cấp quyền.</p>
    </Dialog>
</template>
