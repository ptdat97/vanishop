<script setup lang="ts">
import Dialog from './Dialog.vue';

/**
 * Popup toàn màn hình dùng chung của Admin cho màn hình làm việc lớn (vd. ma trận biến thể):
 *   <FullscreenDialog v-model:open="open" title="Biến thể" subtitle="…">
 *       …nội dung…
 *       <template #actions>…nút ở đầu…</template>
 *       <template #footer>…nút Lưu/Đóng…</template>
 *   </FullscreenDialog>
 * Nội dung **vẫn ở trong DOM khi đóng** để không mất bản nháp đang sửa (đóng không cần hỏi lại; truyền `dirty` nếu đóng làm mất
 * dữ liệu). Esc/×, focus trap, trả focus, khoá cuộn: xem Dialog.
 */
defineProps<{ title: string; subtitle?: string; dirty?: boolean }>();
const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <Dialog v-model:open="open" :title="title" :subtitle="subtitle" :dirty="dirty" size="fullscreen" keep-mounted>
        <slot />
        <template v-if="$slots.actions" #actions><slot name="actions" /></template>
        <template v-if="$slots.footer" #footer><slot name="footer" /></template>
    </Dialog>
</template>
