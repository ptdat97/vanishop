<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import Select from '@admin/Components/Select.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

/**
 * Ma trận biến thể theo trục tuỳ chọn (ADR-045, docs/03-domains/variant-matrix.md §3): sản phẩm chọn 1–3 loại tuỳ chọn
 * (Màu, Size, Chất liệu…) làm trục; mỗi SKU là một tổ hợp giá trị. 2 trục → lưới, 1 hoặc 3 trục → danh sách tổ hợp. Bấm để
 * bật/tắt SKU, sửa mọi SKU trong một bảng (kể cả cột do module/plugin góp: giá, tồn…), áp hàng loạt, lưu cả lưới một lần.
 * Ảnh theo từng SKU (đã lưu), áp bộ ảnh cho mọi SKU cùng giá trị ở một trục (vd. mọi size cùng màu).
 */
type FieldValue = string | number | boolean | null;
interface Field { key: string; label: string; type: string; required: boolean; options: Record<string, string>; help: string | null }
interface Column { code: string; label: string; editable: boolean; fields: Field[] }
interface OptionValue { id: number; code: string; name: string | null; hex: string | null; group: string | null }
interface OptionType { id: number; code: string; name: string; display_type: string; values: OptionValue[] }
interface Axis { option_type_id: number; value_ids: number[] }
interface Image { id: number; url: string }
interface Variant { id: number; sku: string; barcode: string | null; status: string; weight_gram: number | null; values: number[]; label: string; images: Image[] }
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
    productUrl: string;
    optionTypesUrl: string;
    styleCode: string;
    axes: Axis[];
    optionTypes: OptionType[];
    variants: Variant[];
    columns: Column[];
    values: Record<number, Record<string, Record<string, FieldValue>>>;
    statusLabels: Record<string, string>;
}>();

const MAX_AXES = 3;
const page = usePage<{ errors: Record<string, string> }>();
const errors = computed(() => page.props.errors ?? {});
const typeById = computed(() => new Map(props.optionTypes.map((type) => [type.id, type])));
const valueById = computed(() => new Map(props.optionTypes.flatMap((type) => type.values.map((value) => [value.id, value] as const))));
const typeName = (typeId: number): string => typeById.value.get(typeId)?.name ?? String(typeId);
const valueName = (valueId: number): string => {
    const value = valueById.value.get(valueId);
    return value ? (value.name ?? value.code) : String(valueId);
};
const isSwatch = (typeId: number): boolean => typeById.value.get(typeId)?.display_type === 'swatch';
/** Đã có SKU thì không đổi được tập loại tuỳ chọn (server cũng chặn); vẫn đổi được thứ tự trục, thêm/bớt giá trị chưa dùng. */
const locked = computed(() => props.variants.length > 0);

const axes = ref<Axis[]>([]);
const drafts = reactive<Record<string, Draft>>({});
const dirty = ref(false);
let submitted: string[] = [];
let initializing = false;

/** Khoá tổ hợp không phụ thuộc thứ tự trục: "loại:giá trị|…" theo id loại. */
function comboKey(valueIds: number[], axisList: Axis[] = axes.value): string {
    return axisList.map((axis, index) => [axis.option_type_id, valueIds[index]] as const)
        .sort((a, b) => a[0] - b[0]).map(([type, value]) => `${type}:${value}`).join('|');
}

function init(): void {
    initializing = true;
    axes.value = props.axes.map((axis) => ({ option_type_id: axis.option_type_id, value_ids: [...axis.value_ids] }));
    Object.keys(drafts).forEach((existing) => delete drafts[existing]);
    for (const variant of props.variants) {
        drafts[comboKey(variant.values, props.axes)] = {
            variantId: variant.id, enabled: true, sku: variant.sku, barcode: variant.barcode, weight_gram: variant.weight_gram, status: variant.status,
            fields: JSON.parse(JSON.stringify(props.values[variant.id] ?? {})),
        };
    }
    dirty.value = false;
    // Thay đổi do nạp lại dữ liệu từ server không tính là "chưa lưu".
    nextTick(() => (initializing = false));
}
init();
// Ảnh đổi (tải lên, sắp xếp) không nạp lại bản nháp — chỉ khi trục, SKU hay giá trị cột đổi.
const signature = computed(() => JSON.stringify([props.axes, props.variants.map((v) => [v.id, v.sku, v.barcode, v.status, v.weight_gram, v.values]), props.values]));
watch(signature, init);
watch([drafts, axes], () => {
    if (!initializing) dirty.value = true;
}, { deep: true });

/** Mọi tổ hợp của các trục hiện tại (id giá trị theo thứ tự trục). */
const combos = computed<number[][]>(() => axes.value.length === 0
    ? []
    : axes.value.reduce<number[][]>((acc, axis) => acc.flatMap((prefix) => axis.value_ids.map((valueId) => [...prefix, valueId])), [[]]));
const comboLabel = (values: number[]): string => values.map(valueName).join(' / ');
const draftOf = (values: number[]): Draft | undefined => drafts[comboKey(values)];

function toggle(values: number[], force?: boolean): void {
    const existing = draftOf(values);
    if (existing) {
        existing.enabled = force ?? !existing.enabled;
        return;
    }
    if (force === false) return;
    drafts[comboKey(values)] = {
        variantId: null, enabled: true, sku: [props.styleCode, ...values.map((id) => valueById.value.get(id)?.code ?? id)].join('-').toUpperCase(),
        barcode: null, weight_gram: null, status: 'active', fields: {},
    };
}

function enableAll(): void {
    combos.value.forEach((values) => toggle(values, true));
}

// Trục và giá trị
const usedValueIds = computed(() => new Set(props.variants.flatMap((variant) => variant.values)));
const addableTypes = computed(() => props.optionTypes.filter((type) => !axes.value.some((axis) => axis.option_type_id === type.id)).map((type) => ({ value: type.id, label: type.name })));
const newTypeId = ref<number | null>(null);
const newValue = reactive<Record<number, number | null>>({});

function addAxis(): void {
    if (newTypeId.value && axes.value.length < MAX_AXES) {
        axes.value = [...axes.value, { option_type_id: newTypeId.value, value_ids: [] }];
    }
    newTypeId.value = null;
}

function removeAxis(axis: Axis): void {
    axes.value = axes.value.filter((item) => item !== axis);
}

function addableValues(axis: Axis): Array<{ value: number; label: string }> {
    return (typeById.value.get(axis.option_type_id)?.values ?? [])
        .filter((value) => !axis.value_ids.includes(value.id))
        .map((value) => ({ value: value.id, label: value.group ? `${value.name ?? value.code} (${value.group})` : (value.name ?? value.code) }));
}

function addValue(axis: Axis): void {
    const valueId = newValue[axis.option_type_id];
    if (valueId && !axis.value_ids.includes(valueId)) {
        axis.value_ids.push(valueId);
        // Giá trị mới: bật sẵn mọi tổ hợp chứa nó (thường dùng nhất); tắt ô không cần.
        const position = axes.value.indexOf(axis);
        combos.value.filter((values) => values[position] === valueId).forEach((values) => toggle(values, true));
    }
    newValue[axis.option_type_id] = null;
}

async function removeValue(axis: Axis, valueId: number): Promise<void> {
    if ((await confirmAction({ title: `Bỏ giá trị ${valueName(valueId)}?`, message: 'Các ô chưa lưu của giá trị này bị bỏ.', confirmLabel: 'Bỏ', tone: 'danger' })) !== false) {
        axis.value_ids = axis.value_ids.filter((id) => id !== valueId);
    }
}

// Lưới (2 trục)
const gridStyle = computed(() => ({ gridTemplateColumns: `minmax(10rem, auto) repeat(${axes.value[1]?.value_ids.length ?? 0}, 4.5rem)` }));
const cellClass = (draft: Draft | undefined): string => draft?.enabled
    ? (draft.status === 'active' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-400 bg-slate-100 text-slate-500')
    : 'border-dashed border-slate-300 text-slate-400';
const cellText = (draft: Draft | undefined): string => (draft?.enabled ? (draft.status === 'active' ? 'Bán' : 'Ngừng') : '+');

/** Dòng của bảng chi tiết: tổ hợp đang bật, theo thứ tự trục. */
interface Row { values: number[]; key: string; label: string; draft: Draft }
const rows = computed(() => combos.value
    .map((values) => ({ values, key: comboKey(values), label: comboLabel(values), draft: draftOf(values) }))
    .filter((row): row is Row => row.draft !== undefined && row.draft.enabled));
const fieldColumns = computed(() => props.columns.flatMap((column) => column.fields.map((field) => ({ key: `f:${column.code}.${field.key}`, column, field }))));
const tableColumns = computed(() => [
    { key: 'label', label: axes.value.map((axis) => typeName(axis.option_type_id)).join(' / '), class: 'whitespace-nowrap' },
    { key: 'images', label: 'Ảnh' },
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

// Áp hàng loạt cho các SKU khớp giá trị từng trục (0 = tất cả) trong bản nháp.
const batch = reactive<{ filter: Record<number, number>; target: string; value: FieldValue }>({ filter: {}, target: '', value: null });
const batchTargets = computed(() => [
    { value: 'status', label: 'Trạng thái' },
    { value: 'weight_gram', label: 'Khối lượng (g)' },
    ...props.columns.filter((column) => column.editable).flatMap((column) => column.fields.map((field) => ({ value: `${column.code}.${field.key}`, label: `${column.label}: ${field.label}` }))),
]);
const batchOptions = (axis: Axis) => [{ value: 0, label: `Mọi ${typeName(axis.option_type_id).toLowerCase()}` }, ...axis.value_ids.map((id) => ({ value: id, label: valueName(id) }))];

function applyBatch(): void {
    if (!batch.target) return;
    for (const row of rows.value) {
        if (axes.value.some((axis, index) => (batch.filter[axis.option_type_id] ?? 0) !== 0 && row.values[index] !== batch.filter[axis.option_type_id])) continue;
        if (batch.target === 'status') row.draft.status = String(batch.value ?? 'active');
        else if (batch.target === 'weight_gram') row.draft.weight_gram = batch.value === null || batch.value === '' ? null : Number(batch.value);
        else {
            const [code, field] = batch.target.split('.', 2);
            row.draft.fields[code] = { ...(row.draft.fields[code] ?? {}), [field]: batch.value === '' ? null : batch.value };
        }
    }
}

const canSave = computed(() => dirty.value && axes.value.length > 0 && axes.value.every((axis) => axis.value_ids.length > 0));

function save(): void {
    submitted = [];
    const cells: Array<{ values: number[]; enabled: boolean; status: string; sku: string; barcode: string | null; weight_gram: number | null; fields: Record<string, Record<string, FieldValue>> }> = [];
    for (const values of combos.value) {
        const draft = draftOf(values);
        if (!draft || (!draft.enabled && draft.variantId === null)) continue;
        submitted.push(comboKey(values));
        cells.push({
            values, enabled: draft.enabled, status: draft.status, sku: draft.sku, barcode: draft.barcode, weight_gram: draft.weight_gram,
            fields: Object.fromEntries(props.columns.filter((column) => column.editable).map((column) => [column.code, draft.fields[column.code] ?? {}])),
        });
    }
    router.put(props.saveUrl, { axes: axes.value, cells }, { preserveScroll: true, onSuccess: () => (dirty.value = false) });
}

/** Lỗi của ô: `cells.<n>.<trường>` theo thứ tự đã gửi; lỗi của cột góp vào: `variant.<id>`. */
function rowError(rowKey: string, variantId: number | null): string | null {
    const index = submitted.indexOf(rowKey);
    const own = index < 0 ? [] : Object.entries(errors.value).filter(([field]) => field.startsWith(`cells.${index}.`)).map(([, message]) => message);
    const provided = variantId === null ? undefined : errors.value[`variant.${variantId}`];

    return [...own, ...(provided ? [provided] : [])].join(' ') || null;
}
const axisErrors = computed(() => Object.entries(errors.value).filter(([field]) => field === 'axes' || field.startsWith('axes.')).map(([, message]) => message));

// Ảnh theo SKU (chỉ SKU đã lưu).
const variantById = computed(() => new Map(props.variants.map((variant) => [variant.id, variant])));
const imageVariantId = ref<number | null>(null);
const imageVariant = computed(() => (imageVariantId.value ? variantById.value.get(imageVariantId.value) ?? null : null));
const imagesUrl = (variant: Variant): string => `${props.productUrl}/variants/${variant.id}/images`;
const picking = ref(false);

function uploadImages(variant: Variant, event: Event): void {
    const files = Array.from((event.target as HTMLInputElement).files ?? []);
    if (files.length) {
        router.post(imagesUrl(variant), { images: files }, { forceFormData: true, preserveScroll: true });
    }
}

function attachFromLibrary(items: MediaItem[]): void {
    if (imageVariant.value && items.length) {
        router.post(`${imagesUrl(imageVariant.value)}/library`, { media_ids: items.map((item) => item.id) }, { preserveScroll: true });
    }
    picking.value = false;
}

/** Kéo-thả xong lưu ngay thứ tự ảnh (ảnh đầu là ảnh đại diện của SKU). */
function reorderImages(variant: Variant, images: Image[]): void {
    router.put(`${imagesUrl(variant)}/order`, { order: images.map((image) => image.id) }, { preserveScroll: true });
}

function removeImage(variant: Variant, imageId: number): void {
    router.delete(`${imagesUrl(variant)}/${imageId}`, { preserveScroll: true });
}

async function copyImages(variant: Variant, typeId: number, valueId: number): Promise<void> {
    const message = `Mọi SKU khác có ${typeName(typeId).toLowerCase()} ${valueName(valueId)} sẽ dùng đúng bộ ảnh này (thay ảnh đang có).`;
    if ((await confirmAction({ title: 'Áp bộ ảnh cho các SKU cùng giá trị?', message, confirmLabel: 'Áp dụng' })) !== false) {
        router.post(`${imagesUrl(variant)}/copy`, { option_type_id: typeId }, { preserveScroll: true });
    }
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

const enabledCount = computed(() => rows.value.length);
</script>

<template>
    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="variant-matrix-title">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 id="variant-matrix-title" class="text-sm font-medium">Biến thể</h2>
                <p class="text-xs text-slate-500">Chọn 1–3 loại tuỳ chọn làm trục, bấm tổ hợp để bật/tắt SKU. Tắt SKU đã có: chưa bán/chưa có tồn → xoá, đã phát sinh → ngừng bán.</p>
            </div>
            <div class="flex items-center gap-2">
                <span v-if="dirty" class="text-xs text-amber-700">Chưa lưu</span>
                <button type="button" :class="primaryButton" :disabled="!canSave" @click="save">Lưu biến thể</button>
            </div>
        </div>

        <!-- Trục: loại tuỳ chọn (kéo để đổi thứ tự) và giá trị sản phẩm dùng (kéo để đổi thứ tự hiển thị) -->
        <div class="space-y-2">
            <Sortable v-slot="{ item: axis, handle }" v-model="axes" :item-key="(axis) => axis.option_type_id" :item-label="(axis) => `trục ${typeName(axis.option_type_id)}`">
                <div class="flex flex-wrap items-center gap-2 rounded-md border border-slate-200 p-2 text-sm">
                    <SortHandle v-bind="handle" />
                    <span class="w-24 font-medium">{{ typeName(axis.option_type_id) }}</span>
                    <Sortable
                        v-slot="{ item: valueId, handle: valueHandle }"
                        :model-value="axis.value_ids"
                        layout="grid"
                        list-class="flex flex-wrap gap-1"
                        :item-key="(valueId) => valueId"
                        :item-label="(valueId) => valueName(valueId)"
                        @update:model-value="(ids) => (axis.value_ids = ids)"
                    >
                        <span class="flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 py-0.5 pl-1 pr-2 text-xs">
                            <SortHandle v-bind="valueHandle" />
                            <span v-if="isSwatch(axis.option_type_id)" class="inline-block h-3.5 w-3.5 rounded-full border border-slate-300" :style="{ background: valueById.get(valueId)?.hex ?? 'transparent' }" />
                            {{ valueName(valueId) }}
                            <button
                                type="button"
                                class="text-slate-400 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="usedValueIds.has(valueId)"
                                :title="usedValueIds.has(valueId) ? 'Còn SKU: tắt các ô của giá trị này rồi lưu trước' : undefined"
                                :aria-label="`Bỏ ${valueName(valueId)}`"
                                @click="removeValue(axis, valueId)"
                            >✕</button>
                        </span>
                    </Sortable>
                    <span class="ml-auto flex items-center gap-1">
                        <span class="w-40"><Select v-model="newValue[axis.option_type_id]" :options="addableValues(axis)" placeholder="+ Giá trị" :aria-label="`Thêm giá trị ${typeName(axis.option_type_id)}`" /></span>
                        <button type="button" :class="secondaryButton" :disabled="!newValue[axis.option_type_id]" @click="addValue(axis)">Thêm</button>
                        <button v-if="!locked" type="button" class="text-xs text-red-600" @click="removeAxis(axis)">Bỏ trục</button>
                    </span>
                </div>
            </Sortable>
            <div v-if="!locked && axes.length < MAX_AXES" class="flex items-center gap-2 text-sm">
                <span class="w-56"><Select v-model="newTypeId" :options="addableTypes" placeholder="+ Thêm trục (loại tuỳ chọn)…" aria-label="Thêm trục" /></span>
                <button type="button" :class="secondaryButton" :disabled="!newTypeId" @click="addAxis">Thêm trục</button>
                <a :href="optionTypesUrl" class="text-xs text-indigo-600 underline">Quản lý loại tuỳ chọn</a>
            </div>
            <p v-else-if="locked" class="text-xs text-slate-500">Đã có SKU: không đổi được loại tuỳ chọn (tắt hết SKU rồi lưu trước). Vẫn thêm/bớt giá trị và kéo đổi thứ tự trục được.</p>
            <p v-for="message in axisErrors" :key="message" class="text-sm text-red-600">{{ message }}</p>
        </div>

        <p v-if="!axes.length" class="text-sm text-slate-500">Chưa có trục. Thêm ít nhất một loại tuỳ chọn (vd. Màu, Size).</p>
        <template v-else-if="combos.length">
            <!-- 2 trục: lưới hàng × cột -->
            <div v-if="axes.length === 2" class="overflow-x-auto text-sm">
                <div class="grid items-center gap-1" :style="gridStyle">
                    <span class="p-2 text-xs font-medium text-slate-500">{{ typeName(axes[0].option_type_id) }} \ {{ typeName(axes[1].option_type_id) }}</span>
                    <span v-for="columnId in axes[1].value_ids" :key="columnId" class="p-2 text-center font-medium">{{ valueName(columnId) }}</span>
                </div>
                <div v-for="rowId in axes[0].value_ids" :key="rowId" class="grid items-center gap-1 border-t border-slate-100" :style="gridStyle">
                    <div class="flex items-center gap-2 p-2">
                        <span v-if="isSwatch(axes[0].option_type_id)" class="inline-block h-5 w-5 rounded-full border border-slate-300" :style="{ background: valueById.get(rowId)?.hex ?? 'transparent' }" />
                        <span>{{ valueName(rowId) }}</span>
                    </div>
                    <div v-for="columnId in axes[1].value_ids" :key="columnId" class="p-1 text-center">
                        <button
                            type="button"
                            class="h-9 w-16 rounded border text-xs"
                            :class="cellClass(draftOf([rowId, columnId]))"
                            :aria-pressed="Boolean(draftOf([rowId, columnId])?.enabled)"
                            :aria-label="comboLabel([rowId, columnId])"
                            @click="toggle([rowId, columnId])"
                        >{{ cellText(draftOf([rowId, columnId])) }}</button>
                    </div>
                </div>
            </div>
            <!-- 1 hoặc 3 trục: danh sách tổ hợp -->
            <div v-else class="flex flex-wrap gap-1 text-sm">
                <button
                    v-for="values in combos"
                    :key="comboKey(values)"
                    type="button"
                    class="rounded border px-2 py-1 text-xs"
                    :class="cellClass(draftOf(values))"
                    :aria-pressed="Boolean(draftOf(values)?.enabled)"
                    @click="toggle(values)"
                >{{ comboLabel(values) }} · {{ cellText(draftOf(values)) }}</button>
            </div>
            <button v-if="enabledCount < combos.length" type="button" class="text-xs text-indigo-600 underline" @click="enableAll">Bật mọi tổ hợp ({{ combos.length }})</button>
        </template>

        <div v-if="enabledCount" class="flex flex-wrap items-end gap-2 rounded-md bg-slate-50 p-3 text-sm">
            <span class="w-full text-xs text-slate-500">Áp hàng loạt cho các SKU khớp (chưa lưu cho tới khi bấm "Lưu biến thể")</span>
            <div v-for="axis in axes" :key="axis.option_type_id" class="w-36">
                <Select :model-value="batch.filter[axis.option_type_id] ?? 0" :options="batchOptions(axis)" :aria-label="`${typeName(axis.option_type_id)} áp dụng`" @update:model-value="(value) => (batch.filter[axis.option_type_id] = Number(value))" />
            </div>
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
            <template #cell-label="{ row }">{{ row.label }}</template>
            <template #cell-images="{ row }">
                <button
                    v-if="row.draft.variantId && variantById.get(row.draft.variantId)"
                    type="button"
                    class="flex items-center gap-1 text-xs underline"
                    :class="variantById.get(row.draft.variantId)!.images.length ? 'text-slate-600' : 'text-amber-700'"
                    @click="imageVariantId = row.draft.variantId"
                >
                    <img v-if="variantById.get(row.draft.variantId)!.images.length" :src="variantById.get(row.draft.variantId)!.images[0].url" alt="" class="h-8 w-6 rounded object-cover" />
                    {{ variantById.get(row.draft.variantId)!.images.length ? `${variantById.get(row.draft.variantId)!.images.length} ảnh` : 'Thêm ảnh' }}
                </button>
                <span v-else class="text-xs text-slate-400" title="Lưu biến thể trước rồi thêm ảnh">—</span>
            </template>
            <template #cell-sku="{ row }"><input v-model="row.draft.sku" :class="[inputClass, 'w-44 font-mono text-xs']" :aria-label="`SKU ${row.label}`" /></template>
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

        <!-- Ảnh của một SKU -->
        <div v-if="imageVariant" class="space-y-3 rounded-md border border-slate-200 p-3" aria-live="polite">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium">Ảnh của {{ imageVariant.label }} <span class="font-mono text-xs text-slate-400">{{ imageVariant.sku }}</span></span>
                <button type="button" class="text-xs text-slate-500 underline" @click="imageVariantId = null">Đóng</button>
            </div>
            <p v-if="errors.images" class="text-sm text-red-600">{{ errors.images }}</p>
            <div class="flex flex-wrap gap-3">
                <Sortable
                    v-slot="{ item: image, index, handle }"
                    :model-value="imageVariant.images"
                    layout="grid"
                    list-class="flex flex-wrap gap-3"
                    item-class="w-24"
                    :item-key="(image) => image.id"
                    :item-label="(image) => `ảnh ${imageVariant!.images.indexOf(image) + 1} của ${imageVariant!.label}`"
                    @update:model-value="(images) => reorderImages(imageVariant!, images)"
                >
                    <img :src="image.url" :alt="index === 0 ? 'Ảnh đại diện' : ''" class="h-32 w-24 rounded object-cover" />
                    <div class="mt-1 flex items-center justify-between text-xs">
                        <SortHandle v-bind="handle" />
                        <button type="button" class="text-red-600" @click="removeImage(imageVariant!, image.id)">Xoá</button>
                    </div>
                </Sortable>
                <label class="flex h-32 w-24 cursor-pointer items-center justify-center rounded border border-dashed border-slate-300 text-xs text-slate-500">
                    + Ảnh
                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" multiple @change="uploadImages(imageVariant, $event)" />
                </label>
                <button type="button" class="flex h-32 w-24 items-center justify-center rounded border border-dashed border-indigo-300 text-center text-xs text-indigo-600" @click="picking = true">
                    Chọn từ<br />thư viện
                </button>
            </div>
            <div v-if="imageVariant.images.length && props.axes.length > 1" class="flex flex-wrap gap-2 text-xs">
                <button
                    v-for="(axis, index) in props.axes"
                    :key="axis.option_type_id"
                    type="button"
                    :class="secondaryButton"
                    @click="copyImages(imageVariant, axis.option_type_id, imageVariant.values[index])"
                >Áp cho mọi SKU cùng {{ typeName(axis.option_type_id).toLowerCase() }} {{ valueName(imageVariant.values[index]) }}</button>
            </div>
            <p class="text-xs text-slate-500">Ảnh đầu là ảnh đại diện của SKU (khuyến nghị tỉ lệ 4:5). Thời trang thường dùng chung ảnh cho mọi size cùng màu.</p>
        </div>
        <MediaPicker :open="picking" :title="`Ảnh cho ${imageVariant?.label ?? ''}`" @update:open="(value) => !value && (picking = false)" @select="attachFromLibrary" />
    </section>
</template>
