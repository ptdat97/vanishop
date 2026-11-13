<script lang="ts">
/** Số popup đang mở (popup lồng nhau: thư viện ảnh trên ma trận biến thể…) — chỉ mở khoá cuộn trang nền khi đóng popup cuối. */
let openCount = 0;
</script>

<script setup lang="ts">
import { confirmAction } from '@admin/confirm';
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';

/**
 * Popup dùng chung của Admin (nền cho FullscreenDialog, MediaPicker; trang/plugin dùng trực tiếp được):
 *   <Dialog v-model:open="open" title="…" size="large" :dirty="form.isDirty">…<template #footer>…</template></Dialog>
 * - Esc, nút ×, bấm ra nền (trừ `fullscreen`) → đóng; `dirty` → hỏi lại trước khi đóng.
 * - Giữ Tab/Shift+Tab trong popup (focus trap); mở → focus vào popup, đóng → trả focus về nút đã mở.
 * - `keepMounted`: nội dung ở lại trong DOM khi đóng (giữ bản nháp đang sửa); mặc định gỡ khi đóng.
 * - Khoá cuộn trang nền khi mở; popup lồng nhau đếm chung.
 */
const props = withDefaults(defineProps<{ title: string; subtitle?: string; size?: 'medium' | 'large' | 'fullscreen'; dirty?: boolean; keepMounted?: boolean }>(), {
    size: 'medium',
    dirty: false,
    keepMounted: false,
});
const open = defineModel<boolean>('open', { default: false });
const panel = ref<HTMLElement | null>(null);
const titleId = useId();
let returnFocus: HTMLElement | null = null;
let locked = false;

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]), [contenteditable="true"]';

function lockScroll(value: boolean): void {
    if (value === locked) return;
    locked = value;
    openCount += value ? 1 : -1;
    document.body.style.overflow = openCount > 0 ? 'hidden' : '';
}

watch(
    open,
    (value) => {
        if (typeof document === 'undefined') return;
        lockScroll(value);
        if (value) {
            returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            nextTick(() => {
                const autofocus = panel.value?.querySelector<HTMLElement>('[autofocus]');
                (autofocus ?? panel.value)?.focus();
            });
        } else if (returnFocus?.isConnected) {
            returnFocus.focus();
            returnFocus = null;
        }
    },
    { immediate: true },
);
onBeforeUnmount(() => lockScroll(false));

/** Yêu cầu đóng (Esc, ×, nền, nút "Đóng" trong slot qua `close`): hỏi lại khi còn thay đổi chưa lưu. */
async function close(): Promise<void> {
    if (props.dirty && (await confirmAction({ title: 'Đóng khi còn thay đổi chưa lưu?', message: 'Các thay đổi chưa lưu sẽ mất.', confirmLabel: 'Đóng', tone: 'danger' })) === false) {
        return;
    }
    open.value = false;
}

function trapTab(event: KeyboardEvent): void {
    const items = [...(panel.value?.querySelectorAll<HTMLElement>(FOCUSABLE) ?? [])].filter((element) => element.offsetParent !== null || element === document.activeElement);
    if (items.length === 0) {
        event.preventDefault();
        panel.value?.focus();
        return;
    }
    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;
    if (event.shiftKey && (active === first || active === panel.value)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
    }
}

const sizeClass = {
    medium: 'm-auto max-h-[90vh] w-full max-w-lg rounded-lg',
    large: 'm-auto h-[90vh] w-full max-w-6xl rounded-lg',
    fullscreen: 'min-h-0 flex-1 sm:rounded-xl',
};

defineExpose({ close });
</script>

<template>
    <Teleport to="body">
        <div
            v-if="keepMounted || open"
            v-show="open"
            class="fixed inset-0 z-50 flex flex-col bg-slate-900/50 p-2 sm:p-4"
            @keydown.esc.stop="close"
            @keydown.tab="trapTab"
            @click.self="size !== 'fullscreen' && close()"
        >
            <div
                ref="panel"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="flex flex-col overflow-hidden bg-slate-50 shadow-2xl focus:outline-none"
                :class="sizeClass[size]"
            >
                <header class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                    <div class="min-w-0 flex-1">
                        <h2 :id="titleId" class="truncate text-base font-semibold text-slate-900">{{ title }}</h2>
                        <p v-if="subtitle" class="truncate text-xs text-slate-500">{{ subtitle }}</p>
                    </div>
                    <slot name="actions" />
                    <button type="button" class="rounded px-2 text-2xl leading-none text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Đóng" @click="close">×</button>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto" :class="size === 'large' ? 'flex flex-col p-3' : 'p-4 sm:p-6'">
                    <slot :close="close" />
                </div>
                <footer v-if="$slots.footer" class="flex flex-wrap items-center gap-3 border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
                    <slot name="footer" :close="close" />
                </footer>
            </div>
        </div>
    </Teleport>
</template>
