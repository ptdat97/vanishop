<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    filters: { q: string; status: string | null };
    products: {
        data: Array<{ id: number; style_code: string; name: string | null; brand: string | null; status: string; colors: number; image_url: string | null; views: number }>;
        current: number;
        last: number;
        total: number;
    };
    canManage: boolean;
    canManagePrices: boolean;
    extensions: ExtensionList;
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'products')?.url ?? '');
const q = ref(props.filters.q);
const status = ref(props.filters.status ?? '');
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
const editingPriceId = ref<number | null>(null);
const priceDraft = ref('');
const priceError = ref('');
const savingPrice = ref(false);
const columns = [
    { key: 'image', label: '', class: 'w-16' },
    { key: 'name', label: 'Sản phẩm', class: 'font-medium' },
    { key: 'style_code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'brand', label: 'Thương hiệu' },
    { key: 'colors', label: 'Màu' },
    { key: 'views', label: 'Lượt xem 30 ngày', align: 'right' as const, class: 'tabular-nums' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const },
];

function search(page = 1): void {
    router.get(baseUrl.value, { q: q.value || undefined, status: status.value || undefined, ext, page: page > 1 ? page : undefined }, { preserveState: true, replace: true });
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
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm sản phẩm</Link>
    </PageHeader>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="inputClass" class="max-w-xs" type="search" placeholder="Tìm theo tên hoặc mã (không cần dấu)" aria-label="Tìm sản phẩm" />
        <div class="w-44"><Select v-model="status" :options="labelOptions(labels.product_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" /></div>
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>

    <div v-if="extensions.actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500">Đã chọn {{ selected.length }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="selected" />
    </div>
    <DataTable v-model:selected="selected" :columns="columns" :rows="products.data" :extensions="extensions" :row-label="(product) => product.style_code" empty-text="Không có sản phẩm nào.">
        <template #cell-image="{ row }">
            <img v-if="row.image_url" :src="row.image_url" alt="" class="h-12 w-10 rounded object-cover" />
            <div v-else class="h-12 w-10 rounded bg-slate-100" />
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
                    <button type="button" class="shrink-0 text-indigo-600 hover:underline" @click="openPriceEditor(row.id, editValue)">Sửa</button>
                </div>
            </div>
            <span v-else>{{ value }}</span>
        </template>
        <template #cell-stock="{ row, value, url }">
            <Link v-if="url" :href="url" class="text-indigo-600 hover:underline" :aria-label="`Mở lưới tồn kho của ${row.name ?? row.style_code}`">{{ value }}</Link>
            <span v-else>{{ value }}</span>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.product_status[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : row.status === 'draft' ? 'warning' : 'neutral'" />
        </template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
        </template>
    </DataTable>

    <Pagination :current="products.current" :last="products.last" @change="search" />
</template>
