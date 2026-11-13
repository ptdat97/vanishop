<script setup lang="ts" generic="Row extends object">
import { nextSort, type ListSortState } from '@admin/list';
import type { ExtensionList } from '@admin/types';
import { computed, watch } from 'vue';

export interface DataTableColumn {
    key: string;
    label: string;
    align?: 'left' | 'right';
    /** Class thêm cho ô của cột (độ rộng, font mono…). */
    class?: string;
    /** Bấm tiêu đề để sắp xếp (server phải nhận khoá này — `ListSort` ở PHP). */
    sortable?: boolean;
}

/**
 * Bảng danh sách Admin. Lọc, sắp xếp, phân trang ở server. Nội dung ô: slot `cell-<key>` (mặc định in `row[key]`).
 * `extensions` thêm cột của plugin và ô chọn dòng khi có thao tác hàng loạt (ADR-030 §4.A); `selectable` bật ô chọn cho
 * thao tác hàng loạt của chính trang (slot `bulk` hiện khi đã chọn, nhận `selected` và `clear`). Lựa chọn chỉ giữ các dòng
 * đang hiển thị (đổi trang/lọc → bỏ dòng không còn thấy). Khoá dòng mặc định là `row.id`; bảng không có id truyền `rowKey`.
 * Sắp xếp: cột `sortable` + `:sort` (trạng thái hiện tại) + `@sort` (trạng thái mới; `null` = thứ tự mặc định) —
 * thường qua `useListQuery` (@admin/list). `loading` làm mờ bảng khi đang tải. Trạng thái trống: slot `empty` (mặc định
 * `emptyText`).
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
        /** Ô chọn dòng cho thao tác hàng loạt của trang (không cần thao tác của plugin). */
        selectable?: boolean;
        sort?: ListSortState | null;
        loading?: boolean;
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
        selectable: false,
        sort: null,
        loading: false,
    },
);
const selected = defineModel<number[]>('selected', { default: () => [] });
const emit = defineEmits<{ sort: [sort: ListSortState | null] }>();

const extensionColumns = computed(() => props.extensions?.columns ?? []);
const canSelect = computed(() => props.selectable || (props.extensions?.actions.length ?? 0) > 0);
const span = computed(() => props.columns.length + extensionColumns.value.length + (canSelect.value ? 1 : 0));
const idOf = (row: Row): number => props.extensionId(row);
const allSelected = computed(() => props.rows.length > 0 && props.rows.every((row) => selected.value.includes(idOf(row))));

// Đổi trang/lọc: bỏ khỏi lựa chọn các dòng không còn hiển thị (thao tác hàng loạt không chạm dòng người dùng không thấy).
watch(
    () => props.rows,
    (rows) => {
        const visible = new Set(rows.map(idOf));
        if (selected.value.some((id) => !visible.has(id))) {
            selected.value = selected.value.filter((id) => visible.has(id));
        }
    },
);

function clearSelection(): void {
    selected.value = [];
}

function ariaSort(column: DataTableColumn): 'ascending' | 'descending' | 'none' | undefined {
    if (!column.sortable) return undefined;
    if (props.sort?.key !== column.key) return 'none';
    return props.sort.direction === 'asc' ? 'ascending' : 'descending';
}

function toggleAll(): void {
    selected.value = allSelected.value ? [] : props.rows.map(idOf);
}

function cell(row: Row, key: string): unknown {
    return (row as Record<string, unknown>)[key];
}

function isSelected(row: Row): boolean {
    return canSelect.value && selected.value.includes(idOf(row));
}

function mobileColumnClass(column: DataTableColumn, index: number, row?: Row): string[] {
    const classes: string[] = [];
    if (index === 0) {
        classes.push('max-sm:sticky', canSelect.value ? 'max-sm:left-8' : 'max-sm:left-0', 'max-sm:z-10');
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
    <div>
    <div v-if="$slots.bulk && selected.length" class="mb-2 flex flex-wrap items-center gap-2 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm" data-bulk-bar>
        <span class="font-medium text-indigo-900">Đã chọn {{ selected.length }}</span>
        <button type="button" class="text-indigo-700 hover:underline" @click="clearSelection">Bỏ chọn</button>
        <slot name="bulk" :selected="selected" :clear="clearSelection" />
    </div>
    <div
        class="overflow-x-auto transition-opacity"
        role="region"
        aria-label="Bảng dữ liệu; cuộn ngang để xem thêm cột"
        tabindex="0"
        :aria-busy="loading || undefined"
        :class="[bare ? '' : 'rounded-lg border border-slate-200 bg-white', loading ? 'pointer-events-none opacity-60' : '']"
    >
        <table class="w-full max-sm:min-w-max text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th v-if="canSelect" class="w-8 px-2 py-2 max-sm:sticky max-sm:left-0 max-sm:z-20 bg-slate-50">
                        <input type="checkbox" :checked="allSelected" aria-label="Chọn tất cả trên trang hiện tại" @change="toggleAll" />
                    </th>
                    <th v-for="(column, index) in columns" :key="column.key" scope="col" class="px-4 py-2 font-medium max-sm:bg-slate-50" :class="[column.align === 'right' ? 'text-right' : '', column.class, ...mobileColumnClass(column, index)]" :aria-sort="ariaSort(column)">
                        <button
                            v-if="column.sortable"
                            type="button"
                            class="inline-flex items-center gap-1 rounded hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-600"
                            :class="sort?.key === column.key ? 'text-slate-900' : ''"
                            :title="`Sắp xếp theo ${column.label}`"
                            @click="emit('sort', nextSort(sort, column.key))"
                        >
                            <slot :name="`head-${column.key}`" :column="column">{{ column.label }}</slot>
                            <span aria-hidden="true" class="text-xs">{{ sort?.key === column.key ? (sort.direction === 'asc' ? '▲' : '▼') : '↕' }}</span>
                        </button>
                        <slot v-else :name="`head-${column.key}`" :column="column">{{ column.label }}</slot>
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
                    :aria-selected="canSelect ? isSelected(row) : undefined"
                    @click="handleRowClick($event, row)"
                    @keydown.enter.self="rowClick?.(row)"
                    @keydown.space.self.prevent="rowClick?.(row)"
                >
                    <td v-if="canSelect" class="px-2 py-2 max-sm:sticky max-sm:left-0 max-sm:z-10" :class="isSelected(row) ? 'max-sm:bg-indigo-50' : 'max-sm:bg-white'">
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
                    <td :colspan="span" class="px-4 py-6 text-center text-slate-500"><slot name="empty">{{ emptyText }}</slot></td>
                </tr>
            </tbody>
            <tfoot v-if="$slots.footer" class="border-t border-slate-200 bg-slate-50">
                <slot name="footer" :span="span" />
            </tfoot>
        </table>
    </div>
    </div>
</template>
