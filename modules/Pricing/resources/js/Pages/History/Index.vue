<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import { useListQuery } from '@admin/list';
import { recordOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

type Row = { id: number; at: string; sku: string; product: string | null; price_list: string | null; old: string | null; new: string | null; old_compare: string | null; new_compare: string | null; actor: string };

/** Lịch sử giá theo SKU (A5): mọi lần đổi giá — ai, lúc nào, bảng giá nào, giá cũ → mới. */
const props = defineProps<{
    filters: { q: string; price_list: number | null };
    priceLists: Array<{ id: number; label: string }>;
    rows: { data: Row[]; current: number; last: number; from: number | null; to: number | null; total: number; per_page: number };
    urls: { base: string; priceLists: string };
}>();

const q = ref(props.filters.q);
const priceList = ref<number | ''>(props.filters.price_list ?? '');
const list = useListQuery(() => props.urls.base);
const columns = [
    { key: 'at', label: 'Thời điểm', class: 'whitespace-nowrap tabular-nums' },
    { key: 'sku', label: 'SKU', class: 'font-mono text-xs' },
    { key: 'product', label: 'Sản phẩm' },
    { key: 'price_list', label: 'Bảng giá' },
    { key: 'price', label: 'Giá', class: 'tabular-nums' },
    { key: 'compare', label: 'Giá gạch', class: 'tabular-nums' },
    { key: 'actor', label: 'Người đổi' },
];

function search(page = 1, perPage = props.rows.per_page): void {
    list.visit({ q: q.value, price_list: priceList.value, page, per_page: perPage });
}
</script>

<template>
    <Head title="Lịch sử giá" />
    <PageHeader title="Lịch sử giá" :subtitle="`${rows.total} lần đổi giá`">
        <Link :href="urls.priceLists" :class="secondaryButton">Bảng giá</Link>
    </PageHeader>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="search()">
        <input v-model="q" :class="inputClass" class="max-w-xs" type="search" placeholder="SKU, mã hoặc tên sản phẩm" aria-label="Tìm SKU" />
        <div class="w-56"><Select v-model="priceList" :options="recordOptions(priceLists)" empty-label="Mọi bảng giá" aria-label="Bảng giá" /></div>
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>

    <DataTable :columns="columns" :rows="rows.data" :loading="list.loading.value" :row-label="(row) => row.sku" empty-text="Chưa có lần đổi giá nào.">
        <template #cell-product="{ row }">{{ row.product ?? '—' }}</template>
        <template #cell-price_list="{ row }">{{ row.price_list ?? '—' }}</template>
        <template #cell-price="{ row }">
            <span class="text-slate-500">{{ row.old ?? '—' }}</span> → <span class="font-medium">{{ row.new ?? 'gỡ khỏi bảng' }}</span>
        </template>
        <template #cell-compare="{ row }">
            <span v-if="row.old_compare !== row.new_compare"><span class="text-slate-500">{{ row.old_compare ?? '—' }}</span> → {{ row.new_compare ?? '—' }}</span>
            <span v-else class="text-slate-400">{{ row.new_compare ?? '—' }}</span>
        </template>
    </DataTable>
    <Pagination :current="rows.current" :last="rows.last" :from="rows.from" :to="rows.to" :total="rows.total" :per-page="rows.per_page" :per-page-options="[50, 100]" @change="search" @per-page-change="(size) => search(1, size)" />
</template>
