<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import FileInput from '@admin/Components/FileInput.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import { useListQuery, type ListSortState } from '@admin/list';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { labelOptions, recordOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    filters: { q: string; status: string | null; category: number | null; brand: string | null; collection: number | null; missing: string | null };
    filterOptions: Record<'categories' | 'brands' | 'collections', Array<{ id: number; label: string }>> & { missing: Record<string, string> };
    sort: ListSortState | null;
    products: {
        data: Array<{ id: number; style_code: string; name: string | null; brand: string | null; status: string; skus: number; image_url: string | null; views: number; missing: string[] }>;
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
const brandOptions = computed(() => [{ value: 'none', label: 'Chưa có thương hiệu' }, ...props.filterOptions.brands.map((item) => ({ value: String(item.id), label: item.label }))]);
const perPageOptions = [30, 50, 100];
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
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
    { key: 'name', label: 'Sản phẩm', class: 'font-medium' },
    { key: 'style_code', label: 'Mã', class: 'font-mono text-xs', sortable: true },
    { key: 'brand', label: 'Thương hiệu' },
    { key: 'skus', label: 'SKU' },
    { key: 'views', label: 'Lượt xem 30 ngày', align: 'right' as const, class: 'tabular-nums' },
    { key: 'status', label: 'Trạng thái', sortable: true },
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
    router.post(edit.url, { changes, reason: 'Sửa nhanh ở danh sách sản phẩm' }, {
        preserveScroll: true,
        onError: (errors) => {
            stockError.value = Object.values(errors)[0] ?? 'Không thể cập nhật tồn.';
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
            priceError.value = errors.amount ?? 'Không thể cập nhật giá.';
        },
        onSuccess: () => {
            editingPriceId.value = null;
        },
        onFinish: () => {
            savingPrice.value = false;
        },
    });
}
</script>

<template>
    <Head title="Sản phẩm" />
    <CatalogTabs :nav="nav" active="products" />
    <PageHeader title="Sản phẩm" :subtitle="`${products.total} sản phẩm`">
        <a :href="fileUrls.export" :class="secondaryButton" data-export>Xuất file</a>
        <button v-if="canManage" type="button" :class="secondaryButton" data-import-toggle @click="showImport = !showImport">Nhập file</button>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm sản phẩm</Link>
    </PageHeader>

    <form v-if="showImport" class="mb-4 grid max-w-2xl gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" data-import @submit.prevent="submitImport">
        <p>File Excel (.xlsx) hoặc CSV, mỗi SKU một dòng; các dòng cùng <strong>Mã sản phẩm</strong> là một sản phẩm. Dùng file "Xuất file" làm mẫu. Ô trống = giữ nguyên; sản phẩm mới là bản nháp nếu không ghi trạng thái. Bước sau là xem trước, chưa ghi gì.</p>
        <FileInput v-model="importForm.file" accept=".xlsx,.csv" :max-kb="5120" label="Chọn file" :progress="importForm.progress?.percentage ?? null" />
        <p v-for="(error, key) in importForm.errors" :key="key" class="text-red-600">{{ error }}</p>
        <div><button type="submit" :class="primaryButton" :disabled="!importForm.file || importForm.processing">Xem trước</button></div>
    </form>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="inputClass" class="max-w-xs" type="search" placeholder="Tìm theo tên hoặc mã (không cần dấu)" aria-label="Tìm sản phẩm" />
        <div class="w-44"><Select v-model="status" :options="labelOptions(labels.product_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" /></div>
        <div v-if="filterOptions.categories.length" class="w-52"><Select v-model="category" :options="recordOptions(filterOptions.categories)" empty-label="Mọi danh mục" aria-label="Danh mục (gồm danh mục con)" /></div>
        <div class="w-48"><Select v-model="brand" :options="brandOptions" empty-label="Mọi thương hiệu" aria-label="Thương hiệu" /></div>
        <div class="w-48"><Select v-model="missing" :options="labelOptions(filterOptions.missing)" empty-label="Đủ/thiếu dữ liệu" aria-label="Thiếu dữ liệu" /></div>
        <div v-if="filterOptions.collections.length" class="w-48"><Select v-model="collection" :options="recordOptions(filterOptions.collections)" empty-label="Mọi bộ sưu tập" aria-label="Bộ sưu tập" /></div>
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>

    <div v-if="extensions.actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500">Đã chọn {{ selected.length }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="selected" />
    </div>
    <DataTable v-model:selected="selected" :columns="columns" :rows="products.data" :extensions="extensions" :sort="sort" :loading="list.loading.value" @sort="(next) => search(1, products.per_page, next)" :row-label="(product) => product.style_code" :row-click="canManage ? editProduct : undefined" empty-text="Không có sản phẩm nào.">
        <template #cell-image="{ row }">
            <img v-if="row.image_url" :src="row.image_url" alt="" class="h-12 w-10 rounded object-cover" />
            <div v-else class="h-12 w-10 rounded bg-slate-100" />
        </template>
        <template #cell-name="{ row }">
            <span>{{ row.name ?? '—' }}</span>
            <span v-if="row.missing.length" class="mt-1 flex flex-wrap gap-1" data-missing>
                <span v-for="item in row.missing" :key="item" class="rounded bg-amber-50 px-1.5 py-0.5 text-xs font-normal text-amber-800">{{ item }}</span>
            </span>
        </template>
        <template #cell-brand="{ row }">{{ row.brand ?? '—' }}</template>
        <template #cell-price="{ row, value, editValue, url }">
            <div v-if="canManagePrices && url" class="min-w-52">
                <form v-if="editingPriceId === row.id" class="flex flex-wrap items-center gap-2" @submit.prevent="savePrice(url)">
                    <label class="sr-only" :for="`base-price-${row.id}`">Giá niêm yết cho mọi size</label>
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
                        placeholder="Giá mới"
                    />
                    <button type="submit" :class="primaryButton" :disabled="savingPrice">{{ savingPrice ? 'Đang lưu…' : 'Lưu' }}</button>
                    <button type="button" :class="secondaryButton" :disabled="savingPrice" @click="closePriceEditor">Hủy</button>
                    <span v-if="priceError" class="basis-full text-xs text-rose-700" role="alert">{{ priceError }}</span>
                    <span class="basis-full text-xs text-slate-500">Hiện tại: {{ value }}. Áp dụng giá niêm yết mới cho mọi size.</span>
                </form>
                <div v-else class="flex items-start justify-between gap-2">
                    <span>{{ value }}</span>
                    <button type="button" class="shrink-0 text-indigo-600 hover:underline" @click="openPriceEditor(row.id, editValue as number | null)">Sửa</button>
                </div>
            </div>
            <span v-else>{{ value }}</span>
        </template>
        <template #cell-stock="{ row, value, editValue, url }">
            <form v-if="editValue && editingStockId === row.id" class="min-w-52 space-y-1" data-stock-editor @submit.prevent="saveStock(editValue as StockEdit)" @click.stop>
                <label v-for="line in (editValue as StockEdit).rows" :key="line.variant_id" class="flex items-center justify-between gap-2 text-xs">
                    <span class="text-slate-600">{{ line.label }}</span>
                    <input v-model="stockDraft[line.variant_id]" :class="inputClass" class="w-20 text-right tabular-nums" type="number" min="0" max="1000000" step="1" required :aria-label="`Tồn ${line.label}`" />
                </label>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <button type="submit" :class="primaryButton" :disabled="savingStock">{{ savingStock ? 'Đang lưu…' : 'Lưu' }}</button>
                    <button type="button" :class="secondaryButton" :disabled="savingStock" @click="closeStockEditor">Hủy</button>
                </div>
                <span v-if="stockError" class="block text-xs text-rose-700" role="alert">{{ stockError }}</span>
                <span class="block text-xs text-slate-500">Số tồn thực tế trong kho (chưa trừ hàng đang giữ cho đơn).</span>
            </form>
            <div v-else class="flex items-start justify-between gap-2">
                <Link v-if="url" :href="url" class="text-indigo-600 hover:underline" :aria-label="`Mở lưới tồn kho của ${row.name ?? row.style_code}`">{{ value }}</Link>
                <span v-else>{{ value }}</span>
                <button v-if="editValue" type="button" class="shrink-0 text-indigo-600 hover:underline" data-stock-edit @click.stop="openStockEditor(row.id, editValue as StockEdit)">Sửa</button>
            </div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.product_status[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : row.status === 'draft' ? 'warning' : 'neutral'" />
        </template>
    </DataTable>

    <Pagination :current="products.current" :last="products.last" :from="products.from" :to="products.to" :total="products.total" :per-page="products.per_page" :per-page-options="perPageOptions" @change="search" @per-page-change="changePageSize" />
</template>
