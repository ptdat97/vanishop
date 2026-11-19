<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import ExtensionTools from '@admin/Components/Extensions/ExtensionTools.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionAction, ExtensionList } from '@admin/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { type ReturnLabels, returnStatusTone } from '../../labels';

const props = defineProps<{
    reasonsUrl: string;
    baseUrl: string;
    labels: ReturnLabels;
    status: string;
    search: string;
    statuses: string[];
    counts: Record<string, number>;
    /** Thao tác hàng loạt (0.3.102): Duyệt, Đã nhận hàng. */
    actions: ExtensionAction[];
    /** Cột/lọc/thao tác hàng loạt/công cụ của plugin (EP v3 X1). */
    extensions: ExtensionList;
    returns: Array<{ id: number; number: string; status: string; reason_code: string; refund_amount: number; source: string; order_number: string | null; order_url: string; created_at: string | null }>;
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    sort: ListSortState | null;
    perPageOptions: number[];
}>();

const selected = ref<number[]>([]);
const search = ref(props.search);
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const allActions = computed(() => [...props.actions, ...props.extensions.actions]);
const bulk = computed(() => ({ ...props.extensions, actions: allActions.value }));
const columns = [
    { key: 'number', label: t('returns::admin.returns_index.request'), sortable: true },
    { key: 'order_number', label: t('returns::admin.returns_index.order'), class: 'font-mono' },
    { key: 'reason_code', label: t('returns::admin.returns_index.reason') },
    { key: 'refund_amount', label: t('returns::admin.returns_index.refund'), sortable: true },
    { key: 'status', label: t('returns::admin.returns_index.status') },
];

const list = useListQuery(() => props.baseUrl);
function filter(status: string, page?: number, perPage = props.pagination.per_page, sort = props.sort): void {
    list.visit({ status, q: search.value.trim(), ext: { ...ext }, ...list.sortQuery(sort), page, per_page: perPage });
}

function goToPage(page: number): void {
    filter(props.status, page);
}

function changePageSize(perPage: number): void {
    filter(props.status, undefined, perPage);
}
</script>

<template>
    <Head :title="t('returns::admin.returns_index.return_exchange')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('returns::admin.returns_index.return_exchange') }}</p>
    <PageHeader :title="t('returns::admin.returns_index.return_exchange_requests')" :subtitle="t('returns::admin.returns_index.refunds_calculated_from_line_totals')">
        <Link :href="reasonsUrl" :class="secondaryButton">{{ t('returns::admin.returns_index.return_reasons') }}</Link>
    </PageHeader>

    <form class="mb-3 flex max-w-md gap-2" @submit.prevent="filter(status)">
        <input v-model="search" :class="inputClass" type="search" :placeholder="t('returns::admin.returns_index.request_number_order_number')" :aria-label="t('returns::admin.returns_index.search_return_exchange_requests')" />
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <button type="submit" :class="secondaryButton">{{ t('returns::admin.returns_index.search') }}</button>
    </form>

    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" :class="status ? secondaryButton : primaryButton" @click="filter('')">{{ t('returns::admin.returns_index.all') }}</button>
        <button v-for="value in statuses" :key="value" type="button" :class="status === value ? primaryButton : secondaryButton" @click="filter(value)">
            {{ labels.return_status[value] ?? value }}
            <span class="ml-1 text-xs opacity-70">{{ counts[value] ?? 0 }}</span>
        </button>
    </div>

    <ExtensionTools :tools="extensions.tools ?? []" />
    <div v-if="allActions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500">{{ t('returns::admin.returns_index.count_selected', { count: selected.length }) }}</span>
        <ExtensionActions :actions="allActions" :ids="selected" />
    </div>
    <DataTable v-model:selected="selected" :columns="columns" :rows="returns" :sort="sort" :loading="list.loading.value" @sort="(next) => filter(props.status, 1, pagination.per_page, next)" :extensions="bulk" :row-label="(item) => item.number" :empty-text="search ? t('returns::admin.returns_index.no_matching_requests') : t('returns::admin.returns_index.no_requests_yet')">
        <template #cell-number="{ row }">
            <Link :href="`${baseUrl}/${row.id}`" class="font-mono text-indigo-600 hover:underline">{{ row.number }}</Link>
            <div class="text-xs text-slate-400">{{ row.created_at }} · {{ row.source === 'customer' ? t('returns::admin.returns_index.sent_customer') : t('returns::admin.returns_index.created_staff') }}</div>
        </template>
        <template #cell-order_number="{ row }">
            <Link :href="row.order_url" class="text-indigo-600 hover:underline">{{ row.order_number ?? '—' }}</Link>
        </template>
        <template #cell-reason_code="{ row }">{{ labels.reason[row.reason_code] ?? row.reason_code }}</template>
        <template #cell-refund_amount="{ row }"><MoneyText :amount="row.refund_amount" /></template>
        <template #cell-status="{ row }"><StatusBadge :label="labels.return_status[row.status] ?? row.status" :tone="returnStatusTone(row.status)" /></template>
    </DataTable>
    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="goToPage" @per-page-change="changePageSize" />
</template>
