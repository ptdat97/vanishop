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
import { computed, reactive, ref } from 'vue';
import type { InventoryLabels } from '../../labels';

type Level = { on_hand: number; reserved: number; safety_stock: number; available: number };
type Row = { id: number; sku: string; style_code: string; style_name: string; color_code: string; size_code: string; status: string; sellable: number; levels: Level[] };
type StockLocation = { id: number; code: string; name: string; external: boolean; online: boolean };

/**
 * Tồn kho (đợt 1 — dễ cấu hình): mọi sản phẩm × kho, tìm theo tên/mã/SKU, lọc hết hàng / sắp hết; gõ số tồn thẳng vào ô
 * rồi lưu cả trang; nhập/xuất CSV. Cộng/trừ và tồn an toàn ở nút "⋯" của từng ô.
 */
const props = defineProps<{
    labels: InventoryLabels;
    urls: { base: string; batch: string; change: string; export: string; import: string; movements: string; transfers: string; locations: string | null; default_location: string | null };
    query: { q: string; style: string; filter: 'all' | 'out' | 'low' };
    pagination: { page: number; per_page: number; total: number };
    threshold: number;
    warnings: { no_location: boolean; no_online_location: boolean };
    locations: StockLocation[];
    variants: Row[];
    canAdjust: boolean;
}>();

const page = usePage<{ errors: Record<string, string> }>();
const search = ref(props.query.q);
const filter = ref(props.query.filter);
const filterOptions = computed(() => [
    { value: 'all', label: 'Tất cả' },
    { value: 'out', label: 'Hết hàng (online)' },
    { value: 'low', label: `Sắp hết (≤ ${props.threshold})` },
]);
const columns = computed(() => [
    { key: 'product', label: 'Sản phẩm / SKU' },
    { key: 'sellable', label: 'Bán được online', align: 'right' as const },
    ...props.locations.map((location, index) => ({ key: `location-${index}`, label: location.code })),
    { key: 'actions', label: '', align: 'right' as const },
]);
const lastPage = computed(() => Math.max(1, Math.ceil(props.pagination.total / props.pagination.per_page)));

/** Ô đang sửa: "kho:variant" → số tồn mới. */
const edits = reactive<Record<string, number>>({});
const reason = ref('');
const saving = ref(false);
const dirty = computed(() => Object.keys(edits).length);
const cellKey = (location: StockLocation, row: Row) => `${location.id}:${row.id}`;
const editable = (location: StockLocation) => props.canAdjust && !location.external;

function onInput(location: StockLocation, row: Row, index: number, raw: string): void {
    const value = Number.parseInt(raw, 10);
    const key = cellKey(location, row);
    if (Number.isNaN(value) || value < 0 || value === row.levels[index].on_hand) {
        delete edits[key];
    } else {
        edits[key] = value;
    }
}

function discard(): void {
    for (const key of Object.keys(edits)) delete edits[key];
    reason.value = '';
}

async function visit(params: Record<string, string | number | undefined>): Promise<void> {
    if (dirty.value && (await confirmAction({ title: `Bỏ ${dirty.value} ô chưa lưu?`, confirmLabel: 'Bỏ thay đổi', tone: 'danger' })) === false) return;
    discard();
    router.get(props.urls.base, params, { preserveState: true });
}

function find(): void {
    void visit({ q: search.value.trim() || undefined, filter: filter.value === 'all' ? undefined : filter.value });
}

function goTo(target: number): void {
    void visit({ q: props.query.q || undefined, style: props.query.style || undefined, filter: props.query.filter === 'all' ? undefined : props.query.filter, page: target > 1 ? target : undefined });
}

function save(): void {
    const changes = props.variants.flatMap((row) =>
        props.locations.flatMap((location, index) => {
            const value = edits[cellKey(location, row)];
            return value === undefined ? [] : [{ location_id: location.id, variant_id: row.id, on_hand: value, expected: row.levels[index].on_hand }];
        }),
    );
    saving.value = true;
    router.post(props.urls.batch, { changes, reason: reason.value || null }, { preserveScroll: true, onSuccess: discard, onFinish: () => (saving.value = false) });
}

/** Thao tác nâng cao của một ô: cộng/trừ (có lý do) hoặc tồn an toàn. */
const advanced = ref<null | { sku: string; location: string }>(null);
const form = useForm({ location_id: 0, variant_id: 0, action: 'adjust', quantity: 0, reason: '' });
const advancedActions = computed(() => labelOptions(props.labels.stock_action).filter((option) => option.value !== 'count'));

function openAdvanced(row: Row, location: StockLocation): void {
    form.reset();
    form.clearErrors();
    form.location_id = location.id;
    form.variant_id = row.id;
    advanced.value = { sku: row.sku, location: location.code };
}

function submitAdvanced(): void {
    form.post(props.urls.change, { preserveScroll: true, onSuccess: () => (advanced.value = null) });
}

/** Nhập CSV: chọn file (+ kho mặc định cho dòng không có cột location) → trang xem trước. */
const importForm = useForm<{ file: File | null; location_id: number | null }>({ file: null, location_id: null });
const importLocations = computed(() => props.locations.filter((location) => !location.external).map((location) => ({ value: location.id, label: `${location.code} — ${location.name}` })));
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
    <PageHeader title="Tồn kho" subtitle="Gõ số tồn thực tế vào ô rồi bấm Lưu. Có thể bán = tồn − đang giữ cho đơn − tồn an toàn.">
        <a :href="urls.export" :class="secondaryButton">Xuất CSV</a>
        <button v-if="canAdjust && locations.length" type="button" :class="secondaryButton" @click="showImport = !showImport">Nhập CSV</button>
        <Link :href="urls.transfers" :class="secondaryButton">Chuyển kho</Link>
        <Link v-if="urls.locations" :href="urls.locations" :class="secondaryButton">Kho & cửa hàng</Link>
    </PageHeader>
    <FlashMessage />
    <p v-if="page.props.errors?.changes" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ page.props.errors.changes }}</p>

    <div v-if="warnings.no_location" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm" data-no-location>
        <p class="font-medium">Cửa hàng chưa có kho nào — mọi sản phẩm đang hiện "hết hàng".</p>
        <p class="mt-1 text-slate-600">Tạo "Kho chính" (giao đơn online, quản lý tồn tại VaniShop) rồi nhập số tồn.</p>
        <button v-if="urls.default_location" type="button" :class="[primaryButton, 'mt-3']" @click="createDefault">Tạo Kho chính</button>
        <p v-else class="mt-2 text-slate-600">Nhờ chủ cửa hàng tạo kho ở Kho & cửa hàng.</p>
    </div>
    <div v-else-if="warnings.no_online_location" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
        Chưa có kho nào đang bật "Giao đơn online" — khách không mua được. Sửa ở
        <Link v-if="urls.locations" :href="urls.locations" class="underline">Kho & cửa hàng</Link><span v-else>Kho & cửa hàng</span>.
    </div>

    <form v-if="showImport" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="submitImport">
        <p>File CSV có cột <code>sku</code>, <code>on_hand</code> và (tuỳ chọn) <code>location</code> — mã kho. File "Xuất CSV" sửa xong nhập lại được. Bước sau là xem trước, chưa ghi gì.</p>
        <input type="file" accept=".csv,text/csv" @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
        <Select v-model="importForm.location_id" :options="importLocations" empty-label="Kho cho dòng không có cột location: — kho giao online đầu tiên —" :empty-value="null" />
        <p v-for="(error, key) in importForm.errors" :key="key" class="text-red-600">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="!importForm.file || importForm.processing">Xem trước</button></div>
    </form>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="find">
        <input v-model="search" :class="[inputClass, 'max-w-sm']" placeholder="Tên sản phẩm, mã hoặc SKU" />
        <Select v-model="filter" :options="filterOptions" class="w-48" aria-label="Lọc" />
        <button type="submit" :class="primaryButton">Lọc</button>
        <span v-if="query.style" class="self-center text-sm text-slate-600">
            Mã {{ query.style }} · <button type="button" class="text-indigo-600 hover:underline" @click="visit({})">bỏ lọc</button>
        </span>
    </form>

    <div v-if="locations.length" data-stock-grid>
        <DataTable :columns="columns" :rows="variants" :row-class="() => 'align-top'" :row-label="(row) => row.sku" empty-text="Không có sản phẩm phù hợp.">
            <template v-for="(location, index) in locations" :key="location.id" #[`head-location-${index}`]>
                {{ location.code }}
                <span v-if="location.external" class="ml-1 text-amber-600">(đồng bộ)</span>
                <span v-else-if="!location.online" class="ml-1 text-slate-400">(không bán online)</span>
            </template>
            <template #cell-product="{ row }">
                <div class="font-medium">{{ row.style_name }}</div>
                <div class="font-mono text-xs text-slate-500">{{ row.sku }} · {{ row.color_code }} / {{ row.size_code }}<span v-if="row.status !== 'active'"> · ngừng bán</span></div>
            </template>
            <template #cell-sellable="{ row }">
                <span class="font-semibold" :class="row.sellable === 0 ? 'text-red-600' : row.sellable <= threshold ? 'text-amber-600' : 'text-emerald-700'">{{ row.sellable }}</span>
            </template>
            <template v-for="(location, index) in locations" :key="location.id" #[`cell-location-${index}`]="{ row }">
                <div class="flex items-center gap-1">
                    <input
                        v-if="editable(location)"
                        type="number" min="0" :value="edits[cellKey(location, row)] ?? row.levels[index].on_hand"
                        :class="[inputClass, 'w-20 py-1', edits[cellKey(location, row)] !== undefined ? 'border-indigo-500 bg-indigo-50' : '']"
                        :aria-label="`Tồn ${row.sku} tại ${location.code}`"
                        @input="onInput(location, row, index, ($event.target as HTMLInputElement).value)"
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
    <p class="mt-2 text-xs text-slate-500">{{ pagination.total }} SKU</p>
    <Pagination :current="pagination.page" :last="lastPage" @change="goTo" />

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
