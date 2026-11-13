<script setup lang="ts">
import { GENERAL_ERROR_KEYS, firstTime, toast, toastState, type ToastTone } from '@admin/toast';
import { router, usePage } from '@inertiajs/vue3';
import { ToastClose, ToastDescription, ToastProvider, ToastRoot, ToastViewport } from 'reka-ui';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Mọi thông báo của Admin dạng toast; gắn một lần trong AdminLayout. Nguồn:
 * - `toast()` (@admin/toast) từ trang/plugin;
 * - flash data `toasts` của Inertia (server: `->with('success'|'warning'|'error'|'info', …)`) qua sự kiện `flash` — phát ở mọi
 *   phản hồi mang flash nên **mỗi thao tác một toast**, kể cả cùng nội dung; không lưu vào lịch sử (quay lại không hiện lại);
 * - lỗi chung (`GENERAL_ERROR_KEYS`) qua sự kiện `error` của mỗi lần gửi.
 * Thành công tự ẩn sau 4 giây; lỗi/cảnh báo giữ tới khi đóng (`role=alert`).
 */
type FlashToast = { type: ToastTone; message: string };
const page = usePage<{ errors?: Record<string, unknown> }>();

function showFlash(flash: Record<string, unknown> | undefined): void {
    if (!flash || !firstTime(flash)) return;
    for (const item of (flash.toasts as FlashToast[] | undefined) ?? []) toast(item.message, item.type);
}

function showErrors(errors: Record<string, unknown> | undefined): void {
    if (!errors || !firstTime(errors)) return;
    for (const key of GENERAL_ERROR_KEYS) {
        const message = errors[key];
        if (typeof message === 'string' && message !== '') toast(message, 'error');
    }
}

const stopFlash = router.on('flash', (event) => showFlash(event.detail.flash as Record<string, unknown>));
const stopError = router.on('error', (event) => showErrors(event.detail.errors as Record<string, unknown>));
onMounted(() => {
    // Lần tải đầu (mở trang, tải lại sau redirect toàn trang).
    showFlash(page.flash as Record<string, unknown>);
    showErrors(page.props.errors);
});
onBeforeUnmount(() => {
    stopFlash();
    stopError();
});

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
