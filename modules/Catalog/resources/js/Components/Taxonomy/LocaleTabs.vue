<script setup lang="ts">
import type { StoreLocaleOption } from '@admin/types';

/** Chọn ngôn ngữ nội dung đang sửa; ẩn khi cửa hàng chỉ có một ngôn ngữ. */
defineProps<{ locales: StoreLocaleOption[] }>();
const locale = defineModel<string>({ required: true });
</script>

<template>
    <div v-if="locales.length > 1" class="flex gap-2 text-sm" data-locale-tabs>
        <button v-for="option in locales" :key="option.code" type="button" class="rounded px-2 py-1" :class="locale === option.code ? 'bg-slate-900 text-white' : 'bg-slate-100'" @click="locale = option.code">
            {{ option.label }}
        </button>
    </div>
</template>
