<script setup lang="ts">
import type { MediaItem, SharedProps } from '@admin/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MediaManager from './MediaManager.vue';

/**
 * Modal chọn ảnh từ Thư viện ảnh (như file manager popup của VaniCommerce). Dùng ở form bất kỳ:
 *   <MediaPicker v-model:open="picking" :multiple="true" @select="(items) => …" />
 * Ảnh tải lên trong modal được chọn sẵn.
 */
withDefaults(defineProps<{ title?: string; multiple?: boolean }>(), { title: 'Chọn ảnh từ thư viện', multiple: true });
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ select: [items: MediaItem[]] }>();

const libraryUrl = computed(() => usePage<SharedProps>().props.urls.media ?? null);

function choose(items: MediaItem[]): void {
    emit('select', items);
    open.value = false;
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-2 sm:p-6" @keydown.esc="open = false" @click.self="open = false">
            <div class="flex h-[90vh] w-full max-w-6xl flex-col rounded-lg bg-slate-50 p-3 shadow-xl" role="dialog" aria-modal="true" :aria-label="title">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="font-medium">{{ title }}</h2>
                    <button type="button" class="px-2 text-xl text-slate-500 hover:text-slate-800" aria-label="Đóng" @click="open = false">×</button>
                </div>
                <MediaManager v-if="libraryUrl" class="min-h-0 flex-1" :library-url="libraryUrl" pick :multiple="multiple" @select="choose" @cancel="open = false" />
                <p v-else class="p-6 text-sm text-slate-600">Bạn chưa có quyền xem Thư viện ảnh (media.view). Hãy nhờ quản trị cấp quyền.</p>
            </div>
        </div>
    </Teleport>
</template>
