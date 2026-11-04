<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
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
        data: Array<{ id: number; style_code: string; name: string | null; brand: string | null; status: string; colors: number; image_url: string | null }>;
        current: number;
        last: number;
        total: number;
    };
    canManage: boolean;
    extensions: Omit<ExtensionList, 'actions'>;
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'products')?.url ?? '');
const q = ref(props.filters.q);
const status = ref(props.filters.status ?? '');
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const columns = [
    { key: 'image', label: '', class: 'w-16' },
    { key: 'name', label: 'Sản phẩm', class: 'font-medium' },
    { key: 'style_code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'brand', label: 'Thương hiệu' },
    { key: 'colors', label: 'Màu' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const },
];

function search(page = 1): void {
    router.get(baseUrl.value, { q: q.value || undefined, status: status.value || undefined, ext, page: page > 1 ? page : undefined }, { preserveState: true, replace: true });
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

    <DataTable :columns="columns" :rows="products.data" :extensions="{ ...extensions, actions: [] }" :row-label="(product) => product.style_code" empty-text="Không có sản phẩm nào.">
        <template #cell-image="{ row }">
            <img v-if="row.image_url" :src="row.image_url" alt="" class="h-12 w-10 rounded object-cover" />
            <div v-else class="h-12 w-10 rounded bg-slate-100" />
        </template>
        <template #cell-brand="{ row }">{{ row.brand ?? '—' }}</template>
        <template #cell-status="{ row }">
            <StatusBadge :label="labels.product_status[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : row.status === 'draft' ? 'warning' : 'neutral'" />
        </template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
        </template>
    </DataTable>

    <Pagination :current="products.current" :last="products.last" @change="search" />
</template>
