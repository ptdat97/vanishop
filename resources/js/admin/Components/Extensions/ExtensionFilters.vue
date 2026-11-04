<script setup lang="ts">
import Select from '@admin/Components/Select.vue';
import type { ExtensionFilter } from '@admin/types';

/** Bộ lọc do plugin khai báo; gửi lên dạng ext[<plugin>:<key>] (rỗng = không lọc). */
defineProps<{ filters: ExtensionFilter[] }>();
const values = defineModel<Record<string, string>>({ required: true });

const options = (filter: ExtensionFilter): Array<{ value: string; label: string }> => Object.entries(filter.options).map(([value, label]) => ({ value, label }));
</script>

<template>
    <Select v-for="filter in filters" :key="filter.key" :model-value="values[filter.key] ?? ''" :options="options(filter)" :empty-label="`${filter.label}: tất cả`" :aria-label="filter.label" @update:model-value="values[filter.key] = $event" />
</template>
