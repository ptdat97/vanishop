<script setup lang="ts" generic="Row extends object">
import type { ExtensionList } from '@admin/types';
import { computed } from 'vue';

export interface DataTableColumn {
    key: string;
    label: string;
    align?: 'left' | 'right';
    /** Class thêm cho ô của cột (độ rộng, font mono…). */
    class?: string;
}

/**
 * Bảng danh sách Admin. Lọc, sắp xếp, phân trang ở server. Nội dung ô: slot `cell-<key>` (mặc định in `row[key]`).
 * `extensions` thêm cột của plugin và ô chọn dòng khi có thao tác hàng loạt (ADR-030 §4.A). Khoá dòng mặc định là
 * `row.id`; bảng không có id (dòng nhập liệu, số liệu) truyền `rowKey`.
 */
const props = withDefaults(
    defineProps<{
        columns: DataTableColumn[];
        rows: Row[];
        extensions?: Pick<ExtensionList, 'columns' | 'values' | 'actions'>;
        emptyText?: string;
        rowKey?: (row: Row, index: number) => string | number;
        rowLabel?: (row: Row) => string;
        rowClass?: (row: Row) => string | undefined;
        /** Id số của dòng cho cột/thao tác của plugin (mặc định `row.id`). */
        extensionId?: (row: Row) => number;
        /** Dòng đang mở chi tiết (slot `expanded`, chiếm cả chiều ngang bảng). */
        expanded?: (row: Row) => boolean;
        /** Không viền/nền ngoài — khi bảng nằm trong khung đã có viền. */
        bare?: boolean;
    }>(),
    {
        extensions: undefined,
        emptyText: 'Không có dữ liệu.',
        rowKey: (row: Row, index: number) => ((row as { id?: number | string }).id ?? index),
        rowLabel: (row: Row) => `#${String((row as { id?: number | string }).id ?? '')}`,
        rowClass: undefined,
        extensionId: (row: Row) => Number((row as { id?: number }).id),
        expanded: undefined,
    },
);
const selected = defineModel<number[]>('selected', { default: () => [] });

const extensionColumns = computed(() => props.extensions?.columns ?? []);
const selectable = computed(() => (props.extensions?.actions.length ?? 0) > 0);
const span = computed(() => props.columns.length + extensionColumns.value.length + (selectable.value ? 1 : 0));
const idOf = (row: Row): number => props.extensionId(row);
const allSelected = computed(() => props.rows.length > 0 && props.rows.every((row) => selected.value.includes(idOf(row))));

function toggleAll(): void {
    selected.value = allSelected.value ? [] : props.rows.map(idOf);
}

function cell(row: Row, key: string): unknown {
    return (row as Record<string, unknown>)[key];
}
</script>

<template>
    <div class="overflow-x-auto" :class="bare ? '' : 'rounded-lg border border-slate-200 bg-white'">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th v-if="selectable" class="w-8 px-2 py-2">
                        <input type="checkbox" :checked="allSelected" aria-label="Chọn tất cả" @change="toggleAll" />
                    </th>
                    <th v-for="column in columns" :key="column.key" scope="col" class="px-4 py-2 font-medium" :class="[column.align === 'right' ? 'text-right' : '', column.class]">
                        <slot :name="`head-${column.key}`" :column="column">{{ column.label }}</slot>
                    </th>
                    <th v-for="column in extensionColumns" :key="`ext-${column.key}`" scope="col" class="px-4 py-2 font-medium">{{ column.label }}</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(row, index) in rows" :key="rowKey(row, index)">
                <tr class="border-t border-slate-100 hover:bg-slate-50/60" :class="rowClass?.(row)">
                    <td v-if="selectable" class="px-2 py-2"><input v-model="selected" type="checkbox" :value="idOf(row)" :aria-label="`Chọn ${rowLabel(row)}`" /></td>
                    <td v-for="column in columns" :key="column.key" class="px-4 py-2" :class="[column.align === 'right' ? 'text-right' : '', column.class]">
                        <slot :name="`cell-${column.key}`" :row="row" :index="index">{{ cell(row, column.key) }}</slot>
                    </td>
                    <td v-for="column in extensionColumns" :key="`ext-${column.key}`" class="px-4 py-2">{{ extensions?.values[idOf(row)]?.[column.key] ?? '—' }}</td>
                </tr>
                <tr v-if="expanded?.(row)" class="border-t border-slate-100 bg-slate-50">
                    <td :colspan="span" class="px-4 py-2"><slot name="expanded" :row="row" /></td>
                </tr>
                </template>
                <tr v-if="!rows.length">
                    <td :colspan="span" class="px-4 py-6 text-center text-slate-500">{{ emptyText }}</td>
                </tr>
            </tbody>
            <tfoot v-if="$slots.footer" class="border-t border-slate-200 bg-slate-50">
                <slot name="footer" :span="span" />
            </tfoot>
        </table>
    </div>
</template>
