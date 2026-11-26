<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import Select from '@admin/Components/Select.vue';
import { useLeaveGuard } from '@admin/forms';
import { labelOptions } from '@admin/options';
import { inputClass } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import VariantWeightInput from './VariantWeightInput.vue';

/**
 * Sản phẩm đơn: một SKU không màu, size… (ma trận 0 trục, một ô `values: []` — VariantMatrix). Ảnh, SKU, barcode, khối lượng,
 * trạng thái và cột do module góp (giá niêm yết, giá so sánh…) trên một dòng. Gửi kèm form sản phẩm (một nút Lưu) qua
 * `payload()`; null = không đổi. Thêm mới: ảnh theo `media_ids` của form; đã lưu: ảnh của chính SKU (lưu ngay khi thao tác).
 */
type FieldValue = string | number | boolean | null;
interface Field { key: string; label: string; type: string; required: boolean; options: Record<string, string>; help: string | null }
interface Column { code: string; label: string; editable: boolean; fields: Field[] }
interface Variant { id: number; sku: string; barcode: string | null; status: string; weight_gram: number | null; images: Array<{ id: number; url: string }> }
interface Draft { sku: string; barcode: string | null; weight_gram: number | null; status: string; fields: Record<string, Record<string, FieldValue>> }

const props = defineProps<{
    /** '' khi sản phẩm chưa lưu. */
    productUrl: string;
    productCode: string;
    /** SKU đã lưu của sản phẩm đơn (null khi thêm mới hoặc chưa có SKU). */
    variant: Variant | null;
    /** Giá trị cột góp vào của SKU đã lưu. */
    values: Record<string, Record<string, FieldValue>>;
    columns: Column[];
    statusLabels: Record<string, string>;
    defaultWeightGram: number;
}>();
const mediaIds = defineModel<number[]>('mediaIds', { default: () => [] });

const page = usePage<{ errors: Record<string, string> }>();
const draft = reactive<Draft>({ sku: '', barcode: null, weight_gram: null, status: 'active', fields: {} });
/** SKU theo mã sản phẩm đến khi sửa tay (thêm mới). */
const skuEdited = ref(false);
let initial = '';

function init(): void {
    Object.assign(draft, {
        sku: props.variant?.sku ?? '', barcode: props.variant?.barcode ?? null, weight_gram: props.variant?.weight_gram ?? null,
        status: props.variant?.status ?? 'active', fields: JSON.parse(JSON.stringify(props.values ?? {})),
    });
    skuEdited.value = props.variant !== null;
    initial = JSON.stringify(draft);
}
init();
watch(() => JSON.stringify([props.variant && [props.variant.id, props.variant.sku, props.variant.barcode, props.variant.status, props.variant.weight_gram], props.values]), init);

const sku = computed({
    get: () => (skuEdited.value ? draft.sku : props.productCode.toUpperCase()),
    set: (value: string) => {
        skuEdited.value = true;
        draft.sku = value;
    },
});

const fieldColumns = computed(() => props.columns.flatMap((column) => column.fields.map((field) => ({ key: `f:${column.code}.${field.key}`, column, field }))));
const tableColumns = computed(() => [
    { key: 'images', label: t('catalog::admin.default_variant_editor.image') },
    { key: 'sku', label: 'SKU' },
    { key: 'barcode', label: 'Barcode' },
    { key: 'weight_gram', label: t('catalog::admin.default_variant_editor.weight') },
    { key: 'status', label: t('catalog::admin.default_variant_editor.status') },
    ...fieldColumns.value.map(({ key, column, field }) => ({ key, label: column.editable ? field.label : t('catalog::admin.default_variant_editor.label_view_only', { label: field.label }) })),
]);
const rows = [{ key: 'single' }];

const fieldValue = (column: Column, field: Field): FieldValue => draft.fields[column.code]?.[field.key] ?? null;
function setField(column: Column, field: Field, value: FieldValue): void {
    draft.fields[column.code] = { ...(draft.fields[column.code] ?? {}), [field.key]: value === '' ? null : value };
}

/** Thêm mới: chưa nhập gì thì lưu sản phẩm chưa có SKU. Đã có SKU: gửi khi có thay đổi. */
const dirty = computed(() => (props.variant ? JSON.stringify(draft) !== initial : skuEdited.value || JSON.stringify(draft) !== initial));

/** `force`: gửi cả khi không đổi (chuyển về sản phẩm đơn — server cần ô của SKU giữ lại). */
function payload(force = false): { axes: never[]; cells: Array<Record<string, unknown>> } | null {
    if (!dirty.value && !force) return null;
    return {
        axes: [],
        cells: [{
            values: [], enabled: true, status: draft.status, sku: sku.value, barcode: draft.barcode || null, weight_gram: draft.weight_gram,
            fields: Object.fromEntries(props.columns.filter((column) => column.editable).map((column) => [column.code, draft.fields[column.code] ?? {}])),
        }],
    };
}
defineExpose({ payload, dirty });
useLeaveGuard(() => dirty.value);

const rowError = computed(() => Object.entries(page.props.errors ?? {})
    .filter(([field]) => field === 'axes' || field === 'cells' || field === 'collapse_to' || field.startsWith('cells.0.') || (props.variant !== null && field === `variant.${props.variant.id}`))
    .map(([, message]) => message).join(' ') || null);

// Ảnh của SKU đã lưu: chọn từ thư viện / xoá, lưu ngay.
const picking = ref(false);
const imagesUrl = computed(() => (props.variant ? `${props.productUrl}/variants/${props.variant.id}/images` : ''));
function attach(items: MediaItem[]): void {
    picking.value = false;
    if (imagesUrl.value && items.length) {
        router.post(`${imagesUrl.value}/library`, { media_ids: items.map((item) => item.id) }, { preserveScroll: true });
    }
}
function removeImage(imageId: number): void {
    router.delete(`${imagesUrl.value}/${imageId}`, { preserveScroll: true });
}
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-slate-200" data-single-variant>
        <DataTable :columns="tableColumns" :rows="rows" :row-key="(row) => row.key" :row-label="() => 'SKU'" :row-class="() => 'align-top'" :expanded="() => rowError !== null" bare>
            <template #cell-images>
                <div v-if="variant" class="flex w-48 flex-wrap items-center gap-1">
                    <span v-for="image in variant.images" :key="image.id" class="group relative">
                        <img :src="image.url" alt="" class="h-12 w-9 rounded object-cover" />
                        <button type="button" class="absolute -top-1 -right-1 hidden rounded-full bg-white px-1 text-xs text-red-600 shadow group-hover:block" :aria-label="t('catalog::admin.default_variant_editor.delete_image')" @click="removeImage(image.id)">✕</button>
                    </span>
                    <button type="button" class="text-xs text-indigo-600 underline" @click="picking = true">{{ t('catalog::admin.default_variant_editor.image_2') }}</button>
                </div>
                <div v-else class="w-48"><MediaField v-model="mediaIds" multiple :max="20" /></div>
            </template>
            <template #cell-sku><input v-model="sku" :class="[inputClass, 'w-44 font-mono text-xs']" aria-label="SKU" /></template>
            <template #cell-barcode><input v-model="draft.barcode" :class="[inputClass, 'w-32 font-mono text-xs']" aria-label="Barcode" /></template>
            <template #cell-weight_gram><VariantWeightInput v-model="draft.weight_gram" :default-grams="defaultWeightGram" /></template>
            <template #cell-status><div class="w-32"><Select v-model="draft.status" :options="labelOptions(statusLabels)" :aria-label="t('catalog::admin.default_variant_editor.status')" /></div></template>
            <template v-for="{ key: columnKey, column, field } in fieldColumns" :key="columnKey" #[`cell-${columnKey}`]>
                <input
                    v-if="field.type === 'bool'"
                    type="checkbox"
                    :checked="Boolean(fieldValue(column, field))"
                    :disabled="!column.editable"
                    :aria-label="field.label"
                    @change="setField(column, field, ($event.target as HTMLInputElement).checked)"
                />
                <div v-else-if="field.type === 'select'" class="w-32">
                    <Select :model-value="fieldValue(column, field) as string | null" :options="labelOptions(field.options)" :disabled="!column.editable" :aria-label="field.label" @change="setField(column, field, $event)" />
                </div>
                <input
                    v-else
                    :value="fieldValue(column, field) ?? ''"
                    :type="field.type === 'int' ? 'number' : 'text'"
                    :class="[inputClass, field.type === 'int' ? 'w-28' : 'w-40']"
                    :disabled="!column.editable"
                    :aria-label="field.label"
                    :title="field.help ?? undefined"
                    @input="setField(column, field, field.type === 'int' && ($event.target as HTMLInputElement).value !== '' ? Number(($event.target as HTMLInputElement).value) : ($event.target as HTMLInputElement).value)"
                />
            </template>
            <template #expanded><p class="text-xs text-red-600">{{ rowError }}</p></template>
        </DataTable>
        <MediaPicker v-if="variant" :open="picking" :title="t('catalog::admin.default_variant_editor.image_sku')" @update:open="(value) => !value && (picking = false)" @select="attach" />
    </div>
</template>
