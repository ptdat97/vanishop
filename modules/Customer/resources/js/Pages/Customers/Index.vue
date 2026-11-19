<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
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
    sort: ListSortState | null;
    extensions: Omit<ExtensionList, 'actions'>;
}>();

const columns = [
    { key: 'full_name', label: t('customer::admin.customers_index.customer'), sortable: true },
    { key: 'phone', label: t('customer::admin.customers_index.phone'), class: 'font-mono text-xs' },
    { key: 'email', label: 'Email', class: 'text-xs' },
    { key: 'registered', label: t('customer::admin.customers_index.type'), class: 'text-xs' },
    { key: 'group', label: t('customer::admin.customers_index.group'), class: 'text-xs' },
    { key: 'created_at', label: t('customer::admin.customers_index.created'), class: 'text-xs', sortable: true },
];
const list = useListQuery(() => props.baseUrl);
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

function search(page?: number, pageSize = props.pagination.per_page, sort = props.sort): void {
    list.visit({ ...filters, ext: { ...ext }, ...list.sortQuery(sort), page, per_page: pageSize });
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
    <Head :title="t('customer::admin.customers_index.customers')" />
    <PageHeader :title="t('customer::admin.customers_index.customers')" :subtitle="t('customer::admin.customers_index.total_customers', { total: pagination.total })">
        <Link :href="groupsUrl" :class="secondaryButton">{{ t('customer::admin.customers_index.customer_groups') }}</Link>
    </PageHeader>

    <form class="mb-3 flex flex-wrap items-center gap-2" @submit.prevent="search()">
        <input v-model="filters.q" :class="[inputClass, 'w-72']" :placeholder="t('customer::admin.customers_index.phone_email_name_customer_id')" :aria-label="t('customer::admin.customers_index.search_customers')" />
        <div class="w-48">
            <Select v-model="filters.status" :options="codeOptions(statuses, labels.customer_status)" :empty-label="t('customer::admin.customers_index.all_statuses')" :aria-label="t('customer::admin.customers_index.status')" />
        </div>
        <div class="w-44">
            <Select v-model="filters.group" :options="recordOptions(groups, (group) => group.name)" :empty-label="t('customer::admin.customers_index.all_groups')" :empty-value="null" :aria-label="t('customer::admin.customers_index.customer_groups')" />
        </div>
        <input v-model="filters.tag" :class="[inputClass, 'w-36']" placeholder="Tag" :aria-label="t('customer::admin.customers_index.customer_tag')" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">{{ t('customer::admin.customers_index.apply') }}</button>
        <button v-if="hasActiveFilters" type="button" :class="secondaryButton" @click="clearFilters">{{ t('customer::admin.customers_index.clear_all_filters') }}</button>
        <span v-if="hasActiveFilters" class="text-xs text-indigo-700" aria-live="polite">{{ t('customer::admin.customers_index.filtering') }}</span>
    </form>

    <DataTable :columns="columns" :rows="customers" :sort="sort" :loading="list.loading.value" @sort="(next) => search(1, pagination.per_page, next)" :extensions="{ ...extensions, actions: [] }" :extension-id="(customer) => customer.ref" :row-label="(customer) => customer.id" :row-click="openCustomer" :empty-text="t('customer::admin.customers_index.no_customers')">
        <template #cell-full_name="{ row }">
            <span class="font-medium">{{ row.full_name ?? t('customer::admin.customers_index.no_name_yet') }}</span>
            <div class="font-mono text-xs text-slate-400">
                {{ row.id }}<span v-if="row.status !== 'active'"> · {{ labels.customer_status[row.status] ?? row.status }}</span>
            </div>
        </template>
        <template #cell-phone="{ row }">{{ row.phone ?? '—' }}</template>
        <template #cell-email="{ row }">{{ row.email ?? '—' }}</template>
        <template #cell-registered="{ row }">{{ row.registered ? t('customer::admin.customers_index.account') : t('customer::admin.customers_index.guest') }}</template>
        <template #cell-group="{ row }">{{ row.group ?? '—' }}</template>
    </DataTable>

    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="search($event)" @per-page-change="changePageSize" />
</template>
