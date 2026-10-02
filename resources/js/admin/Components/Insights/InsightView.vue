<script setup lang="ts">
import { computed } from "vue";
import { formatValue, type InsightData } from "./types";

const props = defineProps<{ data: InsightData }>();

const max = computed(() =>
    props.data.kind === "series" ? Math.max(1, ...props.data.values) : 1,
);
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
        <div
            v-if="data.values.length"
            class="flex h-40 items-end gap-1"
            role="img"
            :aria-label="data.label"
        >
            <div
                v-for="(value, index) in data.values"
                :key="index"
                class="group relative flex-1 rounded-t bg-indigo-500/80 hover:bg-indigo-600"
                :style="{ height: `${Math.max(2, (value / max) * 100)}%` }"
                :title="`${data.labels[index]}: ${formatValue(value, data.format)}`"
            />
        </div>
        <div
            v-if="data.labels.length"
            class="mt-1 flex justify-between text-xs text-slate-400"
        >
            <span>{{ data.labels[0] }}</span>
            <span>{{ data.labels[data.labels.length - 1] }}</span>
        </div>
    </div>

    <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th
                        v-for="column in data.columns"
                        :key="column.key"
                        class="py-2 pr-3 font-medium"
                        :class="column.format === 'text' ? '' : 'text-right'"
                    >
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, index) in data.rows"
                    :key="index"
                    class="border-b border-slate-100"
                >
                    <td
                        v-for="column in data.columns"
                        :key="column.key"
                        class="py-2 pr-3"
                        :class="
                            column.format === 'text'
                                ? ''
                                : 'text-right tabular-nums'
                        "
                    >
                        {{ formatValue(row[column.key], column.format) }}
                    </td>
                </tr>
                <tr v-if="!data.rows.length">
                    <td
                        :colspan="data.columns.length"
                        class="py-4 text-center text-slate-400"
                    >
                        Không có dữ liệu.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
