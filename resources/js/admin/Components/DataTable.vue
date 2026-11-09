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
        extensions?: Pick<ExtensionList, 'columns' | 'values' | 'actions' | 'editValues' | 'urls'>;
        emptyText?: string;
        rowKey?: (row: Row, index: number) => string | number;
        rowLabel?: (row: Row) => string;
        rowClass?: (row: Row) => string | undefined;
        rowClick?: (row: Row) => void;
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
        rowClick: undefined,
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

function isSelected(row: Row): boolean {
    return selectable.value && selected.value.includes(idOf(row));
}

function mobileColumnClass(column: DataTableColumn, index: number, row?: Row): string[] {
    const classes: string[] = [];
    if (index === 0) {
        classes.push('max-sm:sticky', selectable.value ? 'max-sm:left-8' : 'max-sm:left-0', 'max-sm:z-10');
    } else if (column.key === 'actions') {
        classes.push('max-sm:sticky', 'max-sm:right-0', 'max-sm:z-10');
    } else {
        return classes;
    }

    classes.push(row && isSelected(row) ? 'max-sm:bg-indigo-50' : 'max-sm:bg-white');
    return classes;
}

function handleRowClick(event: MouseEvent, row: Row): void {
    if (!props.rowClick) {
        return;
    }

    if (event.target instanceof Element && event.target.closest('a, button, input, select, textarea, form, label, [role="button"], [role="link"], [contenteditable="true"]')) {
        return;
    }

    props.rowClick(row);
}
</script>

<template>
    <div class="overflow-x-auto" role="region" aria-label="Bảng dữ liệu; cuộn ngang để xem thêm cột" tabindex="0" :class="bare ? '' : 'rounded-lg border border-slate-200 bg-white'">
        <table class="w-full max-sm:min-w-max text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th v-if="selectable" class="w-8 px-2 py-2 max-sm:sticky max-sm:left-0 max-sm:z-20 bg-slate-50">
                        <input type="checkbox" :checked="allSelected" aria-label="Chọn tất cả trên trang hiện tại" @change="toggleAll" />
                    </th>
                    <th v-for="(column, index) in columns" :key="column.key" scope="col" class="px-4 py-2 font-medium max-sm:bg-slate-50" :class="[column.align === 'right' ? 'text-right' : '', column.class, ...mobileColumnClass(column, index)]">
                        <slot :name="`head-${column.key}`" :column="column">{{ column.label }}</slot>
                    </th>
                    <th v-for="column in extensionColumns" :key="`ext-${column.key}`" scope="col" class="px-4 py-2 font-medium">{{ column.label }}</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(row, index) in rows" :key="rowKey(row, index)">
                <tr
                    class="border-t border-slate-100 hover:bg-slate-50/60"
                    :class="[rowClass?.(row), isSelected(row) ? 'bg-indigo-50 hover:bg-indigo-50' : '', rowClick ? 'cursor-pointer focus-visible:outline-2 focus-visible:outline-indigo-600 focus-visible:bg-indigo-50' : '']"
                    :tabindex="rowClick ? 0 : undefined"
                    :aria-label="rowClick ? `Mở ${rowLabel(row)}` : undefined"
                    :aria-selected="selectable ? isSelected(row) : undefined"
                    @click="handleRowClick($event, row)"
                    @keydown.enter.self="rowClick?.(row)"
                    @keydown.space.self.prevent="rowClick?.(row)"
                >
                    <td v-if="selectable" class="px-2 py-2 max-sm:sticky max-sm:left-0 max-sm:z-10" :class="isSelected(row) ? 'max-sm:bg-indigo-50' : 'max-sm:bg-white'">
                        <input v-model="selected" class="focus-visible:outline-2 focus-visible:outline-indigo-600" type="checkbox" :value="idOf(row)" :aria-label="`Chọn ${rowLabel(row)}`" />
                    </td>
                    <td v-for="(column, columnIndex) in columns" :key="column.key" class="px-4 py-2" :class="[column.align === 'right' ? 'text-right' : '', column.class, ...mobileColumnClass(column, columnIndex, row)]">
                        <slot :name="`cell-${column.key}`" :row="row" :index="index">{{ cell(row, column.key) }}</slot>
                    </td>
                    <td v-for="column in extensionColumns" :key="`ext-${column.key}`" class="px-4 py-2 whitespace-pre-line">
                        <slot
                            :name="`cell-${column.key}`"
                            :row="row"
                            :index="index"
                            :value="extensions?.values[idOf(row)]?.[column.key] ?? '—'"
                            :edit-value="extensions?.editValues?.[idOf(row)]?.[column.key] ?? null"
                            :url="extensions?.urls?.[idOf(row)]?.[column.key]"
                        >
                            {{ extensions?.values[idOf(row)]?.[column.key] ?? '—' }}
                        </slot>
                    </td>
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
