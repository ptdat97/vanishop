<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import Dialog from '@admin/Components/Dialog.vue';
import FileInput from '@admin/Components/FileInput.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import Select from '@admin/Components/Select.vue';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import { confirmAction } from '@admin/confirm';
import { useLeaveGuard } from '@admin/forms';
import { labelOptions } from '@admin/options';
import { useStoreLocales } from '@admin/locales';
import { slugify } from '@admin/slug';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { toast } from '@admin/toast';
import type { MediaItem } from '@admin/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';

/**
 * Ma trận biến thể theo trục tuỳ chọn (ADR-045, docs/03-domains/variant-matrix.md §3): sản phẩm chọn 1–3 loại tuỳ chọn
 * (Màu, Size, Chất liệu…) làm trục; mỗi SKU là một tổ hợp giá trị. 2 trục → lưới, 1 hoặc 3 trục → danh sách tổ hợp. Bấm để
 * bật/tắt SKU, sửa mọi SKU trong một bảng (kể cả cột do module/plugin góp: giá, tồn…), áp hàng loạt, lưu cả lưới một lần.
 * Ảnh theo từng SKU (đã lưu), áp bộ ảnh cho mọi SKU cùng giá trị ở một trục (vd. mọi size cùng màu).
 *
 * Nằm trong form sản phẩm (một nút Lưu chung): form gọi `payload()` khi lưu — null khi ma trận không đổi. Thiếu giá trị/loại
 * tuỳ chọn thì tạo nhanh tại chỗ (không rời trang, không mất phần đang nhập).
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
    /** '' khi sản phẩm chưa lưu (chưa có ảnh theo SKU). */
    productUrl: string;
    urls: { option_types: string; option_type_store: string; option_value_store: string };
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
function comboKey(valueIds: number[], axisList: Axis[] = activeAxes.value): string {
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

// Mã sản phẩm đổi (thường khi thêm mới): SKU tự sinh chưa lưu đổi theo tiền tố mới; SKU đã sửa tay hoặc đã lưu giữ nguyên.
watch(() => props.styleCode, (next, previous) => {
    const from = `${previous}-`.toUpperCase();
    Object.values(drafts).forEach((draft) => {
        if (draft.variantId === null && draft.sku.startsWith(from)) draft.sku = `${next}-`.toUpperCase() + draft.sku.slice(from.length);
    });
});

/** Mọi tổ hợp của các trục hiện tại (id giá trị theo thứ tự trục). */
/**
 * Trục đang dùng để dựng tổ hợp: sản phẩm chưa có SKU thì bỏ qua trục chưa chọn giá trị (vd. chỉ chọn màu, để trống Size → sản
 * phẩm một trục); đã có SKU thì mọi trục.
 */
const activeAxes = computed<Axis[]>(() => (locked.value ? axes.value : axes.value.filter((axis) => axis.value_ids.length > 0)));
const combos = computed<number[][]>(() => activeAxes.value.length === 0
    ? []
    : activeAxes.value.reduce<number[][]>((acc, axis) => acc.flatMap((prefix) => axis.value_ids.map((valueId) => [...prefix, valueId])), [[]]));
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
        const position = activeAxes.value.indexOf(axis);
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
const gridStyle = computed(() => ({ gridTemplateColumns: `minmax(10rem, auto) repeat(${activeAxes.value[1]?.value_ids.length ?? 0}, 4.5rem)` }));
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
    { key: 'label', label: activeAxes.value.map((axis) => typeName(axis.option_type_id)).join(' / '), class: 'whitespace-nowrap' },
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
        if (activeAxes.value.some((axis, index) => (batch.filter[axis.option_type_id] ?? 0) !== 0 && row.values[index] !== batch.filter[axis.option_type_id])) continue;
        if (batch.target === 'status') row.draft.status = String(batch.value ?? 'active');
        else if (batch.target === 'weight_gram') row.draft.weight_gram = batch.value === null || batch.value === '' ? null : Number(batch.value);
        else {
            const [code, field] = batch.target.split('.', 2);
            row.draft.fields[code] = { ...(row.draft.fields[code] ?? {}), [field]: batch.value === '' ? null : batch.value };
        }
    }
}

type Cell = { values: number[]; enabled: boolean; status: string; sku: string; barcode: string | null; weight_gram: number | null; fields: Record<string, Record<string, FieldValue>> };

/**
 * Dữ liệu ma trận gửi kèm form sản phẩm; null = không đổi gì. Trục chưa chọn giá trị nào bị bỏ khi sản phẩm chưa có SKU (vd. sản
 * phẩm chỉ có màu thì để trống Size).
 */
function payload(): { axes: Axis[]; cells: Cell[] } | null {
    if (!dirty.value) return null;
    const sent = activeAxes.value;
    if (sent.length === 0) return null;
    submitted = [];
    const cells: Cell[] = [];
    const combinations = sent.reduce<number[][]>((acc, axis) => acc.flatMap((prefix) => axis.value_ids.map((valueId) => [...prefix, valueId])), [[]]);
    for (const values of combinations) {
        const key = comboKey(values, sent);
        const draft = drafts[key];
        if (!draft || (!draft.enabled && draft.variantId === null)) continue;
        submitted.push(key);
        cells.push({
            values, enabled: draft.enabled, status: draft.status, sku: draft.sku, barcode: draft.barcode, weight_gram: draft.weight_gram,
            fields: Object.fromEntries(props.columns.filter((column) => column.editable).map((column) => [column.code, draft.fields[column.code] ?? {}])),
        });
    }
    return { axes: sent, cells };
}
/** Bật/tắt cả hàng/cột của lưới (trục `position` = giá trị `valueId`): còn ô tắt → bật hết, đã bật hết → tắt hết. */
function toggleLine(position: number, valueId: number): void {
    const targets = combos.value.filter((values) => values[position] === valueId);
    const allOn = targets.every((values) => draftOf(values)?.enabled);
    targets.forEach((values) => toggle(values, !allOn));
}
const lineCount = (position: number, valueId: number): string => {
    const targets = combos.value.filter((values) => values[position] === valueId);
    return `${targets.filter((values) => draftOf(values)?.enabled).length}/${targets.length}`;
};

/** Tóm tắt cho thẻ "Biến thể" trên form sản phẩm (ngoài popup). */
const summary = computed(() => ({
    axes: activeAxes.value.map((axis) => ({ name: typeName(axis.option_type_id), swatch: isSwatch(axis.option_type_id), values: axis.value_ids.map((id) => ({ name: valueName(id), hex: valueById.value.get(id)?.hex ?? null })) })),
    active: rows.value.filter((row) => row.draft.status === 'active').length,
    inactive: rows.value.filter((row) => row.draft.status !== 'active').length,
    withoutImages: props.variants.filter((variant) => variant.images.length === 0).length,
    images: props.variants.flatMap((variant) => variant.images.slice(0, 1)).slice(0, 8),
    dirty: dirty.value,
}));

defineExpose({ payload, summary });

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

const imageUploadProgress = ref<number | null>(null);

function uploadImages(variant: Variant, files: File[]): void {
    if (files.length) {
        router.post(imagesUrl(variant), { images: files }, {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progress) => (imageUploadProgress.value = progress?.percentage ?? null),
            onFinish: () => (imageUploadProgress.value = null),
        });
    }
}

function attachFromLibrary(items: MediaItem[]): void {
    if (imageVariant.value && items.length) {
        router.post(`${imagesUrl(imageVariant.value)}/library`, { media_ids: items.map((item) => item.id) }, { preserveScroll: true });
    }
    picking.value = false;
}

/* Gán một bộ ảnh cho nhiều SKU: chọn ảnh (thứ tự trong khay của Thư viện ảnh) → chọn SKU → gán (thêm vào cuối, bỏ ảnh đã có). */
const assign = reactive<{ picking: boolean; open: boolean; media: MediaItem[]; variantIds: number[]; busy: boolean }>({ picking: false, open: false, media: [], variantIds: [], busy: false });

function startAssign(items: MediaItem[]): void {
    assign.picking = false;
    if (!items.length) return;
    assign.media = items;
    assign.variantIds = imageVariantId.value ? [imageVariantId.value] : [];
    assign.open = true;
}

/** SKU có giá trị `valueId` ở trục `axisIndex` (vd. mọi size của màu Đỏ). */
const variantsWith = (axisIndex: number, valueId: number): number[] => props.variants.filter((variant) => variant.values[axisIndex] === valueId).map((variant) => variant.id);

function toggleGroup(ids: number[]): void {
    const all = ids.every((id) => assign.variantIds.includes(id));
    assign.variantIds = all ? assign.variantIds.filter((id) => !ids.includes(id)) : [...new Set([...assign.variantIds, ...ids])];
}

function submitAssign(): void {
    if (!assign.variantIds.length || assign.busy) return;
    assign.busy = true;
    router.post(`${props.productUrl}/images/assign`, { media_ids: assign.media.map((item) => item.id), variant_ids: assign.variantIds }, {
        preserveScroll: true,
        onSuccess: () => {
            assign.open = false;
            assign.variantIds = [];
        },
        onError: (errors) => toast(Object.values(errors)[0] ?? 'Không gán được ảnh.', 'error'),
        onFinish: () => (assign.busy = false),
    });
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

// Tạo nhanh giá trị / loại tuỳ chọn ngay tại đây (túi lỗi riêng để không lẫn với lỗi của form sản phẩm).
const { defaultLocale } = useStoreLocales();
const quickErrors = computed(() => (page.props.errors as unknown as Record<string, Record<string, string> | undefined>).quickOption ?? {});
const quickValue = reactive<Record<number, { name: string; hex: string }>>({});
const quickBusy = ref(false);
const newValueDraft = (axis: Axis) => (quickValue[axis.option_type_id] ??= { name: '', hex: '#000000' });

function createValue(axis: Axis): void {
    const draft = newValueDraft(axis);
    const name = draft.name.trim();
    const code = slugify(name).toUpperCase().slice(0, 32);
    if (!name || !code) return;
    const existing = typeById.value.get(axis.option_type_id)?.values.find((value) => value.code === code);
    if (existing) {
        if (!axis.value_ids.includes(existing.id)) {
            newValue[axis.option_type_id] = existing.id;
            addValue(axis);
        }
        draft.name = '';
        return;
    }
    quickBusy.value = true;
    router.post(props.urls.option_value_store.replace('{type}', String(axis.option_type_id)), {
        code, position: typeById.value.get(axis.option_type_id)?.values.length ?? 0, group: null, family: null,
        hex: isSwatch(axis.option_type_id) ? draft.hex.toUpperCase() : null, translations: { [defaultLocale]: { name } },
    }, {
        preserveState: true, preserveScroll: true, errorBag: 'quickOption',
        onSuccess: () => {
            const created = typeById.value.get(axis.option_type_id)?.values.find((value) => value.code === code);
            if (created) {
                newValue[axis.option_type_id] = created.id;
                addValue(axis);
            }
            draft.name = '';
        },
        onFinish: () => (quickBusy.value = false),
    });
}

const quickType = reactive({ name: '', display_type: 'button' });
const displayTypeOptions = [{ value: 'button', label: 'Nút' }, { value: 'swatch', label: 'Ô màu' }, { value: 'dropdown', label: 'Danh sách' }];

function createType(): void {
    const name = quickType.name.trim();
    let code = slugify(name).replace(/-/g, '_').slice(0, 32);
    if (!name || !code) return;
    if (!/^[a-z]/.test(code)) code = `t_${code}`.slice(0, 32);
    const existing = props.optionTypes.find((type) => type.code === code);
    if (existing) {
        newTypeId.value = existing.id;
        addAxis();
        quickType.name = '';
        return;
    }
    quickBusy.value = true;
    router.post(props.urls.option_type_store, {
        code, display_type: quickType.display_type, is_filterable: true, position: props.optionTypes.length, translations: { [defaultLocale]: { name } },
    }, {
        preserveState: true, preserveScroll: true, errorBag: 'quickOption',
        onSuccess: () => {
            const created = props.optionTypes.find((type) => type.code === code);
            if (created) {
                newTypeId.value = created.id;
                addAxis();
            }
            quickType.name = '';
        },
        onFinish: () => (quickBusy.value = false),
    });
}

// Rời trang khi còn thay đổi chưa lưu → hỏi lại (một hộp hỏi chung với form sản phẩm).
useLeaveGuard(() => dirty.value);

const enabledCount = computed(() => rows.value.length);
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(320px,400px)_minmax(0,1fr)]">
        <aside class="space-y-3" aria-label="Tuỳ chọn biến thể">
            <div class="flex items-baseline justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-900">1. Tuỳ chọn</h3>
                <a :href="urls.option_types" target="_blank" class="text-xs text-indigo-600 underline">Danh mục tuỳ chọn ↗</a>
            </div>
            <p class="text-xs text-slate-500">Tối đa 3 trục (vd. Màu, Size). Chọn giá trị có sẵn hoặc gõ tên để tạo mới.</p>
        <!-- Bước 1 — Tuỳ chọn: trục (kéo để đổi thứ tự) và giá trị (kéo để đổi thứ tự hiển thị) -->
        <div class="space-y-2">
            <Sortable v-slot="{ item: axis, handle }" v-model="axes" :item-key="(axis) => axis.option_type_id" :item-label="(axis) => `trục ${typeName(axis.option_type_id)}`">
                <div class="space-y-2 rounded-lg border border-slate-200 bg-white p-3 text-sm">
                    <div class="flex items-center gap-2">
                        <SortHandle v-bind="handle" />
                        <span class="font-medium">{{ typeName(axis.option_type_id) }}</span>
                        <span class="rounded-full bg-slate-100 px-2 text-xs text-slate-600">{{ axis.value_ids.length }}</span>
                        <button v-if="!locked" type="button" class="ml-auto text-xs text-red-600 hover:underline" @click="removeAxis(axis)">Bỏ trục</button>
                    </div>
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
                    <p v-if="!axis.value_ids.length" class="text-xs text-amber-700">Chưa chọn giá trị — trục trống được bỏ qua.</p>
                    <div class="space-y-1.5">
                        <!-- Chọn là thêm ngay (một thao tác). -->
                        <div v-if="addableValues(axis).length">
                            <Select
                                :model-value="null"
                                :options="addableValues(axis)"
                                :placeholder="`+ ${typeName(axis.option_type_id)} có sẵn`"
                                :aria-label="`Thêm giá trị ${typeName(axis.option_type_id)}`"
                                @update:model-value="(id) => { newValue[axis.option_type_id] = Number(id); addValue(axis); }"
                            />
                        </div>
                        <!-- Chưa có thì tạo mới tại chỗ: gõ tên (màu kèm ô chọn màu) rồi Enter. -->
                        <div class="flex items-center gap-1">
                        <input
                            v-if="isSwatch(axis.option_type_id)"
                            v-model="newValueDraft(axis).hex"
                            type="color"
                            class="h-8 w-8 cursor-pointer rounded border border-slate-300"
                            :aria-label="`Mã màu cho ${typeName(axis.option_type_id)} mới`"
                        />
                        <input
                            v-model="newValueDraft(axis).name"
                            :class="[inputClass, 'min-w-0 flex-1']"
                            :placeholder="`${typeName(axis.option_type_id)} mới…`"
                            :aria-label="`Tạo ${typeName(axis.option_type_id)} mới`"
                            @keydown.enter.prevent="createValue(axis)"
                        />
                        <button type="button" :class="secondaryButton" :disabled="quickBusy || !newValueDraft(axis).name.trim()" @click="createValue(axis)">Tạo</button>
                        </div>
                    </div>
                </div>
            </Sortable>
            <div v-if="!locked && axes.length < MAX_AXES" class="space-y-1.5 rounded-lg border border-dashed border-slate-300 p-3 text-sm">
                <span class="text-xs font-medium text-slate-600">Thêm trục ({{ axes.length }}/{{ MAX_AXES }})</span>
                <span v-if="addableTypes.length" class="block">
                    <Select :model-value="null" :options="addableTypes" placeholder="+ Thêm trục có sẵn…" aria-label="Thêm trục" @update:model-value="(id) => { newTypeId = Number(id); addAxis(); }" />
                </span>
                <span class="block text-xs text-slate-400">hoặc tạo loại mới</span>
                <div class="flex items-center gap-1">
                    <input v-model="quickType.name" :class="[inputClass, 'min-w-0 flex-1']" placeholder="vd. Chất liệu" aria-label="Tên loại tuỳ chọn mới" @keydown.enter.prevent="createType" />
                    <span class="w-28"><Select v-model="quickType.display_type" :options="displayTypeOptions" aria-label="Kiểu hiển thị" /></span>
                    <button type="button" :class="secondaryButton" :disabled="quickBusy || !quickType.name.trim()" @click="createType">Tạo</button>
                </div>
            </div>
            <p v-else-if="locked" class="text-xs text-slate-500">Đã có SKU: không đổi được loại tuỳ chọn (tắt hết SKU rồi lưu trước). Vẫn thêm/bớt giá trị và kéo đổi thứ tự trục được.</p>
            <p v-for="message in axisErrors" :key="message" class="text-sm text-red-600">{{ message }}</p>
            <p v-for="(message, field) in quickErrors" :key="field" class="text-sm text-red-600">{{ message }}</p>
        </div>

        </aside>

        <div class="min-w-0 space-y-6">
            <section class="space-y-3" aria-label="Tổ hợp đang bán">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">2. Chọn tổ hợp bán</h3>
                    <span class="text-xs text-slate-500">{{ summary.active }} đang bán · {{ summary.inactive }} ngừng bán<template v-if="combos.length"> / {{ combos.length }} tổ hợp</template></span>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3">
        <p v-if="!axes.length" class="text-sm text-slate-500">Chưa có trục. Thêm ít nhất một loại tuỳ chọn (vd. Màu, Size).</p>
                    <p v-else-if="!combos.length" class="text-sm text-slate-500">Chọn hoặc tạo giá trị cho trục (vd. các màu, các size) — mọi tổ hợp được bật sẵn, tắt ô không bán.</p>
                    <template v-else-if="combos.length">
                        <!-- 2 trục: lưới hàng × cột -->
                        <div v-if="activeAxes.length === 2" class="overflow-x-auto text-sm">
                            <div class="grid items-center gap-1" :style="gridStyle">
                                <span class="p-2 text-xs font-medium text-slate-500">{{ typeName(activeAxes[0].option_type_id) }} \ {{ typeName(activeAxes[1].option_type_id) }}</span>
                                <button
                                    v-for="columnId in activeAxes[1].value_ids"
                                    :key="columnId"
                                    type="button"
                                    class="rounded p-2 text-center font-medium hover:bg-indigo-50"
                                    :title="`Bật/tắt cả cột ${valueName(columnId)}`"
                                    @click="toggleLine(1, columnId)"
                                >{{ valueName(columnId) }}</button>
                            </div>
                            <div v-for="rowId in activeAxes[0].value_ids" :key="rowId" class="grid items-center gap-1 border-t border-slate-100" :style="gridStyle">
                                <button type="button" class="flex items-center gap-2 rounded p-2 text-left hover:bg-indigo-50" :title="`Bật/tắt cả hàng ${valueName(rowId)}`" @click="toggleLine(0, rowId)">
                                    <span v-if="isSwatch(activeAxes[0].option_type_id)" class="inline-block h-5 w-5 shrink-0 rounded-full border border-slate-300" :style="{ background: valueById.get(rowId)?.hex ?? 'transparent' }" />
                                    <span class="truncate font-medium">{{ valueName(rowId) }}</span>
                                    <span class="ml-auto text-xs text-slate-400">{{ lineCount(0, rowId) }}</span>
                                </button>
                                <div v-for="columnId in activeAxes[1].value_ids" :key="columnId" class="p-1 text-center">
                                    <button
                                        type="button"
                                        class="h-11 w-full rounded-md border text-xs font-medium transition"
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
                                class="rounded-md border px-3 py-2 text-xs font-medium"
                                :class="cellClass(draftOf(values))"
                                :aria-pressed="Boolean(draftOf(values)?.enabled)"
                                @click="toggle(values)"
                            >{{ comboLabel(values) }} · {{ cellText(draftOf(values)) }}</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span><span class="inline-block h-3 w-3 rounded border border-indigo-500 bg-indigo-50 align-middle" /> Đang bán</span>
                            <span><span class="inline-block h-3 w-3 rounded border border-slate-400 bg-slate-100 align-middle" /> Ngừng bán</span>
                            <span><span class="inline-block h-3 w-3 rounded border border-dashed border-slate-300 align-middle" /> Chưa có</span>
                            <span v-if="activeAxes.length === 2">· Bấm tên hàng/cột để bật/tắt cả hàng/cột</span>
                            <button v-if="enabledCount < combos.length" type="button" class="ml-auto text-indigo-600 underline" @click="enableAll">Bật mọi tổ hợp ({{ combos.length }})</button>
                        </div>
                    </template>

                </div>
            </section>

            <section v-if="enabledCount" class="space-y-3" aria-label="Chi tiết SKU">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">3. Chi tiết SKU</h3>
                    <button v-if="productUrl && variants.length" type="button" :class="secondaryButton" data-assign-images @click="assign.picking = true">Gán ảnh cho nhiều SKU</button>
                    <span class="text-xs text-slate-500">Sửa trực tiếp trong bảng: SKU, giá, tồn… <template v-if="summary.withoutImages"> · <span class="text-amber-700">{{ summary.withoutImages }} SKU chưa có ảnh</span></template></span>
                </div>
                <div class="flex flex-wrap items-end gap-2 rounded-md border border-slate-200 bg-white p-3 text-sm">
                    <span class="w-full text-xs text-slate-500">Áp hàng loạt cho các SKU khớp (lưu khi bấm "Lưu")</span>
                    <div v-for="axis in activeAxes" :key="axis.option_type_id" class="w-36">
                        <Select :model-value="batch.filter[axis.option_type_id] ?? 0" :options="batchOptions(axis)" :aria-label="`${typeName(axis.option_type_id)} áp dụng`" @update:model-value="(value) => (batch.filter[axis.option_type_id] = Number(value))" />
                    </div>
                    <div class="w-56"><Select v-model="batch.target" :options="batchTargets" placeholder="Trường…" aria-label="Trường cần áp" /></div>
                    <div class="w-40">
                        <Select v-if="batch.target === 'status'" v-model="batch.value" :options="labelOptions(statusLabels)" aria-label="Giá trị" />
                        <input v-else v-model="batch.value" :class="inputClass" aria-label="Giá trị" />
                    </div>
                    <button type="button" :class="secondaryButton" :disabled="!batch.target" @click="applyBatch">Áp dụng</button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                    <DataTable
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
                </div>
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
                        <FileInput
                            compact
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            :max-kb="10240"
                            :progress="imageUploadProgress"
                            :disabled="imageUploadProgress !== null"
                            class="w-24"
                            @pick="uploadImages(imageVariant!, $event)"
                        >
                            <span class="flex h-32 w-24 items-center justify-center rounded border border-dashed border-slate-300 text-xs text-slate-500">{{ imageUploadProgress === null ? '+ Ảnh' : `${imageUploadProgress}%` }}</span>
                        </FileInput>
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
            </section>
        </div>
        <MediaPicker v-model:open="assign.picking" title="Chọn ảnh để gán cho nhiều SKU" @select="startAssign" />
        <Dialog v-model:open="assign.open" title="Gán ảnh cho SKU" :subtitle="`${assign.media.length} ảnh — thêm vào cuối bộ ảnh của SKU đã chọn`" size="large" :dirty="assign.variantIds.length > 0 && !assign.busy">
            <div class="space-y-4 overflow-y-auto">
                <ol class="flex flex-wrap gap-2" aria-label="Ảnh sẽ gán (theo thứ tự)">
                    <li v-for="(item, index) in assign.media" :key="item.id" class="relative">
                        <img :src="item.thumb_url ?? item.url" :alt="item.name" class="h-24 w-20 rounded object-cover" />
                        <span class="absolute top-1 left-1 rounded bg-slate-900/70 px-1 text-[10px] text-white">{{ index + 1 }}</span>
                    </li>
                </ol>
                <div v-for="(axis, axisIndex) in props.axes" :key="axis.option_type_id" class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="w-24 text-slate-500">{{ typeName(axis.option_type_id) }}</span>
                    <button
                        v-for="valueId in axis.value_ids.filter((id) => variantsWith(axisIndex, id).length)"
                        :key="valueId"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs"
                        :class="variantsWith(axisIndex, valueId).every((id) => assign.variantIds.includes(id)) ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-300'"
                        :aria-pressed="variantsWith(axisIndex, valueId).every((id) => assign.variantIds.includes(id))"
                        @click="toggleGroup(variantsWith(axisIndex, valueId))"
                    >Mọi SKU {{ valueName(valueId) }}</button>
                </div>
                <fieldset class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
                    <legend class="mb-1 text-sm font-medium">SKU nhận ảnh ({{ assign.variantIds.length }}/{{ variants.length }})</legend>
                    <label v-for="variant in variants" :key="variant.id" class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-slate-100">
                        <input v-model="assign.variantIds" type="checkbox" :value="variant.id" />
                        <span class="flex-1 truncate">{{ variant.label }} <span class="font-mono text-xs text-slate-400">{{ variant.sku }}</span></span>
                        <span class="text-xs text-slate-400">{{ variant.images.length }} ảnh</span>
                    </label>
                </fieldset>
            </div>
            <template #footer="{ close }">
                <button type="button" :class="secondaryButton" @click="close">Huỷ</button>
                <button type="button" :class="primaryButton" class="ml-auto" :disabled="!assign.variantIds.length || assign.busy" @click="submitAssign">
                    {{ assign.busy ? 'Đang gán…' : `Gán cho ${assign.variantIds.length} SKU` }}
                </button>
            </template>
        </Dialog>
        <MediaPicker :open="picking" :title="`Ảnh cho ${imageVariant?.label ?? ''}`" @update:open="(value) => !value && (picking = false)" @select="attachFromLibrary" />
    </div>
</template>
