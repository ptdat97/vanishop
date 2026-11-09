<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import { codeOptions, recordOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

type CustomerFilters = { q: string; status: string; group: number | null; tag: string };
const props = defineProps<{
    baseUrl: string;
    labels: { customer_status: Record<string, string> };
    filters: { q: string | null; status: string | null; group: number | null; tag: string | null };
    statuses: string[];
    groups: Array<{ id: number; code: string; name: string }>;
    groupsUrl: string;
    customers: Array<{
        id: string;
        ref: number;
        phone: string | null;
        email: string | null;
        full_name: string | null;
        status: string;
        registered: boolean;
        created_at: string | null;
        group: string | null;
    }>;
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    extensions: Omit<ExtensionList, 'actions'>;
}>();

const columns = [
    { key: 'full_name', label: 'Khách' },
    { key: 'phone', label: 'SĐT', class: 'font-mono text-xs' },
    { key: 'email', label: 'Email', class: 'text-xs' },
    { key: 'registered', label: 'Loại', class: 'text-xs' },
    { key: 'group', label: 'Nhóm', class: 'text-xs' },
    { key: 'created_at', label: 'Tạo lúc', class: 'text-xs' },
];
const filters = reactive<CustomerFilters>({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    group: props.filters.group,
    tag: props.filters.tag ?? '',
});
const perPageOptions = [30, 50, 100];
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const hasActiveFilters = computed(() =>
    Object.values(filters).some((value) => value !== '' && value !== null) || Object.values(ext).some((value) => value !== ''),
);

function search(page?: number, pageSize = props.pagination.per_page): void {
    const query = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null));
    const extensionFilters = Object.fromEntries(Object.entries(ext).filter(([, value]) => value !== ''));
    router.get(props.baseUrl, { ...query, ext: extensionFilters, page, per_page: pageSize }, { preserveState: true });
}

function clearFilters(): void {
    Object.assign(filters, { q: '', status: '', group: null, tag: '' });
    for (const key of Object.keys(ext)) {
        ext[key] = '';
    }
    search();
}

function openCustomer(customer: { id: string }): void {
    router.visit(`${props.baseUrl}/${customer.id}${window.location.search}`);
}

function changePageSize(pageSize: number): void {
    search(1, pageSize);
}
</script>

<template>
    <Head title="Khách hàng" />
    <PageHeader title="Khách hàng" :subtitle="`${pagination.total} khách`">
        <Link :href="groupsUrl" :class="secondaryButton">Nhóm khách</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap items-center gap-2" @submit.prevent="search()">
        <input v-model="filters.q" :class="[inputClass, 'w-72']" placeholder="SĐT, email, tên hoặc mã khách" aria-label="Tìm khách hàng" />
        <div class="w-48">
            <Select v-model="filters.status" :options="codeOptions(statuses, labels.customer_status)" empty-label="Mọi trạng thái" aria-label="Trạng thái" />
        </div>
        <div class="w-44">
            <Select v-model="filters.group" :options="recordOptions(groups, (group) => group.name)" empty-label="Mọi nhóm" :empty-value="null" aria-label="Nhóm khách" />
        </div>
        <input v-model="filters.tag" :class="[inputClass, 'w-36']" placeholder="Tag" aria-label="Tag khách hàng" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">Áp dụng</button>
        <button v-if="hasActiveFilters" type="button" :class="secondaryButton" @click="clearFilters">Bỏ tất cả bộ lọc</button>
        <span v-if="hasActiveFilters" class="text-xs text-indigo-700" aria-live="polite">Đang lọc</span>
    </form>

    <DataTable :columns="columns" :rows="customers" :extensions="{ ...extensions, actions: [] }" :extension-id="(customer) => customer.ref" :row-label="(customer) => customer.id" :row-click="openCustomer" empty-text="Không có khách nào.">
        <template #cell-full_name="{ row }">
            <span class="font-medium">{{ row.full_name ?? '(chưa có tên)' }}</span>
            <div class="font-mono text-xs text-slate-400">
                {{ row.id }}<span v-if="row.status !== 'active'"> · {{ labels.customer_status[row.status] ?? row.status }}</span>
            </div>
        </template>
        <template #cell-phone="{ row }">{{ row.phone ?? '—' }}</template>
        <template #cell-email="{ row }">{{ row.email ?? '—' }}</template>
        <template #cell-registered="{ row }">{{ row.registered ? 'Tài khoản' : 'Khách vãng lai' }}</template>
        <template #cell-group="{ row }">{{ row.group ?? '—' }}</template>
    </DataTable>

    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="search($event)" @per-page-change="changePageSize" />
</template>
