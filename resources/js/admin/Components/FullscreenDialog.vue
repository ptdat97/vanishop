<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';

/**
 * Popup toàn màn hình dùng chung của Admin cho màn hình làm việc lớn (vd. ma trận biến thể):
 *   <FullscreenDialog v-model:open="open" title="Biến thể" subtitle="…">
 *       …nội dung…
 *       <template #actions>…nút ở đầu…</template>
 *       <template #footer>…nút Lưu/Đóng…</template>
 *   </FullscreenDialog>
 * Nội dung **vẫn ở trong DOM khi đóng** (v-show) để không mất bản nháp đang sửa. Esc hoặc nút × đóng; khoá cuộn trang nền khi mở.
 * Hộp xác nhận và thư viện ảnh (z-50) hiện trên popup này (z-40).
 */
defineProps<{ title: string; subtitle?: string }>();
const open = defineModel<boolean>('open', { default: false });
const panel = ref<HTMLElement | null>(null);
const titleId = useId();
let returnFocus: HTMLElement | null = null;

watch(open, (value) => {
    document.body.style.overflow = value ? 'hidden' : '';
    if (value) {
        returnFocus = document.activeElement as HTMLElement | null;
        nextTick(() => panel.value?.focus());
    } else {
        returnFocus?.focus();
    }
});
onBeforeUnmount(() => (document.body.style.overflow = ''));
</script>

<template>
    <Teleport to="body">
        <div v-show="open" class="fixed inset-0 z-40 flex flex-col bg-slate-900/50 sm:p-4" @keydown.esc.stop="open = false">
            <div
                ref="panel"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="flex min-h-0 flex-1 flex-col overflow-hidden bg-slate-50 shadow-2xl focus:outline-none sm:rounded-xl"
            >
                <header class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                    <div class="min-w-0 flex-1">
                        <h2 :id="titleId" class="truncate text-base font-semibold text-slate-900">{{ title }}</h2>
                        <p v-if="subtitle" class="truncate text-xs text-slate-500">{{ subtitle }}</p>
                    </div>
                    <slot name="actions" />
                    <button type="button" class="rounded px-2 text-2xl leading-none text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Đóng" @click="open = false">×</button>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                    <slot />
                </div>
                <footer v-if="$slots.footer" class="flex flex-wrap items-center gap-3 border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
                    <slot name="footer" />
                </footer>
            </div>
        </div>
    </Teleport>
</template>
