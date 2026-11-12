<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import Select from '@admin/Components/Select.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

/**
 * Ma trận biến thể màu × size (docs/03-domains/variant-matrix.md §3): bấm ô để bật/tắt SKU, sửa mọi SKU trong một bảng (kể cả
 * cột do module/plugin góp: giá, tồn…), áp hàng loạt theo màu/size, lưu cả lưới một lần.
 */
type FieldValue = string | number | boolean | null;
interface Field { key: string; label: string; type: string; required: boolean; options: Record<string, string>; help: string | null }
interface Column { code: string; label: string; editable: boolean; fields: Field[] }
interface Color { id: number; code: string; name: string | null; hex: string | null; images: Array<{ id: number; url: string }> }
interface Size { id: number; code: string; system: string }
interface Variant { id: number; sku: string; barcode: string | null; status: string; weight_gram: number | null; style_color_id: number; size_id: number }
interface Draft {
    variantId: number | null;
    enabled: boolean;
    sku: string;
    barcode: string | null;
    weight_gram: number | null;
    status: string;
    fields: Record<string, Record<string, FieldValue>>;
}

const props = defineProps<{
    saveUrl: string;
    styleCode: string;
    colors: Color[];
    variants: Variant[];
    sizes: Size[];
    columns: Column[];
    values: Record<number, Record<string, Record<string, FieldValue>>>;
    statusLabels: Record<string, string>;
}>();

const page = usePage<{ errors: Record<string, string> }>();
const errors = computed(() => page.props.errors ?? {});
const key = (colorId: number, sizeId: number): string => `${colorId}:${sizeId}`;
const sizeById = computed(() => new Map(props.sizes.map((size) => [size.id, size])));
const sizeOrder = (id: number): number => props.sizes.findIndex((size) => size.id === id);

const order = ref<number[]>([]);
const activeSizes = ref<number[]>([]);
const drafts = reactive<Record<string, Draft>>({});
const dirty = ref(false);
let submitted: string[] = [];
let initializing = false;

function init(): void {
    initializing = true;
    order.value = props.colors.map((color) => color.id);
    activeSizes.value = [...new Set(props.variants.map((variant) => variant.size_id))].sort((a, b) => sizeOrder(a) - sizeOrder(b));
    Object.keys(drafts).forEach((existing) => delete drafts[existing]);
    for (const variant of props.variants) {
        drafts[key(variant.style_color_id, variant.size_id)] = {
            variantId: variant.id, enabled: true, sku: variant.sku, barcode: variant.barcode, weight_gram: variant.weight_gram, status: variant.status,
            fields: JSON.parse(JSON.stringify(props.values[variant.id] ?? {})),
        };
    }
    dirty.value = false;
    // Thay đổi do nạp lại dữ liệu từ server không tính là "chưa lưu".
    nextTick(() => (initializing = false));
}
init();
watch(() => [props.variants, props.colors, props.values], init);
watch([drafts, order, activeSizes], () => {
    if (!initializing) dirty.value = true;
}, { deep: true });

const orderedColors = computed(() => order.value.map((id) => props.colors.find((color) => color.id === id)).filter((color): color is Color => color !== undefined));
const columns = computed(() => activeSizes.value.map((id) => sizeById.value.get(id)).filter((size): size is Size => size !== undefined));
const addableSizes = computed(() => {
    const systems = new Set(columns.value.map((size) => size.system));
    return props.sizes.filter((size) => !activeSizes.value.includes(size.id) && (systems.size === 0 || systems.has(size.system)));
});
const newSizeId = ref<number | null>(null);

function addSize(): void {
    if (newSizeId.value && !activeSizes.value.includes(newSizeId.value)) {
        activeSizes.value = [...activeSizes.value, newSizeId.value].sort((a, b) => sizeOrder(a) - sizeOrder(b));
        // Cột mới: bật sẵn cho mọi màu (thường dùng nhất); tắt ô không cần.
        orderedColors.value.forEach((color) => toggle(color, newSizeId.value!, true));
    }
    newSizeId.value = null;
}

async function removeSize(size: Size): Promise<void> {
    if ((await confirmAction({ title: `Bỏ cột size ${size.code}?`, message: 'Các SKU của size này sẽ bị tắt khi lưu (chưa phát sinh → xoá, đã phát sinh → ngừng bán).', confirmLabel: 'Bỏ cột', tone: 'danger' })) === false) {
        return;
    }
    orderedColors.value.forEach((color) => {
        const draft = drafts[key(color.id, size.id)];
        if (draft) draft.enabled = false;
    });
    activeSizes.value = activeSizes.value.filter((id) => id !== size.id);
}

function toggle(color: Color, sizeId: number, force?: boolean): void {
    const cell = drafts[key(color.id, sizeId)];
    if (cell) {
        cell.enabled = force ?? !cell.enabled;
        return;
    }
    if (force === false) return;
    const size = sizeById.value.get(sizeId);
    drafts[key(color.id, sizeId)] = {
        variantId: null, enabled: true, sku: `${props.styleCode}-${color.code}-${size?.code ?? sizeId}`.toUpperCase(), barcode: null, weight_gram: null, status: 'active', fields: {},
    };
}

/** Kéo-thả hàng màu (Sortable chung — có hỗ trợ bàn phím) đổi thứ tự hiển thị màu; lưu cùng nút "Lưu biến thể". */
const sortableColors = computed({
    get: () => orderedColors.value,
    set: (colors: Color[]) => (order.value = colors.map((color) => color.id)),
});
const gridStyle = computed(() => ({ gridTemplateColumns: `minmax(14rem, auto) repeat(${columns.value.length}, 4.5rem) minmax(11rem, auto)` }));

/** Dòng của bảng chi tiết: ô đang bật, theo thứ tự màu → size. */
interface Row { color: Color; size: Size; key: string; draft: Draft }
const rows = computed(() => orderedColors.value.flatMap((color) => columns.value
    .map((size) => ({ color, size, key: key(color.id, size.id), draft: drafts[key(color.id, size.id)] }))
    .filter((row): row is Row => row.draft !== undefined && row.draft.enabled)));
const fieldColumns = computed(() => props.columns.flatMap((column) => column.fields.map((field) => ({ key: `f:${column.code}.${field.key}`, column, field }))));
const tableColumns = computed(() => [
    { key: 'label', label: 'Màu / size', class: 'whitespace-nowrap' },
    { key: 'sku', label: 'SKU' },
    { key: 'barcode', label: 'Barcode' },
    { key: 'weight_gram', label: 'Khối lượng (g)' },
    { key: 'status', label: 'Trạng thái' },
    ...fieldColumns.value.map(({ key: columnKey, column, field }) => ({ key: columnKey, label: column.editable ? field.label : `${field.label} (chỉ xem)` })),
]);

function fieldValue(draft: Draft, column: Column, field: Field): FieldValue {
    return draft.fields[column.code]?.[field.key] ?? null;
}

function setField(draft: Draft, column: Column, field: Field, value: FieldValue): void {
    draft.fields[column.code] = { ...(draft.fields[column.code] ?? {}), [field.key]: value === '' ? null : value };
}

// Áp hàng loạt cho các SKU khớp màu/size (0 = tất cả) trong bản nháp.
const batch = reactive<{ colorId: number; sizeId: number; target: string; value: FieldValue }>({ colorId: 0, sizeId: 0, target: '', value: null });
const batchTargets = computed(() => [
    { value: 'status', label: 'Trạng thái' },
    { value: 'weight_gram', label: 'Khối lượng (g)' },
    ...props.columns.filter((column) => column.editable).flatMap((column) => column.fields.map((field) => ({ value: `${column.code}.${field.key}`, label: `${column.label}: ${field.label}` }))),
]);

function applyBatch(): void {
    if (!batch.target) return;
    for (const row of rows.value) {
        if ((batch.colorId && row.color.id !== batch.colorId) || (batch.sizeId && row.size.id !== batch.sizeId)) continue;
        if (batch.target === 'status') row.draft.status = String(batch.value ?? 'active');
        else if (batch.target === 'weight_gram') row.draft.weight_gram = batch.value === null || batch.value === '' ? null : Number(batch.value);
        else {
            const [code, field] = batch.target.split('.', 2);
            row.draft.fields[code] = { ...(row.draft.fields[code] ?? {}), [field]: batch.value === '' ? null : batch.value };
        }
    }
}

function save(): void {
    submitted = [];
    const cells: Array<{ style_color_id: number; size_id: number; enabled: boolean; status: string; sku: string; barcode: string | null; weight_gram: number | null; fields: Record<string, Record<string, FieldValue>> }> = [];
    for (const color of orderedColors.value) {
        for (const sizeId of new Set([...activeSizes.value, ...props.variants.filter((variant) => variant.style_color_id === color.id).map((variant) => variant.size_id)])) {
            const draft = drafts[key(color.id, sizeId)];
            if (!draft || (!draft.enabled && draft.variantId === null)) continue;
            submitted.push(key(color.id, sizeId));
            cells.push({
                style_color_id: color.id, size_id: sizeId, enabled: draft.enabled, status: draft.status, sku: draft.sku, barcode: draft.barcode, weight_gram: draft.weight_gram,
                fields: Object.fromEntries(props.columns.filter((column) => column.editable).map((column) => [column.code, draft.fields[column.code] ?? {}])),
            });
        }
    }
    router.put(props.saveUrl, { color_order: order.value, cells }, { preserveScroll: true, onSuccess: () => (dirty.value = false) });
}

/** Lỗi của ô: `cells.<n>.<trường>` theo thứ tự đã gửi; lỗi của cột góp vào: `variant.<id>`. */
function rowError(rowKey: string, variantId: number | null): string | null {
    const index = submitted.indexOf(rowKey);
    const own = index < 0 ? [] : Object.entries(errors.value).filter(([field]) => field.startsWith(`cells.${index}.`)).map(([, message]) => message);
    const provided = variantId === null ? undefined : errors.value[`variant.${variantId}`];

    return [...own, ...(provided ? [provided] : [])].join(' ') || null;
}

// Rời trang khi còn thay đổi chưa lưu → hỏi lại.
const beforeUnload = (event: BeforeUnloadEvent): void => {
    if (dirty.value) event.preventDefault();
};
let removeRouterGuard: (() => void) | null = null;
let leaving = false;
onMounted(() => {
    window.addEventListener('beforeunload', beforeUnload);
    removeRouterGuard = router.on('before', (event) => {
        if (dirty.value && event.detail.visit.method === 'get' && !leaving) {
            confirmAction({ title: 'Rời trang khi ma trận biến thể chưa lưu?', message: 'Các thay đổi chưa lưu sẽ mất.', confirmLabel: 'Rời trang', tone: 'danger' }).then((ok) => {
                if (ok !== false) {
                    leaving = true;
                    router.visit(event.detail.visit.url);
                }
            });
            return false;
        }
    });
});
onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnload);
    removeRouterGuard?.();
});

const colorOptions = computed(() => [{ value: 0, label: 'Mọi màu' }, ...orderedColors.value.map((color) => ({ value: color.id, label: color.name ?? color.code }))]);
const sizeOptions = computed(() => [{ value: 0, label: 'Mọi size' }, ...columns.value.map((size) => ({ value: size.id, label: size.code }))]);
const enabledCount = computed(() => rows.value.length);
</script>

<template>
    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="variant-matrix-title">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 id="variant-matrix-title" class="text-sm font-medium">Biến thể (màu × size)</h2>
                <p class="text-xs text-slate-500">Bấm ô để bật/tắt SKU. Tắt SKU đã có: chưa bán/chưa có tồn → xoá, đã phát sinh → ngừng bán.</p>
            </div>
            <div class="flex items-center gap-2">
                <span v-if="dirty" class="text-xs text-amber-700">Chưa lưu</span>
                <button type="button" :class="primaryButton" :disabled="!dirty" @click="save">Lưu biến thể</button>
            </div>
        </div>

        <p v-if="!colors.length" class="text-sm text-slate-500">Thêm màu ở khối "Màu và ảnh" trước.</p>
        <div v-else class="overflow-x-auto text-sm">
            <div class="grid items-center gap-1" :style="gridStyle">
                <span class="p-2 text-xs font-medium text-slate-500">Màu (kéo để đổi thứ tự)</span>
                <span v-for="size in columns" :key="size.id" class="p-2 text-center font-medium">
                    {{ size.code }}
                    <button type="button" class="ml-1 text-xs text-slate-400 hover:text-red-600" :aria-label="`Bỏ cột size ${size.code}`" @click="removeSize(size)">✕</button>
                </span>
                <span class="flex items-center gap-1 p-1">
                    <span class="w-28"><Select v-model="newSizeId" :options="addableSizes.map((size) => ({ value: size.id, label: `${size.code} (${size.system})` }))" placeholder="+ Size" aria-label="Thêm cột size" /></span>
                    <button type="button" :class="secondaryButton" :disabled="!newSizeId" @click="addSize">Thêm</button>
                </span>
            </div>
            <Sortable v-slot="{ item: color, handle }" v-model="sortableColors" :item-key="(color) => color.id" :item-label="(color) => `màu ${color.name ?? color.code}`">
                <div class="grid items-center gap-1 border-t border-slate-100" :style="gridStyle">
                    <div class="flex items-center gap-2 p-2">
                        <SortHandle v-bind="handle" />
                        <span class="inline-block h-5 w-5 rounded-full border border-slate-300" :style="{ background: color.hex ?? 'transparent' }" />
                        <span>{{ color.name ?? color.code }}</span>
                        <span class="text-xs text-slate-400">{{ color.images.length }} ảnh</span>
                    </div>
                    <div v-for="size in columns" :key="size.id" class="p-1 text-center">
                        <button
                            type="button"
                            class="h-9 w-16 rounded border text-xs"
                            :class="drafts[`${color.id}:${size.id}`]?.enabled
                                ? (drafts[`${color.id}:${size.id}`].status === 'active' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-400 bg-slate-100 text-slate-500')
                                : 'border-dashed border-slate-300 text-slate-300'"
                            :aria-pressed="Boolean(drafts[`${color.id}:${size.id}`]?.enabled)"
                            :aria-label="`${color.name ?? color.code} ${size.code}`"
                            @click="toggle(color, size.id)"
                        >
                            {{ drafts[`${color.id}:${size.id}`]?.enabled ? (drafts[`${color.id}:${size.id}`].status === 'active' ? 'Bán' : 'Ngừng') : '+' }}
                        </button>
                    </div>
                    <span />
                </div>
            </Sortable>
        </div>

        <div v-if="enabledCount" class="flex flex-wrap items-end gap-2 rounded-md bg-slate-50 p-3 text-sm">
            <span class="w-full text-xs text-slate-500">Áp hàng loạt cho các SKU khớp (chưa lưu cho tới khi bấm "Lưu biến thể")</span>
            <div class="w-36"><Select v-model="batch.colorId" :options="colorOptions" aria-label="Màu áp dụng" /></div>
            <div class="w-28"><Select v-model="batch.sizeId" :options="sizeOptions" aria-label="Size áp dụng" /></div>
            <div class="w-56"><Select v-model="batch.target" :options="batchTargets" placeholder="Trường…" aria-label="Trường cần áp" /></div>
            <div class="w-40">
                <Select v-if="batch.target === 'status'" v-model="batch.value" :options="labelOptions(statusLabels)" aria-label="Giá trị" />
                <input v-else v-model="batch.value" :class="inputClass" aria-label="Giá trị" />
            </div>
            <button type="button" :class="secondaryButton" :disabled="!batch.target" @click="applyBatch">Áp dụng</button>
        </div>

        <DataTable
            v-if="enabledCount"
            :columns="tableColumns"
            :rows="rows"
            :row-key="(row) => row.key"
            :row-label="(row) => row.draft.sku"
            :row-class="() => 'align-top'"
            :expanded="(row) => rowError(row.key, row.draft.variantId) !== null"
            bare
        >
            <template #cell-label="{ row }">{{ row.color.name ?? row.color.code }} / {{ row.size.code }}</template>
            <template #cell-sku="{ row }"><input v-model="row.draft.sku" :class="[inputClass, 'w-44 font-mono text-xs']" :aria-label="`SKU ${row.color.code} ${row.size.code}`" /></template>
            <template #cell-barcode="{ row }"><input v-model="row.draft.barcode" :class="[inputClass, 'w-32 font-mono text-xs']" aria-label="Barcode" /></template>
            <template #cell-weight_gram="{ row }"><input v-model.number="row.draft.weight_gram" type="number" min="0" :class="[inputClass, 'w-24']" aria-label="Khối lượng (g)" /></template>
            <template #cell-status="{ row }"><div class="w-32"><Select v-model="row.draft.status" :options="labelOptions(statusLabels)" aria-label="Trạng thái" /></div></template>
            <template v-for="{ key: columnKey, column, field } in fieldColumns" :key="columnKey" #[`cell-${columnKey}`]="{ row }">
                <input
                    v-if="field.type === 'bool'"
                    type="checkbox"
                    :checked="Boolean(fieldValue(row.draft, column, field))"
                    :disabled="!column.editable"
                    :aria-label="field.label"
                    @change="setField(row.draft, column, field, ($event.target as HTMLInputElement).checked)"
                />
                <div v-else-if="field.type === 'select'" class="w-32">
                    <Select :model-value="fieldValue(row.draft, column, field) as string | null" :options="labelOptions(field.options)" :disabled="!column.editable" :aria-label="field.label" @change="setField(row.draft, column, field, $event)" />
                </div>
                <input
                    v-else
                    :value="fieldValue(row.draft, column, field) ?? ''"
                    :type="field.type === 'int' ? 'number' : 'text'"
                    :class="[inputClass, field.type === 'int' ? 'w-28' : 'w-40']"
                    :disabled="!column.editable"
                    :aria-label="field.label"
                    :title="field.help ?? undefined"
                    @input="setField(row.draft, column, field, field.type === 'int' && ($event.target as HTMLInputElement).value !== '' ? Number(($event.target as HTMLInputElement).value) : ($event.target as HTMLInputElement).value)"
                />
            </template>
            <template #expanded="{ row }"><p class="text-xs text-red-600">{{ rowError(row.key, row.draft.variantId) }}</p></template>
        </DataTable>
    </section>
</template>
