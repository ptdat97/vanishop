<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import ExtensionTools from '@admin/Components/Extensions/ExtensionTools.vue';
import FileInput from '@admin/Components/FileInput.vue';
import FormField from '@admin/Components/FormField.vue';
import Dialog from '@admin/Components/Dialog.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import SkuPicker from '@admin/Components/SkuPicker.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { getJson, HttpError, postJson } from '@admin/http';
import type { SkuPickerProduct } from '@admin/skuPicker';
import type { ExtensionFilter, ExtensionList } from '@admin/types';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { stateTone, warningTone } from '../../priceListState';

/**
 * Trang làm việc của bảng giá (thao tác gọn — đợt 1): mọi SKU trong bảng, sửa giá trong ô, chọn nhiều để áp giá hàng loạt
 * (giảm %, giảm số tiền, đồng giá — tính từ giá niêm yết, có xem trước) hoặc gỡ; thêm sản phẩm bằng tìm theo tên/mã/SKU.
 * Cảnh báo (0đ, không thấp hơn giá niêm yết, giảm vượt mức trần) chỉ để nhắc, không chặn lưu.
 */
interface Row {
    variant_id: number;
    style_code: string;
    name: string;
    sku: string;
    variant_label: string;
    status: string;
    list_amount: number | null;
    amount: number | null;
    compare_at_amount: number | null;
    percent: number | null;
    warnings: string[];
}
interface PreviewRow {
    variant_id: number;
    name: string;
    sku: string;
    variant_label: string;
    list_amount: number | null;
    current_amount: number | null;
    new_amount: number | null;
    percent: number | null;
    warnings: string[];
}
type Scope = { scope: 'variants'; variant_ids: number[] } | { scope: 'styles'; style_ids: number[] } | { scope: 'all'; q: string };

const props = defineProps<{
    baseUrl: string;
    labels: {
        price_list_type: Record<string, string>;
        price_list_state: Record<string, string>;
        bulk: { modes: Record<string, string>; warnings: Record<string, string> };
    };
    priceList: { id: number; code: string; name: string; type: string; state: string; starts_at: string | null; ends_at: string | null };
    q: string;
    rows: Row[];
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    perPageOptions: number[];
    summary: { products: number; variants: number; warnings: Record<string, number> };
    capPercent: number;
    canManage: boolean;
    /** Bảng giá niêm yết: chỉ xem — giá niêm yết sửa ở sản phẩm. */
    readOnly: boolean;
    productsUrl: string;
    /** Từ danh sách sản phẩm "Đưa vào bảng giá…": mở sẵn hộp thoại cách tính giá cho các sản phẩm này. */
    addStyleIds: number[];
    /** Cột/lọc/thao tác/công cụ của plugin theo SKU (EP v3 X1). */
    extensions: ExtensionList;
    /** Bộ lọc phụ của plugin cho SkuPicker (`adminFilter('variant', …)`). */
    skuPickerFilters: ExtensionFilter[];
    /** Cách tính / làm tròn của plugin cho "Áp giá…" (EP v3 X4). */
    bulkOptions: { modes: Array<{ code: string; label: string; hint: string }>; roundings: Array<{ code: string; label: string }> };
}>();
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const editable = computed(() => props.canManage && !props.readOnly);

const listUrl = computed(() => `${props.baseUrl}/${props.priceList.id}`);
const isBase = computed(() => props.priceList.type === 'base');
const search = ref(props.q);
const selected = ref<number[]>([]);

function visit(page?: number, perPage = props.pagination.per_page): void {
    router.get(`${listUrl.value}/prices`, { q: search.value.trim() || undefined, ext: { ...ext }, page, per_page: perPage === props.perPageOptions[0] ? undefined : perPage }, { preserveState: true, preserveScroll: true });
}

// Sửa giá trong ô: chỉ gửi các dòng đã đổi.
const edits = reactive<Record<number, { amount: number | null; compare_at_amount: number | null }>>({});
watch(
    () => props.rows,
    (rows) => {
        Object.keys(edits).forEach((key) => delete edits[Number(key)]);
        rows.forEach((row) => (edits[row.variant_id] = { amount: row.amount, compare_at_amount: row.compare_at_amount }));
    },
    { immediate: true },
);
const blank = (value: unknown): number | null => (value === '' || value === null || value === undefined ? null : Number(value));
const dirtyRows = computed(() =>
    props.rows
        .filter((row) => blank(edits[row.variant_id]?.amount) !== row.amount || blank(edits[row.variant_id]?.compare_at_amount) !== row.compare_at_amount)
        .map((row) => ({ variant_id: row.variant_id, amount: blank(edits[row.variant_id].amount), compare_at_amount: blank(edits[row.variant_id].compare_at_amount) })),
);
const saveForm = useForm({ prices: [] as Array<{ variant_id: number; amount: number | null; compare_at_amount: number | null }> });
function saveEdits(): void {
    saveForm.transform(() => ({ prices: dirtyRows.value })).put(`${listUrl.value}/prices`, { preserveScroll: true });
}

async function removeSelected(ids: number[], clear: () => void): Promise<void> {
    if ((await confirmAction({ title: t('pricing::admin.price_lists_prices.remove_count_skus_from_price', { count: ids.length }), message: t('pricing::admin.price_lists_prices.customers_see_prices_from_another'), confirmLabel: t('pricing::admin.price_lists_prices.unlink'), tone: 'danger' })) !== false) {
        router.post(`${listUrl.value}/remove`, { variant_ids: ids }, { preserveScroll: true, onSuccess: clear });
    }
}

// Áp giá hàng loạt: chọn cách tính → xem trước → áp dụng.
const bulkOpen = ref(false);
const bulkScope = ref<Scope | null>(null);
const bulkTitle = ref('');
const rule = reactive({ mode: 'percent' as string, value: null as number | null, round: true, rounding: '' });
const modeOptions = computed(() => [
    ...Object.entries(props.labels.bulk.modes).map(([code, label]) => ({ code, label, hint: ({ percent: t('pricing::admin.price_lists_prices.list_price'), amount: t('pricing::admin.price_lists_prices.off_list_price'), fixed: t('pricing::admin.price_lists_prices.every_sku') } as Record<string, string>)[code] ?? '' })),
    ...props.bulkOptions.modes,
]);
const currentMode = computed(() => modeOptions.value.find((option) => option.code === rule.mode) ?? modeOptions.value[0]);
const preview = ref<{ rows: PreviewRow[]; summary: { total: number; changed: number; skipped: number; warnings: Record<string, number> } } | null>(null);
const previewing = ref(false);
const applying = ref(false);
const ruleError = ref('');
const payload = () => ({
    ...(bulkScope.value as Scope), mode: rule.mode, value: rule.value ?? 0,
    round_to: rule.round && !rule.rounding && rule.mode !== 'fixed' ? 1000 : 0,
    ...(rule.rounding ? { rounding: rule.rounding } : {}),
});

function openBulk(scope: Scope, title: string): void {
    bulkScope.value = scope;
    bulkTitle.value = title;
    preview.value = null;
    ruleError.value = '';
    bulkOpen.value = true;
}
watch(rule, () => (preview.value = null));
// Đến từ danh sách sản phẩm ("Đưa vào bảng giá…"): mở sẵn hộp thoại; khai báo sau các biến của hộp thoại vì chạy ngay.
watch(
    () => props.addStyleIds,
    (ids) => {
        if (ids.length && editable.value) {
            openBulk({ scope: 'styles', style_ids: [...ids] }, t('pricing::admin.price_lists_prices.add_count_products_name', { count: ids.length, name: props.priceList.name }));
            router.replace({ url: `${listUrl.value}/prices`, preserveState: true, preserveScroll: true });
        }
    },
    { immediate: true, flush: 'post' },
);

async function loadPreview(): Promise<void> {
    if (rule.value === null || Number.isNaN(rule.value)) {
        ruleError.value = t('pricing::admin.price_lists_prices.enter_value');
        return;
    }
    previewing.value = true;
    ruleError.value = '';
    try {
        preview.value = (await postJson<{ data: NonNullable<typeof preview.value> }>(`${listUrl.value}/bulk/preview`, payload())).data;
    } catch (error) {
        ruleError.value = error instanceof HttpError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('pricing::admin.price_lists_prices.could_not_preview');
    } finally {
        previewing.value = false;
    }
}

function applyBulk(): void {
    applying.value = true;
    router.post(`${listUrl.value}/bulk`, payload(), {
        preserveScroll: true,
        onSuccess: () => {
            bulkOpen.value = false;
            selected.value = [];
        },
        onFinish: () => (applying.value = false),
    });
}

// Thêm sản phẩm: SkuPicker (mặc định hiện sản phẩm, mở rộng để chọn từng SKU) → bước cách tính giá cho các SKU đã chọn.
const addOpen = ref(false);
const chosen = ref<number[]>([]);
const searchCandidates = async (term: string, filters: Record<string, string>): Promise<SkuPickerProduct[]> =>
    (await getJson<{ data: SkuPickerProduct[] }>(`${listUrl.value}/candidates`, { q: term, ...Object.fromEntries(Object.entries(filters).map(([key, value]) => [`ext[${key}]`, value])) })).data;
function continueToRule(): void {
    addOpen.value = false;
    openBulk({ scope: 'variants', variant_ids: [...chosen.value] }, t('pricing::admin.price_lists_prices.add_count_skus_name', { count: chosen.value.length, name: props.priceList.name }));
    chosen.value = [];
}

// Nhập/xuất file: file xuất cũng là mẫu nhập (sửa cột Giá / Giá gạch rồi nhập lại).
const importOpen = ref(false);
const importForm = useForm({ file: null as File | null, text: '' });
const importErrors = computed(() => Object.entries(importForm.errors as Record<string, string>).filter(([key]) => key.startsWith('import.') || key === 'file' || key === 'text').map(([, message]) => message));
function submitImport(): void {
    importForm.post(`${listUrl.value}/import`, { forceFormData: true, preserveScroll: true, onSuccess: () => {
        if (!importErrors.value.length) {
            importOpen.value = false;
            importForm.reset();
        }
    } });
}

const columns = computed(() => [
    { key: 'name', label: t('pricing::admin.price_lists_prices.product_sku') },
    ...(isBase.value ? [] : [{ key: 'list_amount', label: t('pricing::admin.price_lists_prices.list_price_2'), align: 'right' as const }]),
    { key: 'amount', label: isBase.value ? t('pricing::admin.price_lists_prices.list_price_2') : t('pricing::admin.price_lists_prices.price_list') },
    ...(isBase.value ? [] : [{ key: 'percent', label: t('pricing::admin.price_lists_prices.reduce') }]),
    { key: 'compare_at_amount', label: t('pricing::admin.price_lists_prices.compare_price_optional') },
]);
const previewColumns = [
    { key: 'name', label: 'SKU' },
    { key: 'list_amount', label: t('pricing::admin.price_lists_prices.list'), align: 'right' as const },
    { key: 'current_amount', label: t('pricing::admin.price_lists_prices.current'), align: 'right' as const },
    { key: 'new_amount', label: t('pricing::admin.price_lists_prices.new'), align: 'right' as const },
    { key: 'warnings', label: '' },
];
const warningCount = computed(() => Object.values(props.summary.warnings).reduce((sum, count) => sum + count, 0));
const warningLabel = (warning: string): string => (warning === 'over_cap' ? t('pricing::admin.price_lists_prices.discount_over_cap_percent', { cap_percent: props.capPercent }) : (props.labels.bulk.warnings[warning] ?? warning));
</script>

<template>
    <div>
    <Head :title="t('pricing::admin.price_lists_prices.price_list_name', { name: priceList.name })" />
    <p class="mb-2 text-sm text-slate-500"><Link :href="baseUrl" class="hover:underline">{{ t('pricing::admin.price_lists_prices.price_lists') }}</Link></p>
    <PageHeader :title="priceList.name" :subtitle="`${labels.price_list_type[priceList.type] ?? priceList.type} · ${priceList.starts_at || priceList.ends_at ? `${priceList.starts_at ?? '—'} → ${priceList.ends_at ?? '—'}` : t('pricing::admin.price_lists_prices.no_time_limit')}`">
        <StatusBadge :label="labels.price_list_state[priceList.state] ?? priceList.state" :tone="stateTone(priceList.state)" class="self-center" />
        <Link v-if="canManage" :href="`${listUrl}/edit`" :class="secondaryButton">{{ t('pricing::admin.price_lists_prices.price_list_details') }}</Link>
        <button v-if="editable" type="button" :class="primaryButton" data-add-products @click="addOpen = true">{{ t('pricing::admin.price_lists_prices.add_product') }}</button>
        <a :href="`${listUrl}/export`" :class="secondaryButton" data-export>{{ t('pricing::admin.price_lists_prices.export_file') }}</a>
        <button v-if="editable" type="button" :class="secondaryButton" data-import @click="importOpen = true">{{ t('pricing::admin.price_lists_prices.import_prices') }}</button>
        <Link v-if="readOnly" :href="productsUrl" :class="primaryButton">{{ t('pricing::admin.price_lists_prices.edit_prices_product_list') }}</Link>
    </PageHeader>

    <div v-if="readOnly" class="mb-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600" data-read-only>
        {{ t('pricing::admin.price_lists_prices.store_s_regular_selling_price') }}
    </div>
    <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600" data-summary>
        <span><strong>{{ summary.products }}</strong> {{ t('pricing::admin.price_lists_prices.products') }} <strong>{{ summary.variants }}</strong> SKU</span>
        <span v-for="(count, warning) in summary.warnings" :key="warning"><StatusBadge :label="`${warningLabel(String(warning))}: ${count}`" :tone="warningTone(String(warning))" /></span>
        <span v-if="!isBase && warningCount === 0 && summary.variants" class="text-xs text-slate-400">{{ t('pricing::admin.price_lists_prices.no_warnings') }}</span>
    </div>

    <form class="mb-3 flex max-w-lg gap-2" @submit.prevent="visit()">
        <input v-model="search" :class="inputClass" type="search" :placeholder="t('pricing::admin.price_lists_prices.search_price_list_name_product')" :aria-label="t('pricing::admin.price_lists_prices.search_price_list')" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">{{ t('pricing::admin.price_lists_prices.search') }}</button>
    </form>
    <ExtensionTools :tools="extensions.tools ?? []" />

    <DataTable
        v-model:selected="selected"
        :columns="columns"
        :rows="rows"
        :extensions="extensions"
        :selectable="editable"
        :extension-id="(row) => row.variant_id"
        :row-key="(row) => row.variant_id"
        :row-label="(row) => row.sku"
        :row-class="(row) => (row.status !== 'active' ? 'align-top text-slate-400' : 'align-top')"
        :empty-text="q ? t('pricing::admin.price_lists_prices.no_skus_price_list_match') : readOnly ? t('pricing::admin.price_lists_prices.no_list_prices_yet') : t('pricing::admin.price_lists_prices.price_list_has_no_products')"
    >
        <template #bulk="{ selected: ids, clear }">
            <button type="button" :class="secondaryButton" class="!py-1" @click="openBulk({ scope: 'variants', variant_ids: [...ids] }, t('pricing::admin.price_lists_prices.apply_price_count_selected_skus', { count: ids.length }))">{{ t('pricing::admin.price_lists_prices.apply_price') }}</button>
            <button type="button" :class="secondaryButton" class="!py-1" @click="removeSelected([...ids], clear)">{{ t('pricing::admin.price_lists_prices.remove_from_price_list') }}</button>
            <ExtensionActions :actions="extensions.actions" :ids="[...ids]" selection-description="SKU" />
            <button v-if="pagination.total > ids.length" type="button" class="text-indigo-700 hover:underline" @click="openBulk({ scope: 'all', q }, t('pricing::admin.price_lists_prices.apply_price_all', { total: pagination.total, scope: q ? t('pricing::admin.price_lists_prices.matching_search_suffix') : '' }))">
                {{ t('pricing::admin.price_lists_prices.apply_price_all', { total: pagination.total, scope: q ? t('pricing::admin.price_lists_prices.matching_search_suffix') : '' }) }}
            </button>
        </template>
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.sku }}<template v-if="row.variant_label"> · {{ row.variant_label }}</template></div>
        </template>
        <template #cell-list_amount="{ row }"><MoneyText v-if="row.list_amount !== null" :amount="row.list_amount" /><span v-else class="text-xs text-slate-400">{{ t('pricing::admin.price_lists_prices.not_set') }}</span></template>
        <template #cell-amount="{ row }">
            <input v-model.number="edits[row.variant_id].amount" :class="inputClass" class="max-w-36" type="number" min="0" step="1000" :disabled="!editable" :aria-label="t('pricing::admin.price_lists_prices.price_sku', { sku: row.sku })" />
        </template>
        <template #cell-percent="{ row }">
            <span v-if="row.percent !== null" class="font-medium text-emerald-700">−{{ row.percent }}%</span>
            <div v-for="warning in row.warnings" :key="warning" class="mt-0.5"><StatusBadge :label="warningLabel(warning)" :tone="warningTone(warning)" /></div>
        </template>
        <template #cell-compare_at_amount="{ row }">
            <input v-model.number="edits[row.variant_id].compare_at_amount" :class="inputClass" class="max-w-36" type="number" min="0" step="1000" :disabled="!editable" :aria-label="t('pricing::admin.price_lists_prices.compare_price_sku', { sku: row.sku })" />
        </template>
    </DataTable>
    <p v-for="(message, key) in saveForm.errors" :key="key" class="mt-2 text-sm text-red-600">{{ message }}</p>
    <div v-if="editable && dirtyRows.length" class="sticky bottom-0 mt-3 flex items-center gap-3 border-t border-slate-200 bg-white/95 py-3">
        <button type="button" :class="primaryButton" :disabled="saveForm.processing" data-save-edits @click="saveEdits">{{ t('pricing::admin.price_lists_prices.save_count_edited_prices', { count: dirtyRows.length }) }}</button>
        <span class="text-xs text-slate-500">{{ t('pricing::admin.price_lists_prices.empty_price_remove_sku_from') }}</span>
    </div>
    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="(page) => visit(page)" @per-page-change="(perPage) => visit(undefined, perPage)" />

    <Dialog v-model:open="importOpen" :title="t('pricing::admin.price_lists_prices.import_prices')" :dirty="importForm.isDirty && !importForm.processing">
        <div class="space-y-3 text-sm">
            <p class="text-slate-600">
                {{ t('pricing::admin.price_lists_prices.excel_xlsx_csv_file_columns') }} <strong>SKU</strong> {{ t('pricing::admin.price_lists_prices.text') }} <strong>{{ t('pricing::admin.price_lists_prices.price') }}</strong> {{ t('pricing::admin.price_lists_prices.first_row_column') }} <strong>{{ t('pricing::admin.price_lists_prices.compare_price') }}</strong> {{ t('pricing::admin.price_lists_prices.optional_easiest_click') }}
                <a :href="`${listUrl}/export`" class="text-indigo-600 hover:underline">{{ t('pricing::admin.price_lists_prices.export_file') }}</a>{{ t('pricing::admin.price_lists_prices.edit_price_column_import_back') }}
            </p>
            <ul class="list-disc pl-5 text-xs text-slate-500">
                <li>{{ t('pricing::admin.price_lists_prices.empty_price_cell_unchanged_0') }}</li>
                <li>{{ t('pricing::admin.price_lists_prices.skus_not_yet_list_added') }}</li>
            </ul>
            <FileInput v-model="importForm.file" accept=".xlsx,.csv" :max-kb="5120" :progress="importForm.progress?.percentage" @update:model-value="(file) => { if (file) importForm.text = ''; }" />
            <FormField :label="t('pricing::admin.price_lists_prices.paste_price_rows')">
                <textarea v-model="importForm.text" :class="inputClass" rows="4" maxlength="200000" :disabled="importForm.processing" data-paste-prices @input="importForm.file = null"></textarea>
            </FormField>
            <ul v-if="importErrors.length" class="max-h-48 space-y-1 overflow-y-auto rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-700" data-import-errors>
                <li v-for="message in importErrors" :key="message">{{ message }}</li>
            </ul>
        </div>
        <template #footer>
            <button type="button" :class="primaryButton" :disabled="(!importForm.file && !importForm.text.trim()) || importForm.processing" data-import-submit @click="submitImport">{{ t('pricing::admin.price_lists_prices.import_prices') }}</button>
        </template>
    </Dialog>

    <Dialog v-model:open="addOpen" :title="t('pricing::admin.price_lists_prices.add_products_price_list')" size="large" :dirty="chosen.length > 0">
        <SkuPicker v-model="chosen" :search="searchCandidates" :filters="skuPickerFilters" :placeholder="t('pricing::admin.price_lists_prices.type_name_product_code_sku')" />
        <template #footer>
            <button type="button" :class="primaryButton" :disabled="!chosen.length" data-continue @click="continueToRule">{{ t('pricing::admin.price_lists_prices.next_pricing_method_count_skus', { count: chosen.length }) }}</button>
        </template>
    </Dialog>

    <Dialog v-model:open="bulkOpen" :title="bulkTitle" size="large">
        <div class="space-y-4">
            <fieldset class="flex flex-wrap gap-2" :aria-label="t('pricing::admin.price_lists_prices.pricing_method')">
                <label v-for="option in modeOptions" :key="option.code" class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm" :class="rule.mode === option.code ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                    <input v-model="rule.mode" type="radio" :value="option.code" :data-mode="option.code" /> {{ option.label }}
                </label>
            </fieldset>
            <div class="flex flex-wrap items-center gap-3">
                <input v-model.number="rule.value" :class="inputClass" class="max-w-44" type="number" min="0" :step="['amount', 'fixed'].includes(rule.mode) ? 1000 : 1" :aria-label="currentMode.label" :placeholder="['amount', 'fixed'].includes(rule.mode) ? 'vd. 199000' : 'vd. 30'" />
                <span class="text-sm text-slate-500">{{ currentMode.hint }}</span>
                <label v-if="rule.mode !== 'fixed' && !rule.rounding" class="flex items-center gap-2 text-sm"><input v-model="rule.round" type="checkbox" /> {{ t('pricing::admin.price_lists_prices.round_1_000') }}</label>
                <Select v-if="bulkOptions.roundings.length" v-model="rule.rounding" :options="bulkOptions.roundings.map((option) => ({ value: option.code, label: option.label }))" :empty-label="t('pricing::admin.price_lists_prices.standard_rounding')" empty-value="" class="w-56" :aria-label="t('pricing::admin.price_lists_prices.rounding')" />
            </div>
            <p v-if="ruleError" class="text-sm text-red-600">{{ ruleError }}</p>
            <button type="button" :class="secondaryButton" :disabled="previewing" data-preview @click="loadPreview">{{ t('pricing::admin.price_lists_prices.preview') }}</button>

            <div v-if="preview" class="space-y-2" data-preview-result>
                <p class="text-sm">
                    <strong>{{ preview.summary.changed }}</strong> {{ t('pricing::admin.price_lists_prices.skus_changing_price_total_skus', { total: preview.summary.total }) }}
                    <template v-if="preview.summary.skipped"> {{ t('pricing::admin.price_lists_prices.skipped_skipped_no_list_price', { skipped: preview.summary.skipped }) }}</template>
                </p>
                <div class="flex flex-wrap gap-2">
                    <StatusBadge v-for="(count, warning) in preview.summary.warnings" :key="warning" :label="`${warningLabel(String(warning))}: ${count}`" :tone="warningTone(String(warning))" />
                </div>
                <p v-if="Object.keys(preview.summary.warnings).some((warning) => warning !== 'no_base')" class="text-xs text-slate-500">{{ t('pricing::admin.price_lists_prices.warnings_reminders_only_you_still') }}</p>
                <div class="max-h-72 overflow-y-auto rounded-md border border-slate-200">
                    <DataTable :columns="previewColumns" :rows="preview.rows" :row-key="(row) => row.variant_id" bare>
                        <template #cell-name="{ row }"><div>{{ row.name }}</div><div class="font-mono text-xs text-slate-400">{{ row.sku }}</div></template>
                        <template #cell-list_amount="{ row }"><MoneyText v-if="row.list_amount !== null" :amount="row.list_amount" /><template v-else>—</template></template>
                        <template #cell-current_amount="{ row }"><MoneyText v-if="row.current_amount !== null" :amount="row.current_amount" class="text-slate-500" /><template v-else>—</template></template>
                        <template #cell-new_amount="{ row }">
                            <span class="font-medium"><MoneyText v-if="row.new_amount !== null" :amount="row.new_amount" /><template v-else>—</template></span>
                            <span v-if="row.percent !== null" class="ml-1 text-xs text-emerald-700">−{{ row.percent }}%</span>
                        </template>
                        <template #cell-warnings="{ row }"><StatusBadge v-for="warning in row.warnings" :key="warning" :label="warningLabel(warning)" :tone="warningTone(warning)" /></template>
                    </DataTable>
                </div>
                <p v-if="preview.summary.total > preview.rows.length" class="text-xs text-slate-500">{{ t('pricing::admin.price_lists_prices.showing_first_count_rows_summary', { count: preview.rows.length, total: preview.summary.total }) }}</p>
            </div>
        </div>
        <template #footer>
            <button type="button" :class="primaryButton" :disabled="!preview || applying || preview.summary.changed === 0" data-apply @click="applyBulk">{{ t('pricing::admin.price_lists_prices.apply_changed_skus', { changed: preview?.summary.changed ?? 0 }) }}</button>
        </template>
    </Dialog>
    </div>
</template>
