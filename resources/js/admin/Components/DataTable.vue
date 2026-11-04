<script setup lang="ts" generic="Row extends { id: number }">
import type { ExtensionList } from '@admin/types';
import { computed } from 'vue';

export interface DataTableColumn {
    key: string;
    label: string;
    align?: 'left' | 'right';
}

/**
 * Bảng danh sách Admin. Lọc, sắp xếp, phân trang ở server. Nội dung ô: slot `cell-<key>` (mặc định in `row[key]`).
 * `extensions` thêm cột của plugin và ô chọn dòng khi có thao tác hàng loạt (ADR-030 §4.A).
 */
const props = withDefaults(defineProps<{ columns: DataTableColumn[]; rows: Row[]; extensions?: Pick<ExtensionList, 'columns' | 'values' | 'actions'>; emptyText?: string; rowLabel?: (row: Row) => string }>(), {
    extensions: undefined,
    emptyText: 'Không có dữ liệu.',
    rowLabel: (row: Row) => `#${row.id}`,
});
const selected = defineModel<number[]>('selected', { default: () => [] });

const extensionColumns = computed(() => props.extensions?.columns ?? []);
const selectable = computed(() => (props.extensions?.actions.length ?? 0) > 0);
const span = computed(() => props.columns.length + extensionColumns.value.length + (selectable.value ? 1 : 0));
const allSelected = computed(() => props.rows.length > 0 && props.rows.every((row) => selected.value.includes(row.id)));

function toggleAll(): void {
    selected.value = allSelected.value ? [] : props.rows.map((row) => row.id);
}

function cell(row: Row, key: string): unknown {
    return (row as Record<string, unknown>)[key];
}
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th v-if="selectable" class="w-8 px-2 py-2">
                        <input type="checkbox" :checked="allSelected" aria-label="Chọn tất cả" @change="toggleAll" />
                    </th>
                    <th v-for="column in columns" :key="column.key" scope="col" class="px-4 py-2 font-medium" :class="column.align === 'right' ? 'text-right' : ''">{{ column.label }}</th>
                    <th v-for="column in extensionColumns" :key="`ext-${column.key}`" scope="col" class="px-4 py-2 font-medium">{{ column.label }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/60">
                    <td v-if="selectable" class="px-2 py-2"><input v-model="selected" type="checkbox" :value="row.id" :aria-label="`Chọn ${rowLabel(row)}`" /></td>
                    <td v-for="column in columns" :key="column.key" class="px-4 py-2" :class="column.align === 'right' ? 'text-right' : ''">
                        <slot :name="`cell-${column.key}`" :row="row">{{ cell(row, column.key) }}</slot>
                    </td>
                    <td v-for="column in extensionColumns" :key="`ext-${column.key}`" class="px-4 py-2">{{ extensions?.values[row.id]?.[column.key] ?? '—' }}</td>
                </tr>
                <tr v-if="!rows.length">
                    <td :colspan="span" class="px-4 py-6 text-center text-slate-500">{{ emptyText }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
