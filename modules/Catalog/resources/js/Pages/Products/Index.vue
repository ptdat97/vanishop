<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import FileInput from '@admin/Components/FileInput.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import { useListQuery, type ListSortState } from '@admin/list';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { getJson } from '@admin/http';
import { labelOptions, recordOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    filters: { q: string; status: string | null; category: number | null; brand: string | null; collection: number | null; missing: string | null };
    filterOptions: Record<'categories' | 'brands' | 'collections', Array<{ id: number; label: string }>> & { missing: Record<string, string> };
    sort: ListSortState | null;
    /** Lượt nhập file chạy nền vừa bắt đầu — hỏi tiến độ ở URL này. */
    importRunUrl: string | null;
    products: {
        data: Array<{ id: number; product_code: string; name: string | null; brand: string | null; status: string; skus: number; image_url: string | null; views: number; missing: string[] }>;
        current: number;
        last: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
    };
    canManage: boolean;
    canManagePrices: boolean;
    fileUrls: { export: string; import: string };
    extensions: ExtensionList;
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'products')?.url ?? '');
const q = ref(props.filters.q);
const status = ref(props.filters.status ?? '');
const category = ref<number | ''>(props.filters.category ?? '');
const brand = ref(props.filters.brand ?? '');
const collection = ref<number | ''>(props.filters.collection ?? '');
const missing = ref(props.filters.missing ?? '');
const brandOptions = computed(() => [{ value: 'none', label: t('catalog::admin.products_index.no_brand') }, ...props.filterOptions.brands.map((item) => ({ value: String(item.id), label: item.label }))]);
const perPageOptions = [30, 50, 100];
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
// "Chọn tất cả kết quả lọc" (A5): id mọi sản phẩm khớp bộ lọc đang áp dụng (tối đa 2000) — thao tác hàng loạt nhận danh sách này.
const matching = ref<{ ids: number[]; total: number; capped: boolean } | null>(null);
const loadingMatching = ref(false);
const actionIds = computed(() => matching.value?.ids ?? selected.value);
const pageFullySelected = computed(() => props.products.data.length > 0 && props.products.data.every((row) => selected.value.includes(row.id)));
watch(
    () => props.products,
    () => (matching.value = null),
);

async function selectAllMatching(): Promise<void> {
    loadingMatching.value = true;
    const query: Record<string, string> = { q: q.value, status: status.value, category: String(category.value), brand: brand.value, collection: String(collection.value), missing: missing.value };
    for (const [key, value] of Object.entries(ext)) query[`ext[${key}]`] = value;
    try {
        matching.value = await getJson(`${baseUrl.value}/ids`, query);
    } finally {
        loadingMatching.value = false;
    }
}
const editingPriceId = ref<number | null>(null);
const priceDraft = ref('');
const priceError = ref('');
const savingPrice = ref(false);
// Sửa tồn trong ô (cửa hàng một kho): Inventory gửi sẵn URL lưu, kho và tồn từng màu/size; chỉ gửi ô đã đổi.
type StockEdit = { url: string; location_id: number; rows: Array<{ variant_id: number; label: string; on_hand: number }> };
const editingStockId = ref<number | null>(null);
const stockDraft = ref<Record<number, string>>({});
const stockError = ref('');
const savingStock = ref(false);
const columns = [
    { key: 'image', label: '', class: 'w-16' },
    { key: 'name', label: t('catalog::admin.products_index.products'), class: 'font-medium' },
    { key: 'product_code', label: t('catalog::admin.products_index.code'), class: 'font-mono text-xs', sortable: true },
    { key: 'brand', label: t('catalog::admin.products_index.brand') },
    { key: 'skus', label: 'SKU' },
    { key: 'views', label: t('catalog::admin.products_index.views_30_days'), align: 'right' as const, class: 'tabular-nums' },
    { key: 'status', label: t('catalog::admin.products_index.status'), sortable: true },
];

const list = useListQuery(() => baseUrl.value);
// Nhập/xuất file (A4): mỗi SKU một dòng; file xuất là mẫu để nhập lại; nhập → trang xem trước, chưa ghi gì.
const showImport = ref(false);
const importForm = useForm<{ file: File | null }>({ file: null });

function submitImport(): void {
    importForm.post(props.fileUrls.import, { forceFormData: true });
}

function search(page = 1, pageSize = props.products.per_page, sort = props.sort): void {
    list.visit({ q: q.value, status: status.value, category: category.value, brand: brand.value, collection: collection.value, missing: missing.value, ext: { ...ext }, ...list.sortQuery(sort), page, per_page: pageSize });
}

function changePageSize(pageSize: number): void {
    search(1, pageSize);
}

function editProduct(product: { id: number }): void {
    router.visit(`${baseUrl.value}/${product.id}/edit`);
}

function openPriceEditor(productId: number, amount: number | null): void {
    editingPriceId.value = productId;
    priceDraft.value = amount === null ? '' : String(amount);
    priceError.value = '';
}

function closePriceEditor(): void {
    editingPriceId.value = null;
    priceError.value = '';
}

function openStockEditor(productId: number, edit: StockEdit): void {
    editingStockId.value = productId;
    stockDraft.value = Object.fromEntries(edit.rows.map((row) => [row.variant_id, String(row.on_hand)]));
    stockError.value = '';
}

function closeStockEditor(): void {
    editingStockId.value = null;
    stockError.value = '';
}

function saveStock(edit: StockEdit): void {
    if (savingStock.value) {
        return;
    }
    const changes = edit.rows
        .filter((row) => stockDraft.value[row.variant_id] !== '' && Number(stockDraft.value[row.variant_id]) !== row.on_hand)
        .map((row) => ({ location_id: edit.location_id, variant_id: row.variant_id, on_hand: Number(stockDraft.value[row.variant_id]), expected: row.on_hand }));
    if (!changes.length) {
        closeStockEditor();
        return;
    }

    stockError.value = '';
    savingStock.value = true;
    router.post(edit.url, { changes, reason: t('catalog::admin.products_index.quick_edit_product_list') }, {
        preserveScroll: true,
        onError: (errors) => {
            stockError.value = Object.values(errors)[0] ?? t('catalog::admin.products_index.could_not_update_stock');
        },
        onSuccess: () => {
            editingStockId.value = null;
        },
        onFinish: () => {
            savingStock.value = false;
        },
    });
}

function savePrice(url: string): void {
    if (editingPriceId.value === null || savingPrice.value) {
        return;
    }

    priceError.value = '';
    savingPrice.value = true;
    router.put(url, { amount: priceDraft.value }, {
        preserveScroll: true,
        onError: (errors) => {
            priceError.value = errors.amount ?? t('catalog::admin.products_index.could_not_update_price');
        },
        onSuccess: () => {
            editingPriceId.value = null;
        },
        onFinish: () => {
            savingPrice.value = false;
        },
    });
}

// Nhập file lớn chạy nền: hỏi tiến độ mỗi 2 giây; xong thì tải lại danh sách.
type ImportRun = { label: string; progress: number; finished: boolean; failed_jobs: number; counts: { created: number; updated: number; failed_rows: number }; errors: string[] };
const importRun = ref<ImportRun | null>(null);
let importTimer: ReturnType<typeof setTimeout> | null = null;
async function pollImport(): Promise<void> {
    if (!props.importRunUrl) return;
    try {
        importRun.value = (await getJson<{ data: ImportRun }>(props.importRunUrl)).data;
    } catch {
        importRun.value = null;
        return;
    }
    if (importRun.value.finished) {
        router.reload({ only: ['products'] });
        return;
    }
    importTimer = setTimeout(pollImport, 2000);
}
void pollImport();
onBeforeUnmount(() => importTimer && clearTimeout(importTimer));
</script>

<template>
    <Head :title="t('catalog::admin.products_index.products')" />
    <CatalogTabs :nav="nav" active="products" />
    <PageHeader :title="t('catalog::admin.products_index.products')" :subtitle="t('catalog::admin.products_index.total_products', { total: products.total })">
        <a :href="fileUrls.export" :class="secondaryButton" data-export>{{ t('catalog::admin.products_index.export_file') }}</a>
        <button v-if="canManage" type="button" :class="secondaryButton" data-import-toggle @click="showImport = !showImport">{{ t('catalog::admin.products_index.import_file') }}</button>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('catalog::admin.products_index.add_product') }}</Link>
    </PageHeader>

    <section v-if="importRun" class="mb-4 rounded-lg border border-slate-200 bg-white p-4 text-sm" role="status" aria-live="polite" data-import-run>
        <p class="font-medium">{{ importRun.label }}</p>
        <div class="mt-2 h-2 overflow-hidden rounded bg-slate-100"><div class="h-full bg-indigo-600 transition-all" :style="{ width: `${importRun.progress}%` }"></div></div>
        <p class="mt-2 text-slate-600">
            {{ importRun.finished ? t('catalog::admin.products_index.import_run_done') : t('catalog::admin.products_index.import_run_progress', { progress: importRun.progress }) }}
            {{ t('catalog::admin.products_index.import_run_counts', { created: importRun.counts.created, updated: importRun.counts.updated }) }}
            <template v-if="importRun.counts.failed_rows || importRun.failed_jobs">{{ t('catalog::admin.products_index.import_run_failed', { rows: importRun.counts.failed_rows }) }}</template>
        </p>
        <ul v-if="importRun.errors.length" class="mt-2 max-h-48 list-disc overflow-y-auto pl-5 text-red-700">
            <li v-for="(error, index) in importRun.errors" :key="index">{{ error }}</li>
        </ul>
    </section>

    <form v-if="showImport" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" data-import @submit.prevent="submitImport">
        <p>{{ t('catalog::admin.products_index.excel_xlsx_csv_file_one') }} <strong>{{ t('catalog::admin.products_index.product_code') }}</strong> {{ t('catalog::admin.products_index.form_one_product_use_export') }}</p>
        <FileInput v-model="importForm.file" accept=".xlsx,.csv" :max-kb="20480" :label="t('catalog::admin.products_index.choose_file')" :progress="importForm.progress?.percentage ?? null" />
        <p v-for="(error, key) in importForm.errors" :key="key" class="text-red-600">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="!importForm.file || importForm.processing">{{ t('catalog::admin.products_index.preview') }}</button></div>
    </form>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="inputClass" class="max-w-xs" type="search" :placeholder="t('catalog::admin.products_index.search_name_code_accents_optional')" :aria-label="t('catalog::admin.products_index.search_products')" />
        <div class="w-44"><Select v-model="status" :options="labelOptions(labels.product_status)" :empty-label="t('catalog::admin.products_index.all_statuses')" :aria-label="t('catalog::admin.products_index.status')" /></div>
        <div v-if="filterOptions.categories.length" class="w-52"><Select v-model="category" :options="recordOptions(filterOptions.categories)" :empty-label="t('catalog::admin.products_index.all_categories')" :aria-label="t('catalog::admin.products_index.category_including_subcategories')" /></div>
        <div class="w-48"><Select v-model="brand" :options="brandOptions" :empty-label="t('catalog::admin.products_index.all_brands')" :aria-label="t('catalog::admin.products_index.brand')" /></div>
        <div class="w-48"><Select v-model="missing" :options="labelOptions(filterOptions.missing)" :empty-label="t('catalog::admin.products_index.complete_missing_data')" :aria-label="t('catalog::admin.products_index.missing_data')" /></div>
        <div v-if="filterOptions.collections.length" class="w-48"><Select v-model="collection" :options="recordOptions(filterOptions.collections)" :empty-label="t('catalog::admin.products_index.all_collections')" :aria-label="t('catalog::admin.products_index.collections')" /></div>
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">{{ t('catalog::admin.products_index.filter') }}</button>
    </form>

    <div v-if="extensions.actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500" data-selected-count>{{ t('catalog::admin.products_index.count_selected', { count: actionIds.length }) }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="actionIds" :selection-description="t('catalog::admin.products_index.products_2')" />
    </div>
    <div v-if="matching" class="mb-2 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-900" data-select-matching>
        {{ t('catalog::admin.products_index.all_count_products_matching_filter', { count: matching.ids.length }) }}<span v-if="matching.capped"> {{ t('catalog::admin.products_index.most_count_total_narrow_filter', { count: matching.ids.length, total: matching.total }) }}</span>.
        <button type="button" class="ml-1 underline" @click="matching = null">{{ t('catalog::admin.products_index.select_only_page') }}</button>
    </div>
    <div v-else-if="extensions.actions.length && pageFullySelected && products.total > products.data.length" class="mb-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm" data-select-matching>
        {{ t('catalog::admin.products_index.count_products_page_selected', { count: products.data.length }) }}
        <button type="button" class="ml-1 font-medium text-indigo-700 underline" :disabled="loadingMatching" @click="selectAllMatching">{{ t('catalog::admin.products_index.select_all_total_products_matching', { total: products.total }) }}</button>
    </div>
    <DataTable v-model:selected="selected" :columns="columns" :rows="products.data" :extensions="extensions" :sort="sort" :loading="list.loading.value" @sort="(next) => search(1, products.per_page, next)" :row-label="(product) => product.product_code" :row-click="canManage ? editProduct : undefined" :empty-text="t('catalog::admin.products_index.no_products')">
        <template #cell-image="{ row }">
            <img v-if="row.image_url" :src="row.image_url" alt="" class="h-12 w-10 rounded object-cover" />
            <div v-else class="h-12 w-10 rounded bg-slate-100" />
        </template>
        <template #cell-name="{ row }">
            <span>{{ row.name ?? '—' }}</span>
            <!-- Thiếu dữ liệu: chỉ icon (rê chuột xem nhanh); chi tiết ở trang sửa sản phẩm. -->
            <span
                v-if="row.missing.length"
                class="ml-1.5 inline-flex items-center gap-0.5 align-middle text-xs font-normal text-amber-600"
                role="img"
                :title="t('catalog::admin.products_index.missing_count_items', { count: row.missing.length, items: row.missing.join(', ') })"
                :aria-label="t('catalog::admin.products_index.missing_count_items', { count: row.missing.length, items: row.missing.join(', ') })"
                data-missing
            >
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                {{ row.missing.length }}
            </span>
        </template>
        <template #cell-brand="{ row }">{{ row.brand ?? '—' }}</template>
        <template #cell-price="{ row, value, editValue, url }">
            <div v-if="canManagePrices && url" class="min-w-52">
                <form v-if="editingPriceId === row.id" class="flex flex-wrap items-center gap-2" @submit.prevent="savePrice(url)">
                    <label class="sr-only" :for="`base-price-${row.id}`">{{ t('catalog::admin.products_index.list_price_every_size') }}</label>
                    <input
                        :id="`base-price-${row.id}`"
                        v-model="priceDraft"
                        :class="inputClass"
                        class="w-32"
                        type="number"
                        min="1"
                        max="1000000000"
                        step="1"
                        required
                        :placeholder="t('catalog::admin.products_index.new_price')"
                    />
                    <button type="submit" :class="primaryButton" :disabled="savingPrice">{{ savingPrice ? t('catalog::admin.products_index.saving') : t('catalog::admin.products_index.save') }}</button>
                    <button type="button" :class="secondaryButton" :disabled="savingPrice" @click="closePriceEditor">{{ t('catalog::admin.products_index.cancel') }}</button>
                    <span v-if="priceError" class="basis-full text-xs text-rose-700" role="alert">{{ priceError }}</span>
                    <span class="basis-full text-xs text-slate-500">{{ t('catalog::admin.products_index.current_count_apply_new_list', { count: value }) }}</span>
                </form>
                <div v-else class="flex items-start justify-between gap-2">
                    <span>{{ value }}</span>
                    <button type="button" class="shrink-0 text-indigo-600 hover:underline" @click="openPriceEditor(row.id, editValue as number | null)">{{ t('catalog::admin.products_index.edit') }}</button>
                </div>
            </div>
            <span v-else>{{ value }}</span>
        </template>
        <template #cell-stock="{ row, value, editValue, url }">
            <form v-if="editValue && editingStockId === row.id" class="min-w-52 space-y-1" data-stock-editor @submit.prevent="saveStock(editValue as StockEdit)" @click.stop>
                <label v-for="line in (editValue as StockEdit).rows" :key="line.variant_id" class="flex items-center justify-between gap-2 text-xs">
                    <span class="text-slate-600">{{ line.label }}</span>
                    <input v-model="stockDraft[line.variant_id]" :class="inputClass" class="w-20 text-right tabular-nums" type="number" min="0" max="1000000" step="1" required :aria-label="t('catalog::admin.products_index.stock_label', { label: line.label })" />
                </label>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <button type="submit" :class="primaryButton" :disabled="savingStock">{{ savingStock ? t('catalog::admin.products_index.saving') : t('catalog::admin.products_index.save') }}</button>
                    <button type="button" :class="secondaryButton" :disabled="savingStock" @click="closeStockEditor">{{ t('catalog::admin.products_index.cancel') }}</button>
                </div>
                <span v-if="stockError" class="block text-xs text-rose-700" role="alert">{{ stockError }}</span>
                <span class="block text-xs text-slate-500">{{ t('catalog::admin.products_index.actual_stock_warehouse_before_subtracting') }}</span>
            </form>
            <div v-else class="flex items-start justify-between gap-2">
                <Link v-if="url" :href="url" class="text-indigo-600 hover:underline" :aria-label="t('catalog::admin.products_index.open_stock_grid_product_code', { product_code: row.name ?? row.product_code })">{{ value }}</Link>
                <span v-else>{{ value }}</span>
                <button v-if="editValue" type="button" class="shrink-0 text-indigo-600 hover:underline" data-stock-edit @click.stop="openStockEditor(row.id, editValue as StockEdit)">{{ t('catalog::admin.products_index.edit') }}</button>
            </div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.product_status[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : row.status === 'draft' ? 'warning' : 'neutral'" />
        </template>
    </DataTable>

    <Pagination :current="products.current" :last="products.last" :from="products.from" :to="products.to" :total="products.total" :per-page="products.per_page" :per-page-options="perPageOptions" @change="search" @per-page-change="changePageSize" />
</template>
