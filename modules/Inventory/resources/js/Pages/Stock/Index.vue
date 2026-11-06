<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FlashMessage from '@admin/Components/FlashMessage.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import type { InventoryLabels } from '../../labels';

type Level = { on_hand: number; reserved: number; safety_stock: number; available: number };
type Variant = { id: number; sku: string; style_code: string; style_name: string; color_code: string; size_code: string; status: string; sellable: number; levels: Level[] };
type Product = { style_code: string; style_name: string; sellable: number; variants: Variant[] };
type Row = Variant & { first: boolean; product: Product; index: number };
type StockLocation = { id: number; code: string; name: string; external: boolean; online: boolean };
type Criteria = { q: string; style: string; filter: 'all' | 'out' | 'low'; color: string; size: string };

/**
 * Tồn kho: sản phẩm × kho. Tìm theo tên sản phẩm, mã hoặc SKU; lọc màu, size, hết hàng / sắp hết. Gõ số vào ô rồi Lưu,
 * hoặc chọn nhiều SKU (cả sản phẩm, cả trang, mọi kết quả lọc) để sửa hàng loạt tại một kho. Enter = xuống ô dưới.
 */
const props = defineProps<{
    labels: InventoryLabels;
    urls: { base: string; batch: string; bulk: string; change: string; export: string; import: string; movements: string; locations: string | null; default_location: string | null; main_location: string | null };
    links: Array<{ label: string; url: string }>;
    mainLocation: { id: number; name: string; address: string | null; province_code: string | null; lock_version: number } | null;
    query: Criteria;
    options: { colors: string[]; sizes: string[] };
    pagination: { page: number; per_page: number; total: number; variants: number };
    threshold: number;
    warnings: { no_location: boolean; no_online_location: boolean };
    locations: StockLocation[];
    products: Product[];
    canAdjust: boolean;
}>();

const page = usePage<{ errors: Record<string, string> }>();
const criteria = reactive<Criteria>({ ...props.query });
const filterOptions = computed(() => [
    { value: 'all', label: 'Tất cả' },
    { value: 'out', label: 'Hết hàng (online)' },
    { value: 'low', label: `Sắp hết (≤ ${props.threshold})` },
]);
const colorOptions = computed(() => props.options.colors.map((code) => ({ value: code, label: code })));
const sizeOptions = computed(() => props.options.sizes.map((code) => ({ value: code, label: code })));
const rows = computed<Row[]>(() => {
    let index = 0;
    return props.products.flatMap((product) => product.variants.map((variant, position) => ({ ...variant, first: position === 0, product, index: index++ })));
});
const columns = computed(() => [
    ...(props.canAdjust ? [{ key: 'select', label: '' }] : []),
    { key: 'product', label: 'Sản phẩm / SKU' },
    { key: 'sellable', label: 'Bán được online', align: 'right' as const },
    ...props.locations.map((location, index) => ({ key: `location-${index}`, label: location.code })),
    { key: 'actions', label: '', align: 'right' as const },
]);
const lastPage = computed(() => Math.max(1, Math.ceil(props.pagination.total / props.pagination.per_page)));
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
function params(overrides: Partial<Criteria> & { page?: number } = {}): Record<string, string | number | undefined> {
    const merged = { ...criteria, ...overrides };
    return {
        q: merged.q.trim() || undefined, style: merged.style || undefined, filter: merged.filter === 'all' ? undefined : merged.filter,
        color: merged.color || undefined, size: merged.size || undefined, page: overrides.page && overrides.page > 1 ? overrides.page : undefined,
    };
}

async function visit(query: Record<string, string | number | undefined>): Promise<void> {
    if (dirty.value && (await confirmAction({ title: `Bỏ ${dirty.value} ô chưa lưu?`, confirmLabel: 'Bỏ thay đổi', tone: 'danger' })) === false) return;
    discard();
    clearSelection();
    router.get(props.urls.base, query, { preserveState: true });
}

function find(): void {
    void visit(params({ style: '' }));
}

function clearFilters(): void {
    Object.assign(criteria, { q: '', style: '', filter: 'all', color: '', size: '' });
    void visit({});
}

/* ---------- Chọn nhiều + sửa hàng loạt ---------- */
const selected = ref<number[]>([]);
/** Chọn mọi SKU khớp bộ lọc (kể cả trang khác) — server tự lấy danh sách theo điều kiện lọc. */
const allMatching = ref(false);
const pageIds = computed(() => rows.value.map((row) => row.id));
const pageAllSelected = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selected.value.includes(id)));
const selectedCount = computed(() => (allMatching.value ? props.pagination.variants : selected.value.length));
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
        set: `đặt tồn = ${quantity}`,
        adjust: `${quantity >= 0 ? 'cộng' : 'trừ'} ${Math.abs(quantity)}`,
        safety: `đặt tồn an toàn = ${quantity}`,
    };
    const confirmed = await confirmAction({
        title: `${what[action]} cho ${selectedCount.value} SKU tại ${location.code}?`,
        message: action === 'adjust' ? 'SKU nào không đủ tồn để trừ sẽ được bỏ qua.' : 'Mỗi SKU được ghi một dòng trong lịch sử biến động.',
        confirmLabel: 'Áp dụng',
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
const locationForm = useForm({
    name: props.mainLocation?.name ?? '', address: props.mainLocation?.address ?? '', province_code: props.mainLocation?.province_code ?? '', lock_version: props.mainLocation?.lock_version ?? 0,
});

function saveLocation(): void {
    if (props.urls.main_location) locationForm.put(props.urls.main_location, { preserveScroll: true, onSuccess: () => (editingLocation.value = false) });
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
</script>

<template>
    <Head title="Tồn kho" />
    <PageHeader title="Tồn kho" subtitle="Gõ số tồn vào ô (Enter: xuống dòng) rồi Lưu, hoặc chọn nhiều SKU để sửa hàng loạt. Bán được = tồn − đang giữ − tồn an toàn.">
        <a :href="urls.export" :class="secondaryButton">Xuất CSV</a>
        <button v-if="canAdjust && locations.length" type="button" :class="secondaryButton" @click="showImport = !showImport">Nhập CSV</button>
        <Link v-for="link in links" :key="link.url" :href="link.url" :class="secondaryButton">{{ link.label }}</Link>
        <button v-if="urls.main_location" type="button" :class="secondaryButton" @click="editingLocation = !editingLocation">Kho chính</button>
    </PageHeader>
    <FlashMessage />
    <form v-if="editingLocation && urls.main_location && mainLocation" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm md:grid-cols-3" data-main-location @submit.prevent="saveLocation">
        <p class="md:col-span-3">Kho chính của cửa hàng. Cần nhiều kho, cửa hàng nhận tại quầy hay chuyển kho: bật plugin <strong>Vận hành kho</strong>.</p>
        <input v-model="locationForm.name" :class="inputClass" placeholder="Tên kho" aria-label="Tên kho" />
        <input v-model="locationForm.address" :class="inputClass" placeholder="Địa chỉ" aria-label="Địa chỉ" />
        <input v-model="locationForm.province_code" :class="inputClass" placeholder="Mã tỉnh/thành" aria-label="Mã tỉnh/thành" />
        <p v-for="(error, key) in locationForm.errors" :key="key" class="text-red-600 md:col-span-3">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="locationForm.processing">Lưu</button></div>
    </form>
    <p v-if="page.props.errors?.changes" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ page.props.errors.changes }}</p>

    <div v-if="warnings.no_location" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm" data-no-location>
        <p class="font-medium">Cửa hàng chưa có kho nào — mọi sản phẩm đang hiện "hết hàng".</p>
        <p class="mt-1 text-slate-600">Tạo "Kho chính" (giao đơn online, quản lý tồn tại VaniShop) rồi nhập số tồn.</p>
        <button v-if="urls.default_location" type="button" :class="[primaryButton, 'mt-3']" @click="createDefault">Tạo Kho chính</button>
        <p v-else class="mt-2 text-slate-600">Nhờ chủ cửa hàng tạo Kho chính.</p>
    </div>
    <div v-else-if="warnings.no_online_location" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
        Chưa có kho nào đang bật "Giao đơn online" — khách không mua được. Sửa ở
        <Link v-if="urls.locations" :href="urls.locations" class="underline">Kho & cửa hàng</Link><span v-else>Kho & cửa hàng (plugin Vận hành kho)</span>.
    </div>

    <form v-if="showImport" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="submitImport">
        <p>File CSV có cột <code>sku</code>, <code>on_hand</code> và (tuỳ chọn) <code>location</code> — mã kho. File "Xuất CSV" sửa xong nhập lại được. Bước sau là xem trước, chưa ghi gì.</p>
        <input type="file" accept=".csv,text/csv" @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
        <Select v-model="importForm.location_id" :options="importLocations" empty-label="Kho cho dòng không có cột location: — kho giao online đầu tiên —" :empty-value="null" />
        <p v-for="(error, key) in importForm.errors" :key="key" class="text-red-600">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="!importForm.file || importForm.processing">Xem trước</button></div>
    </form>

    <form class="mb-4 flex flex-wrap items-center gap-2" @submit.prevent="find">
        <input v-model="criteria.q" :class="[inputClass, 'max-w-sm']" placeholder="Tên sản phẩm (vd. đầm lụa), mã hoặc SKU" aria-label="Tìm" />
        <Select v-model="criteria.filter" :options="filterOptions" class="w-44" aria-label="Tình trạng" />
        <Select v-model="criteria.color" :options="colorOptions" empty-label="Mọi màu" empty-value="" class="w-32" aria-label="Màu" />
        <Select v-model="criteria.size" :options="sizeOptions" empty-label="Mọi size" empty-value="" class="w-28" aria-label="Size" />
        <button type="submit" :class="primaryButton">Lọc</button>
        <button v-if="query.q || query.style || query.filter !== 'all' || query.color || query.size" type="button" :class="secondaryButton" @click="clearFilters">Bỏ lọc</button>
        <span v-if="query.style" class="text-sm text-slate-600">Đang xem mã {{ query.style }}</span>
    </form>

    <div v-if="canAdjust && (selected.length || allMatching)" class="sticky top-0 z-10 mb-3 flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm shadow" data-stock-bulkbar>
        <span class="font-medium">Đã chọn {{ selectedCount }} SKU</span>
        <button v-if="pageAllSelected && !allMatching && pagination.variants > pageIds.length" type="button" class="text-indigo-700 underline" @click="allMatching = true">
            Chọn cả {{ pagination.variants }} SKU khớp bộ lọc
        </button>
        <Select v-model="bulk.action" :options="bulkActions" class="w-52" aria-label="Thao tác hàng loạt" />
        <input v-if="bulk.action !== 'zero'" v-model.number="bulk.quantity" type="number" :class="[inputClass, 'w-24']" :placeholder="bulk.action === 'adjust' ? '+10 / -2' : 'Số lượng'" aria-label="Số lượng" />
        <span>tại</span>
        <Select v-model="bulk.location_id" :options="bulkLocationOptions" class="w-56" aria-label="Kho" />
        <input v-model="bulk.reason" :class="[inputClass, 'w-56']" :placeholder="bulk.action === 'adjust' ? 'Lý do (bắt buộc)' : 'Lý do (tuỳ chọn)'" maxlength="255" aria-label="Lý do" />
        <button type="button" :class="primaryButton" :disabled="bulkProcessing || !bulk.location_id || (bulk.action === 'adjust' && !bulk.reason.trim())" @click="applyBulk">Áp dụng</button>
        <button type="button" :class="secondaryButton" @click="clearSelection">Bỏ chọn</button>
    </div>

    <div v-if="locations.length" data-stock-grid>
        <DataTable :columns="columns" :rows="rows" :row-class="(row) => (row.first ? 'align-top border-t-2 border-slate-200' : 'align-top')" :row-label="(row) => row.sku" empty-text="Không có sản phẩm phù hợp.">
            <template #head-select>
                <input type="checkbox" :checked="pageAllSelected" aria-label="Chọn cả trang" @change="toggle(pageIds, ($event.target as HTMLInputElement).checked)" />
            </template>
            <template #cell-select="{ row }">
                <input v-model="selected" type="checkbox" :value="row.id" :aria-label="`Chọn ${row.sku}`" />
            </template>
            <template v-for="(location, index) in locations" :key="location.id" #[`head-location-${index}`]>
                {{ location.code }}
                <span v-if="location.external" class="ml-1 text-amber-600">(đồng bộ)</span>
                <span v-else-if="!location.online" class="ml-1 text-slate-400">(không bán online)</span>
            </template>
            <template #cell-product="{ row }">
                <div v-if="row.first" class="mb-1 flex items-center gap-2">
                    <input
                        v-if="canAdjust" type="checkbox" :checked="productSelected(row.product)" :aria-label="`Chọn cả ${row.product.style_name}`"
                        @change="toggle(productIds(row.product), ($event.target as HTMLInputElement).checked)"
                    />
                    <span class="font-medium">{{ row.product.style_name }}</span>
                    <span class="text-xs text-slate-500">{{ row.product.style_code }} · {{ row.product.variants.length }} SKU · bán được {{ row.product.sellable }}</span>
                </div>
                <div class="font-mono text-xs text-slate-600">{{ row.sku }} · {{ row.color_code }} / {{ row.size_code }}<span v-if="row.status !== 'active'"> · ngừng bán</span></div>
            </template>
            <template #cell-sellable="{ row }">
                <span class="font-semibold" :class="row.sellable === 0 ? 'text-red-600' : row.sellable <= threshold ? 'text-amber-600' : 'text-emerald-700'">{{ row.sellable }}</span>
            </template>
            <template v-for="(location, index) in locations" :key="location.id" #[`cell-location-${index}`]="{ row }">
                <div class="flex items-center gap-1">
                    <input
                        v-if="editable(location)"
                        type="number" min="0" :value="edits[cellKey(location, row)] ?? row.levels[index].on_hand" :data-cell="`${row.index}-${index}`"
                        :class="[inputClass, 'w-20 py-1', edits[cellKey(location, row)] !== undefined ? 'border-indigo-500 bg-indigo-50' : '']"
                        :aria-label="`Tồn ${row.sku} tại ${location.code}`"
                        @input="onInput(location, row, index, ($event.target as HTMLInputElement).value)"
                        @keydown.enter="moveFocus($event, row.index, index)"
                        @focus="($event.target as HTMLInputElement).select()"
                    />
                    <span v-else class="w-20 font-semibold">{{ row.levels[index].on_hand }}</span>
                    <button v-if="editable(location)" type="button" class="px-1 text-slate-400 hover:text-slate-700" title="Cộng/trừ, tồn an toàn" @click="openAdvanced(row, location)">⋯</button>
                </div>
                <div v-if="row.levels[index].reserved || row.levels[index].safety_stock" class="mt-1 text-xs text-slate-500">
                    giữ {{ row.levels[index].reserved }} · an toàn {{ row.levels[index].safety_stock }}
                </div>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`${urls.movements}?variant=${row.id}`" class="text-xs text-indigo-600 hover:underline">Lịch sử</Link>
            </template>
        </DataTable>
    </div>
    <p class="mt-2 text-xs text-slate-500">{{ pagination.total }} sản phẩm · {{ pagination.variants }} SKU</p>
    <Pagination :current="pagination.page" :last="lastPage" @change="(target) => visit(params({ page: target }))" />

    <div v-if="dirty" class="sticky bottom-0 mt-4 flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 bg-white p-3 shadow-lg" data-stock-savebar>
        <span class="text-sm font-medium">{{ dirty }} ô đã sửa</span>
        <input v-model="reason" :class="[inputClass, 'max-w-xs']" placeholder="Lý do (tuỳ chọn), vd. Kiểm kê tháng 11" maxlength="255" />
        <button type="button" :class="primaryButton" :disabled="saving" @click="save">Lưu</button>
        <button type="button" :class="secondaryButton" @click="discard">Huỷ</button>
    </div>

    <form v-if="advanced" class="mt-4 grid max-w-xl gap-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submitAdvanced">
        <p class="font-medium">{{ advanced.sku }} tại {{ advanced.location }}</p>
        <Select v-model="form.action" :options="advancedActions" aria-label="Thao tác" />
        <input v-model.number="form.quantity" :class="inputClass" type="number" :placeholder="form.action === 'adjust' ? 'Ví dụ: 10 hoặc -2' : 'Số lượng chừa lại, không bán online'" />
        <input v-if="form.action === 'adjust'" v-model="form.reason" :class="inputClass" placeholder="Lý do (bắt buộc)" />
        <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
            <button type="button" :class="secondaryButton" @click="advanced = null">Huỷ</button>
        </div>
    </form>
</template>
