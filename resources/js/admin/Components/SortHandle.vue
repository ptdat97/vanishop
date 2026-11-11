<script setup lang="ts">
/**
 * Tay nắm kéo-thả của `Sortable`: gắn bằng `v-bind="handle"` (thuộc tính `handle` của slot). Kéo bằng chuột/cảm ứng,
 * hoặc đưa tiêu điểm vào rồi dùng phím mũi tên. `touch-none` để trình duyệt không cuộn trang khi kéo trên cảm ứng.
 */
</script>

<template>
    <button
        type="button"
        class="inline-flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 active:cursor-grabbing disabled:cursor-not-allowed disabled:opacity-40"
    >
        <svg viewBox="0 0 12 18" class="h-4 w-3" fill="currentColor" aria-hidden="true">
            <circle cx="3" cy="3" r="1.5" /><circle cx="9" cy="3" r="1.5" />
            <circle cx="3" cy="9" r="1.5" /><circle cx="9" cy="9" r="1.5" />
            <circle cx="3" cy="15" r="1.5" /><circle cx="9" cy="15" r="1.5" />
        </svg>
    </button>
</template>
