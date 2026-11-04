<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import { computed } from 'vue';
import { type InsightData, useFormatValue } from './types';

const props = defineProps<{ data: InsightData }>();
const formatValue = useFormatValue();
const columns = computed(() =>
    props.data.kind === 'table' ? props.data.columns.map((column) => ({ key: column.key, label: column.label, align: column.format === 'text' ? ('left' as const) : ('right' as const), class: column.format === 'text' ? '' : 'tabular-nums' })) : [],
);

const max = computed(() => (props.data.kind === 'series' ? Math.max(1, ...props.data.values) : 1));
</script>

<template>
    <div v-if="data.kind === 'metric'">
        <div class="text-2xl font-semibold">
            {{ formatValue(data.value, data.format) }}
        </div>
        <div v-if="data.hint" class="mt-1 text-xs text-slate-500">
            {{ data.hint }}
        </div>
    </div>

    <div v-else-if="data.kind === 'series'">
        <div v-if="data.values.length" class="flex h-40 items-end gap-1" role="img" :aria-label="data.label">
            <div
                v-for="(value, index) in data.values"
                :key="index"
                class="group relative flex-1 rounded-t bg-indigo-500/80 hover:bg-indigo-600"
                :style="{ height: `${Math.max(2, (value / max) * 100)}%` }"
                :title="`${data.labels[index]}: ${formatValue(value, data.format)}`"
            />
        </div>
        <div v-if="data.labels.length" class="mt-1 flex justify-between text-xs text-slate-400">
            <span>{{ data.labels[0] }}</span>
            <span>{{ data.labels[data.labels.length - 1] }}</span>
        </div>
    </div>

    <DataTable v-else :columns="columns" :rows="data.rows" bare>
        <template v-for="column in data.columns" :key="column.key" #[`cell-${column.key}`]="{ row }">{{ formatValue(row[column.key], column.format) }}</template>
    </DataTable>
</template>
