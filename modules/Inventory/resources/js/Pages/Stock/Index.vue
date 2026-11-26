<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import ExtensionTools from '@admin/Components/Extensions/ExtensionTools.vue';
import FileInput from '@admin/Components/FileInput.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { getJson } from '@admin/http';
import { useListQuery } from '@admin/list';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import type { InventoryLabels } from '../../labels';

type Level = { on_hand: number; reserved: number; safety_stock: number; available: number };
type Variant = { id: number; sku: string; product_code: string; product_name: string; options_label: string; status: string; sellable: number; tracked: boolean; levels: Level[] };
type Product = { product_code: string; product_name: string; tracked: boolean; sellable: number; variants: Variant[] };
type Row = Variant & { first: boolean; product: Product; index: number };
type StockLocation = { id: number; code: string; name: string; external: boolean; online: boolean };
type Criteria = { q: string; product: string; filter: 'all' | 'out' | 'low'; options: Record<string, string>; ext?: Record<string, string> };
type OptionFilter = { code: string; name: string; values: string[] };

/**
 * Tồn kho: sản phẩm × kho. Tìm theo tên sản phẩm, mã hoặc SKU; lọc theo giá trị tuỳ chọn (màu, size…), hết hàng / sắp hết. Gõ số vào ô rồi Lưu,
 * hoặc chọn nhiều SKU (cả sản phẩm, cả trang, mọi kết quả lọc) để sửa hàng loạt tại một kho. Enter = xuống ô dưới.
 */
const props = defineProps<{
    labels: InventoryLabels;
    bulkRun: { url: string } | null;
    urls: { base: string; batch: string; bulk: string; change: string; export: string; import: string; movements: string; receipts: string; counts: string; provinces: string; locations: string | null; default_location: string | null; main_location: string | null };
    links: Array<{ label: string; url: string }>;
    mainLocation: { id: number; name: string; address: string | null; province_code: string | null; lock_version: number } | null;
    query: Criteria;
    optionFilters: OptionFilter[];
    pagination: { page: number; per_page: number; total: number; variants: number };
    threshold: number;
    warnings: { no_location: boolean; no_online_location: boolean };
    locations: StockLocation[];
    products: Product[];
    canAdjust: boolean;
    /** Cột/lọc/thao tác hàng loạt/công cụ của plugin theo SKU (EP v3 X1). */
    extensions: ExtensionList;
}>();

const page = usePage<{ errors: Record<string, string> }>();
// PHP trả mảng rỗng `[]` khi không lọc tuỳ chọn → chuẩn hoá thành object.
const criteria = reactive<Criteria>({ ...props.query, options: { ...(Array.isArray(props.query.options) ? {} : props.query.options) } });
/** Bộ lọc của plugin (EP v3 X1). */
const ext = reactive<Record<string, string>>({ ...(Array.isArray(props.query.ext) ? {} : (props.query.ext ?? {})) });
const filterOptions = computed(() => [
    { value: 'all', label: t('inventory::admin.stock_index.all') },
    { value: 'out', label: t('inventory::admin.stock_index.out_stock_online') },
    { value: 'low', label: t('inventory::admin.stock_index.low_stock_threshold', { threshold: props.threshold }) },
]);
const valueOptions = (filter: OptionFilter) => filter.values.map((code) => ({ value: code, label: code }));
const hasOptionFilter = computed(() => Object.values(props.query.options ?? {}).some((code) => code !== ''));
const rows = computed<Row[]>(() => {
    let index = 0;
    return props.products.flatMap((product) => product.variants.map((variant, position) => ({ ...variant, first: position === 0, product, index: index++ })));
});
const columns = computed(() => [
    ...(props.canAdjust ? [{ key: 'select', label: '' }] : []),
    { key: 'product', label: t('inventory::admin.stock_index.product_sku') },
    { key: 'sellable', label: t('inventory::admin.stock_index.sellable_online'), align: 'right' as const },
    ...props.locations.map((location, index) => ({ key: `location-${index}`, label: location.code })),
    { key: 'actions', label: '', align: 'right' as const },
]);
const lastPage = computed(() => Math.max(1, Math.ceil(props.pagination.total / props.pagination.per_page)));
const firstResult = computed(() => (props.pagination.total === 0 ? null : (props.pagination.page - 1) * props.pagination.per_page + 1));
const lastResult = computed(() => Math.min(props.pagination.page * props.pagination.per_page, props.pagination.total));
const editableLocations = computed(() => props.locations.filter((location) => !location.external));

/* ---------- Sửa từng ô ---------- */
/** Ô đang sửa: "kho:variant" → số tồn mới. */
const edits = reactive<Record<string, number>>({});
const reason = ref('');
const saving = ref(false);
const dirty = computed(() => Object.keys(edits).length);
const cellKey = (location: StockLocation, row: Variant) => `${location.id}:${row.id}`;
const editable = (location: StockLocation) => props.canAdjust && !location.external;

function onInput(location: StockLocation, row: Variant, index: number, raw: string): void {
    const value = Number.parseInt(raw, 10);
    const key = cellKey(location, row);
    if (Number.isNaN(value) || value < 0 || value === row.levels[index].on_hand) {
        delete edits[key];
    } else {
        edits[key] = value;
    }
}

/** Enter (Shift+Enter: lên) chuyển sang ô cùng kho ở dòng kế — gõ số kiểm kê liên tục bằng bàn phím. */
function moveFocus(event: KeyboardEvent, rowIndex: number, locationIndex: number): void {
    event.preventDefault();
    const target = document.querySelector<HTMLInputElement>(`[data-cell="${rowIndex + (event.shiftKey ? -1 : 1)}-${locationIndex}"]`);
    target?.focus();
    target?.select();
}

function discard(): void {
    for (const key of Object.keys(edits)) delete edits[key];
    reason.value = '';
}

function save(): void {
    const changes = rows.value.flatMap((row) =>
        props.locations.flatMap((location, index) => {
            const value = edits[cellKey(location, row)];
            return value === undefined ? [] : [{ location_id: location.id, variant_id: row.id, on_hand: value, expected: row.levels[index].on_hand }];
        }),
    );
    saving.value = true;
    router.post(props.urls.batch, { changes, reason: reason.value || null }, { preserveScroll: true, onSuccess: discard, onFinish: () => (saving.value = false) });
}

/* ---------- Lọc, phân trang ---------- */
const perPageOptions = [20, 50, 100];
const list = useListQuery(() => props.urls.base);

function params(overrides: Partial<Criteria> & { page?: number; per_page?: number } = {}): Record<string, string | number | Record<string, string> | undefined> {
    const merged = { ...criteria, ...overrides };
    return {
        q: merged.q.trim() || undefined, product: merged.product || undefined, filter: merged.filter === 'all' ? undefined : merged.filter,
        opt: Object.fromEntries(Object.entries(merged.options).filter(([, code]) => code !== '')),
        ext: Object.fromEntries(Object.entries(ext).filter(([, value]) => value !== '')), page: overrides.page && overrides.page > 1 ? overrides.page : undefined,
        per_page: overrides.per_page ?? props.pagination.per_page,
    };
}

async function visit(query: Record<string, string | number | Record<string, string> | undefined>): Promise<void> {
    if (dirty.value && (await confirmAction({ title: t('inventory::admin.stock_index.discard_dirty_unsaved_cells', { dirty: dirty.value }), confirmLabel: t('inventory::admin.stock_index.discard_changes'), tone: 'danger' })) === false) return;
    discard();
    clearSelection();
    list.visit(query);
}

function find(): void {
    void visit(params({ product: '' }));
}

function changePageSize(perPage: number): void {
    void visit(params({ page: 1, per_page: perPage }));
}

function clearFilters(): void {
    Object.assign(criteria, { q: '', product: '', filter: 'all', options: {} });
    Object.keys(ext).forEach((key) => (ext[key] = ''));
    void visit({});
}

/* ---------- Chọn nhiều + sửa hàng loạt ---------- */
const selected = ref<number[]>([]);
/** Chọn mọi SKU khớp bộ lọc (kể cả trang khác) — server tự lấy danh sách theo điều kiện lọc. */
const allMatching = ref(false);
const pageIds = computed(() => rows.value.map((row) => row.id));
const pageAllSelected = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selected.value.includes(id)));
const selectedCount = computed(() => (allMatching.value ? props.pagination.variants : selected.value.length));
const selectionScope = computed(() => (allMatching.value ? t('inventory::admin.stock_index.matching_filter_every_page') : t('inventory::admin.stock_index.current_page')));
watch(selected, () => {
    if (!pageAllSelected.value) allMatching.value = false;
});

function clearSelection(): void {
    selected.value = [];
    allMatching.value = false;
}

function toggle(ids: number[], on: boolean): void {
    selected.value = on ? [...new Set([...selected.value, ...ids])] : selected.value.filter((id) => !ids.includes(id));
}

const productIds = (product: Product) => product.variants.map((variant) => variant.id);
const productSelected = (product: Product) => productIds(product).every((id) => selected.value.includes(id));

const bulk = reactive({ location_id: null as number | null, action: 'set', quantity: 0, reason: '' });
const bulkActions = computed(() => labelOptions(props.labels.bulk_action));
const bulkLocationOptions = computed(() =>
    props.locations
        .filter((location) => bulk.action === 'safety' || !location.external)
        .map((location) => ({ value: location.id, label: `${location.code} — ${location.name}` })),
);
const bulkProcessing = ref(false);
watch(
    () => props.locations,
    () => {
        bulk.location_id ??= editableLocations.value.find((location) => location.online)?.id ?? editableLocations.value[0]?.id ?? null;
    },
    { immediate: true },
);

async function applyBulk(): Promise<void> {
    const location = props.locations.find((item) => item.id === bulk.location_id);
    if (!location) return;
    const action = bulk.action === 'zero' ? 'set' : bulk.action;
    const quantity = bulk.action === 'zero' ? 0 : bulk.quantity;
    const what: Record<string, string> = {
        set: t('inventory::admin.stock_index.set_stock_quantity', { quantity }),
        adjust: `${quantity >= 0 ? t('inventory::admin.stock_index.add') : t('inventory::admin.stock_index.subtract')} ${Math.abs(quantity)}`,
        safety: t('inventory::admin.stock_index.set_safety_stock_quantity', { quantity }),
    };
    const confirmed = await confirmAction({
        title: t('inventory::admin.stock_index.action_selected_count_skus_selection', { action: what[action], selected_count: selectedCount.value, selection_scope: selectionScope.value, code: location.code }),
        message: t('inventory::admin.stock_index.movement_recorded_hint', { prefix: action === 'adjust' ? t('inventory::admin.stock_index.adjust_skip_hint') : '' }),
        confirmLabel: t('inventory::admin.stock_index.apply'),
    });
    if (confirmed === false) return;

    bulkProcessing.value = true;
    router.post(
        props.urls.bulk,
        {
            location_id: location.id, action, quantity, reason: bulk.reason || null,
            ...(allMatching.value ? { all: true, criteria: props.query } : { variant_ids: selected.value }),
        },
        { preserveScroll: true, onSuccess: () => { clearSelection(); bulk.reason = ''; }, onFinish: () => (bulkProcessing.value = false) },
    );
}

/* ---------- Thao tác nâng cao một ô ---------- */
const advanced = ref<null | { sku: string; location: string }>(null);
const form = useForm({ location_id: 0, variant_id: 0, action: 'adjust', quantity: 0, reason: '' });
const advancedActions = computed(() => labelOptions(props.labels.stock_action).filter((option) => option.value !== 'count'));

function openAdvanced(row: Variant, location: StockLocation): void {
    form.reset();
    form.clearErrors();
    form.location_id = location.id;
    form.variant_id = row.id;
    advanced.value = { sku: row.sku, location: location.code };
}

function submitAdvanced(): void {
    form.post(props.urls.change, { preserveScroll: true, onSuccess: () => (advanced.value = null) });
}

/* ---------- Kho chính (cửa hàng một kho) ---------- */
const editingLocation = ref(false);
const { form: locationForm, save: saveLocationForm } = useAdminForm({
    name: props.mainLocation?.name ?? '', address: props.mainLocation?.address ?? '', province_code: props.mainLocation?.province_code ?? '', lock_version: props.mainLocation?.lock_version ?? 0,
}, { lockVersion: () => props.mainLocation?.lock_version });

// Tỉnh/thành chọn từ danh mục địa giới của cửa hàng (plugin, vd. vani.provinces-vn); không có danh mục → nhập mã tay.
const provinces = ref<Array<{ value: string; label: string }>>([]);
watch(editingLocation, async (open) => {
    // Đóng khung mà không lưu → bỏ thay đổi (không còn "dữ liệu chưa lưu" ẩn khi rời trang).
    if (!open) locationForm.resetAndClearErrors();
    if (!open || provinces.value.length) return;
    try {
        const response = await getJson<{ data: Array<{ code: string; name: string }> }>(props.urls.provinces);
        provinces.value = response.data.map((province) => ({ value: province.code, label: province.name }));
    } catch {
        provinces.value = [];
    }
});

function saveLocation(): void {
    if (props.urls.main_location) saveLocationForm('put', props.urls.main_location, { onSuccess: () => (editingLocation.value = false) });
}

/* ---------- Nhập CSV, kho mặc định ---------- */
const importForm = useForm<{ file: File | null; location_id: number | null }>({ file: null, location_id: null });
const importLocations = computed(() => editableLocations.value.map((location) => ({ value: location.id, label: `${location.code} — ${location.name}` })));
const showImport = ref(false);

function submitImport(): void {
    importForm.post(props.urls.import, { forceFormData: true });
}

function createDefault(): void {
    if (props.urls.default_location) router.post(props.urls.default_location, {}, { preserveScroll: true });
}

// Việc hàng loạt chạy nền (L20): hỏi tiến độ mỗi 2 giây; xong thì tải lại lưới tồn và hiện kết quả.
type RunStatus = { id: string; label: string; progress: number; finished: boolean; failed_jobs: number; counts: { updated: number; unchanged: number; skipped: number; conflicts: number } };
const run = ref<RunStatus | null>(null);
let runTimer: ReturnType<typeof setTimeout> | null = null;
async function pollRun(): Promise<void> {
    if (!props.bulkRun) return;
    try {
        run.value = (await getJson<{ data: RunStatus }>(props.bulkRun.url)).data;
    } catch {
        run.value = null;
        return;
    }
    if (run.value.finished) {
        router.reload({ only: ['products', 'pagination'] });
        return;
    }
    runTimer = setTimeout(pollRun, 2000);
}
pollRun();
onBeforeUnmount(() => runTimer && clearTimeout(runTimer));
</script>

<template>
    <Head :title="t('inventory::admin.stock_index.inventory')" />
    <PageHeader :title="t('inventory::admin.stock_index.inventory')" :subtitle="t('inventory::admin.stock_index.type_stock_into_cells_enter')">
        <Link :href="urls.receipts" :class="secondaryButton" data-receipts>{{ t('inventory::admin.stock_index.goods_receipts') }}</Link>
        <Link :href="urls.counts" :class="secondaryButton" data-counts>{{ t('inventory::admin.stock_index.stock_counts') }}</Link>
        <a :href="urls.export" :class="secondaryButton">{{ t('inventory::admin.stock_index.export_csv') }}</a>
        <button v-if="canAdjust && locations.length" type="button" :class="secondaryButton" @click="showImport = !showImport">{{ t('inventory::admin.stock_index.import_csv') }}</button>
        <Link v-for="link in links" :key="link.url" :href="link.url" :class="secondaryButton">{{ link.label }}</Link>
        <button v-if="urls.main_location" type="button" :class="secondaryButton" @click="editingLocation = !editingLocation">{{ t('inventory::admin.stock_index.main_warehouse') }}</button>
    </PageHeader>
    <ExtensionTools :tools="extensions.tools ?? []" />
    <section v-if="run" class="mb-4 rounded-lg border border-slate-200 bg-white p-4 text-sm" role="status" aria-live="polite" data-bulk-run>
        <p class="font-medium">{{ run.label }}</p>
        <div class="mt-2 h-2 overflow-hidden rounded bg-slate-100"><div class="h-full bg-indigo-600 transition-all" :style="{ width: `${run.progress}%` }"></div></div>
        <p class="mt-2 text-slate-600">
            <template v-if="run.finished">Xong — </template><template v-else>{{ t('inventory::admin.stock_index.running_progress', { progress: run.progress }) }} </template>
            {{ t('inventory::admin.stock_index.recorded_updated', { updated: run.counts.updated }) }}<template v-if="run.counts.unchanged">{{ t('inventory::admin.stock_index.unchanged_unchanged', { unchanged: run.counts.unchanged }) }}</template><template v-if="run.counts.conflicts">{{ t('inventory::admin.stock_index.conflicts_conflicts_stock_changed_after', { conflicts: run.counts.conflicts }) }}</template><template v-if="run.counts.skipped">{{ t('inventory::admin.stock_index.skipped_skipped', { skipped: run.counts.skipped }) }}</template><template v-if="run.failed_jobs">{{ t('inventory::admin.stock_index.failed_jobs_failed_batches_see', { failed_jobs: run.failed_jobs }) }}</template>.
        </p>
    </section>
    <form v-if="editingLocation && urls.main_location && mainLocation" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm md:grid-cols-3" data-main-location @submit.prevent="saveLocation">
        <p class="md:col-span-3">{{ t('inventory::admin.stock_index.store_s_main_warehouse_need') }} <strong>{{ t('inventory::admin.stock_index.warehouse_operations') }}</strong>.</p>
        <input v-model="locationForm.name" :class="inputClass" :placeholder="t('inventory::admin.stock_index.warehouse_name')" :aria-label="t('inventory::admin.stock_index.warehouse_name')" />
        <input v-model="locationForm.address" :class="inputClass" :placeholder="t('inventory::admin.stock_index.address')" :aria-label="t('inventory::admin.stock_index.address')" />
        <Select v-if="provinces.length" v-model="locationForm.province_code" :options="provinces" :empty-label="t('inventory::admin.stock_index.province_city')" empty-value="" :aria-label="t('inventory::admin.stock_index.province_city_2')" data-province-select />
        <input v-else v-model="locationForm.province_code" :class="inputClass" :placeholder="t('inventory::admin.stock_index.province_city_code')" :aria-label="t('inventory::admin.stock_index.province_city_code')" />
        <p v-for="(error, key) in locationForm.errors" :key="key" class="text-red-600 md:col-span-3">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="locationForm.processing">{{ t('inventory::admin.stock_index.save') }}</button></div>
    </form>

    <div v-if="warnings.no_location" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm" data-no-location>
        <p class="font-medium">{{ t('inventory::admin.stock_index.store_has_no_warehouse_every') }}</p>
        <p class="mt-1 text-slate-600">{{ t('inventory::admin.stock_index.create_main_warehouse_ships_online') }}</p>
        <button v-if="urls.default_location" type="button" :class="[primaryButton, 'mt-3']" @click="createDefault">{{ t('inventory::admin.stock_index.create_main_warehouse') }}</button>
        <p v-else class="mt-2 text-slate-600">{{ t('inventory::admin.stock_index.ask_store_owner_create_main') }}</p>
    </div>
    <div v-else-if="warnings.no_online_location" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
        {{ t('inventory::admin.stock_index.no_warehouse_has_ships_online') }}
        <Link v-if="urls.locations" :href="urls.locations" class="underline">{{ t('inventory::admin.stock_index.warehouses_stores') }}</Link><span v-else>{{ t('inventory::admin.stock_index.warehouses_stores_warehouse_operations_plugin') }}</span>.
    </div>

    <form v-if="showImport" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="submitImport">
        <p>{{ t('inventory::admin.stock_index.csv_file_columns') }} <code>sku</code>, <code>on_hand</code> {{ t('inventory::admin.stock_index.optional') }} <code>location</code> {{ t('inventory::admin.stock_index.warehouse_code_export_csv_file') }}</p>
        <FileInput v-model="importForm.file" accept=".csv,text/csv" :max-kb="2048" :label="t('inventory::admin.stock_index.choose_csv_file')" :progress="importForm.progress?.percentage ?? null" />
        <Select v-model="importForm.location_id" :options="importLocations" :empty-label="t('inventory::admin.stock_index.warehouse_rows_without_location_column')" :empty-value="null" />
        <p v-for="(error, key) in importForm.errors" :key="key" class="text-red-600">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="!importForm.file || importForm.processing">{{ t('inventory::admin.stock_index.preview') }}</button></div>
    </form>

    <form class="mb-4 flex flex-wrap items-center gap-2" @submit.prevent="find">
        <input v-model="criteria.q" :class="[inputClass, 'max-w-sm']" :placeholder="t('inventory::admin.stock_index.product_name_silk_dress_code')" :aria-label="t('inventory::admin.stock_index.search')" />
        <Select v-model="criteria.filter" :options="filterOptions" class="w-44" :aria-label="t('inventory::admin.stock_index.availability')" />
        <Select
            v-for="filter in optionFilters"
            :key="filter.code"
            :model-value="criteria.options[filter.code] ?? ''"
            :options="valueOptions(filter)"
            :empty-label="t('inventory::admin.stock_index.all_name', { name: filter.name.toLowerCase() })"
            empty-value=""
            class="w-32"
            :aria-label="filter.name"
            @update:model-value="(code) => (criteria.options[filter.code] = String(code ?? ''))"
        />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="primaryButton">{{ t('inventory::admin.stock_index.filter') }}</button>
        <button v-if="query.q || query.product || query.filter !== 'all' || hasOptionFilter" type="button" :class="secondaryButton" @click="clearFilters">{{ t('inventory::admin.stock_index.clear_filter') }}</button>
        <span v-if="query.product" class="text-sm text-slate-600">{{ t('inventory::admin.stock_index.viewing_code_product', { product: query.product }) }}</span>
    </form>

    <div v-if="canAdjust && (selected.length || allMatching)" class="sticky top-0 z-10 mb-3 flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm shadow" data-stock-bulkbar>
        <span class="font-medium" aria-live="polite">
            {{ allMatching ? t('inventory::admin.stock_index.all_selected_count_skus_matching', { selected_count: selectedCount }) : t('inventory::admin.stock_index.selected_count_skus_current_page', { selected_count: selectedCount }) }}
        </span>
        <button v-if="pageAllSelected && !allMatching && pagination.variants > pageIds.length" type="button" class="text-indigo-700 underline" @click="allMatching = true">
            {{ t('inventory::admin.stock_index.select_all_variants_skus_matching', { variants: pagination.variants }) }}
        </button>
        <Select v-model="bulk.action" :options="bulkActions" class="w-52" :aria-label="t('inventory::admin.stock_index.bulk_actions')" />
        <input v-if="bulk.action !== 'zero'" v-model.number="bulk.quantity" type="number" :class="[inputClass, 'w-24']" :placeholder="bulk.action === 'adjust' ? '+10 / -2' : t('inventory::admin.stock_index.quantity')" :aria-label="t('inventory::admin.stock_index.quantity')" />
        <span>{{ t('inventory::admin.stock_index.text') }}</span>
        <Select v-model="bulk.location_id" :options="bulkLocationOptions" class="w-56" aria-label="Kho" />
        <input v-model="bulk.reason" :class="[inputClass, 'w-56']" :placeholder="bulk.action === 'adjust' ? t('inventory::admin.stock_index.reason_required') : t('inventory::admin.stock_index.reason_optional')" maxlength="255" :aria-label="t('inventory::admin.stock_index.reason')" />
        <button type="button" :class="primaryButton" :disabled="bulkProcessing || !bulk.location_id || (bulk.action === 'adjust' && !bulk.reason.trim())" @click="applyBulk">{{ t('inventory::admin.stock_index.apply') }}</button>
        <ExtensionActions v-if="!allMatching" :actions="extensions.actions" :ids="selected" selection-description="SKU" />
        <button type="button" :class="secondaryButton" @click="clearSelection">{{ t('inventory::admin.stock_index.deselect') }}</button>
    </div>

    <div v-if="locations.length" data-stock-grid>
        <DataTable :columns="columns" :rows="rows" :loading="list.loading.value" :row-class="(row) => (row.first ? 'align-top border-t-2 border-slate-200' : 'align-top')" :row-label="(row) => row.sku" :empty-text="t('inventory::admin.stock_index.no_matching_products')">
            <template #head-select>
                <input type="checkbox" :checked="pageAllSelected" :aria-label="t('inventory::admin.stock_index.select_whole_page')" @change="toggle(pageIds, ($event.target as HTMLInputElement).checked)" />
            </template>
            <template #cell-select="{ row }">
                <input v-model="selected" type="checkbox" :value="row.id" :aria-label="t('inventory::admin.stock_index.select_sku', { sku: row.sku })" />
            </template>
            <template v-for="(location, index) in locations" :key="location.id" #[`head-location-${index}`]>
                {{ location.code }}
                <span v-if="location.external" class="ml-1 text-amber-600">{{ t('inventory::admin.stock_index.synced') }}</span>
                <span v-else-if="!location.online" class="ml-1 text-slate-400">{{ t('inventory::admin.stock_index.not_sold_online') }}</span>
            </template>
            <template #cell-product="{ row }">
                <div v-if="row.first" class="mb-1 flex items-center gap-2">
                    <input
                        v-if="canAdjust" type="checkbox" :checked="productSelected(row.product)" :aria-label="t('inventory::admin.stock_index.select_all_product_name', { product_name: row.product.product_name })"
                        @change="toggle(productIds(row.product), ($event.target as HTMLInputElement).checked)"
                    />
                    <span class="font-medium">{{ row.product.product_name }}</span>
                    <span class="text-xs text-slate-500">{{ row.product.product_code }} · {{ row.product.variants.length }} SKU<template v-if="row.product.tracked"> {{ t('inventory::admin.stock_index.sellable_sellable', { sellable: row.product.sellable }) }}</template></span>
                    <span v-if="!row.product.tracked" class="rounded-full bg-sky-50 px-2 py-0.5 text-xs text-sky-700" :title="t('inventory::admin.stock_index.product_always_sellable_stock_backfills')" data-untracked>{{ t('inventory::admin.stock_index.stock_not_tracked') }}</span>
                </div>
                <div class="font-mono text-xs text-slate-600">{{ row.sku }}<template v-if="row.options_label"> · {{ row.options_label }}</template><span v-if="row.status !== 'active'"> {{ t('inventory::admin.stock_index.discontinued') }}</span></div>
                <div v-for="column in extensions.columns" :key="column.key" class="text-xs text-slate-500" :data-extension-column="column.key">
                    <template v-if="extensions.values[row.id]?.[column.key] !== null && extensions.values[row.id]?.[column.key] !== undefined">{{ column.label }}: {{ extensions.values[row.id][column.key] }}</template>
                </div>
            </template>
            <template #cell-sellable="{ row }">
                <span v-if="!row.tracked" class="text-sky-700" :title="t('inventory::admin.stock_index.stock_not_tracked_always_sellable')">{{ t('inventory::admin.stock_index.unlimited') }}</span>
                <span v-else class="font-semibold" :class="row.sellable === 0 ? 'text-red-600' : row.sellable <= threshold ? 'text-amber-600' : 'text-emerald-700'">{{ row.sellable }}</span>
            </template>
            <template v-for="(location, index) in locations" :key="location.id" #[`cell-location-${index}`]="{ row }">
                <div class="flex items-center gap-1">
                    <input
                        v-if="editable(location)"
                        type="number" min="0" :value="edits[cellKey(location, row)] ?? row.levels[index].on_hand" :data-cell="`${row.index}-${index}`"
                        :class="[inputClass, 'w-20 py-1', edits[cellKey(location, row)] !== undefined ? 'border-indigo-500 bg-indigo-50' : '']"
                        :aria-label="t('inventory::admin.stock_index.stock_sku_code', { sku: row.sku, code: location.code })"
                        @input="onInput(location, row, index, ($event.target as HTMLInputElement).value)"
                        @keydown.enter="moveFocus($event, row.index, index)"
                        @focus="($event.target as HTMLInputElement).select()"
                    />
                    <span v-else class="w-20 font-semibold">{{ row.levels[index].on_hand }}</span>
                    <button v-if="editable(location)" type="button" class="px-1 text-slate-400 hover:text-slate-700" :title="t('inventory::admin.stock_index.add_subtract_safety_stock')" @click="openAdvanced(row, location)">⋯</button>
                </div>
                <div v-if="row.levels[index].reserved || row.levels[index].safety_stock" class="mt-1 text-xs text-slate-500">
                    {{ t('inventory::admin.stock_index.reserved_reserved_safety_safety_stock', { reserved: row.levels[index].reserved, safety_stock: row.levels[index].safety_stock }) }}
                </div>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`${urls.movements}?variant=${row.id}`" class="text-xs text-indigo-600 hover:underline">{{ t('inventory::admin.stock_index.history') }}</Link>
            </template>
        </DataTable>
    </div>
    <p class="mt-2 text-xs text-slate-500">{{ t('inventory::admin.stock_index.total_products_variants_skus', { total: pagination.total, variants: pagination.variants }) }}</p>
    <Pagination :current="pagination.page" :last="lastPage" :from="firstResult" :to="lastResult" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="(target) => visit(params({ page: target }))" @per-page-change="changePageSize" />

    <div v-if="dirty" class="sticky bottom-0 mt-4 flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 bg-white p-3 shadow-lg" data-stock-savebar>
        <span class="text-sm font-medium">{{ t('inventory::admin.stock_index.dirty_cells_edited', { dirty }) }}</span>
        <input v-model="reason" :class="[inputClass, 'max-w-xs']" :placeholder="t('inventory::admin.stock_index.reason_optional_november_stock_count')" maxlength="255" />
        <button type="button" :class="primaryButton" :disabled="saving" @click="save">{{ t('inventory::admin.stock_index.save') }}</button>
        <button type="button" :class="secondaryButton" @click="discard">{{ t('inventory::admin.stock_index.cancel') }}</button>
    </div>

    <form v-if="advanced" class="mt-4 grid max-w-xl gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submitAdvanced">
        <p class="font-medium">{{ t('inventory::admin.stock_index.sku_location', { sku: advanced.sku, location: advanced.location }) }}</p>
        <Select v-model="form.action" :options="advancedActions" :aria-label="t('inventory::admin.stock_index.actions')" />
        <input v-model.number="form.quantity" :class="inputClass" type="number" :placeholder="form.action === 'adjust' ? t('inventory::admin.stock_index.example_10_2') : t('inventory::admin.stock_index.quantity_held_back_not_sold')" />
        <input v-if="form.action === 'adjust'" v-model="form.reason" :class="inputClass" :placeholder="t('inventory::admin.stock_index.reason_required')" />
        <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('inventory::admin.stock_index.save') }}</button>
            <button type="button" :class="secondaryButton" @click="advanced = null">{{ t('inventory::admin.stock_index.cancel') }}</button>
        </div>
    </form>
</template>
