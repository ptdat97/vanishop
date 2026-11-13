<script setup lang="ts">
import { GENERAL_ERROR_KEYS, toast, toastState, type ToastTone } from '@admin/toast';
import { usePage } from '@inertiajs/vue3';
import { ToastClose, ToastDescription, ToastProvider, ToastRoot, ToastViewport } from 'reka-ui';
import { watch } from 'vue';

/**
 * Mọi thông báo của Admin dạng toast; gắn một lần trong AdminLayout. Nguồn: `toast()` (@admin/toast), `flash.success` của server,
 * lỗi chung (`GENERAL_ERROR_KEYS`) trong `errors`. Thành công tự ẩn sau 4 giây; lỗi/cảnh báo giữ tới khi đóng (đọc kịp, `role=alert`).
 */
const page = usePage<{ flash?: { success?: string | null }; errors?: Record<string, unknown> }>();

// Mỗi phản hồi tạo object mới → cùng một thông báo lặp lại (vd. thêm hai ghi chú, lưu lỗi hai lần) vẫn hiện lại.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast(flash.success, 'success');
    },
    { immediate: true },
);
watch(
    () => page.props.errors,
    (errors) => {
        for (const key of GENERAL_ERROR_KEYS) {
            const message = errors?.[key];
            if (typeof message === 'string' && message !== '') toast(message, 'error');
        }
    },
    { immediate: true },
);

const toneClass: Record<ToastTone, string> = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    error: 'border-red-200 bg-red-50 text-red-800',
    warning: 'border-amber-200 bg-amber-50 text-amber-900',
    info: 'border-slate-200 bg-white text-slate-800',
};
const icon: Record<ToastTone, string> = { success: '✓', error: '!', warning: '!', info: 'i' };
</script>

<template>
    <ToastProvider :duration="4000" label="Thông báo">
        <ToastRoot
            v-for="item in toastState.items"
            :key="item.id"
            v-model:open="item.open"
            :type="item.tone === 'error' || item.tone === 'warning' ? 'foreground' : 'background'"
            :duration="item.tone === 'error' || item.tone === 'warning' ? Infinity : 4000"
            class="flex items-start gap-3 rounded-md border px-4 py-3 text-sm shadow-lg"
            :class="toneClass[item.tone]"
            :data-toast="item.tone"
        >
            <span aria-hidden="true" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-current text-xs font-semibold">{{ icon[item.tone] }}</span>
            <ToastDescription class="flex-1 whitespace-pre-line">{{ item.message }}</ToastDescription>
            <ToastClose class="opacity-70 hover:opacity-100" aria-label="Đóng">✕</ToastClose>
        </ToastRoot>
        <ToastViewport class="fixed right-4 bottom-4 z-[60] flex w-96 max-w-[calc(100vw-2rem)] flex-col gap-2 outline-none" />
    </ToastProvider>
</template>
