<script setup lang="ts">
import { inputClass } from '@admin/styles';
import type { ExtensionFilter } from '@admin/types';

/** Bộ lọc do plugin khai báo; gửi lên dạng ext[<plugin>:<key>]. */
defineProps<{ filters: ExtensionFilter[] }>();
const values = defineModel<Record<string, string>>({ required: true });
</script>

<template>
    <select v-for="filter in filters" :key="filter.key" v-model="values[filter.key]" :class="inputClass" :aria-label="filter.label">
        <option :value="undefined">{{ filter.label }}: tất cả</option>
        <option v-for="(label, value) in filter.options" :key="value" :value="value">{{ label }}</option>
    </select>
</template>
