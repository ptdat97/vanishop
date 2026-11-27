<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { stateTone } from '../../priceListState';

type Overview = {
    running: Array<{ id: number; name: string; ends_at: string | null; ending_soon: boolean; prices: number }>;
    upcoming: Array<{ id: number; name: string; starts_at: string | null; prices: number }>;
    missing_base: { count: number; samples: Array<{ sku: string; product: string; url: string }> };
    warnings: { checked: number; capped: boolean; labels: Record<string, number>; samples: Array<{ sku: string; list: string; label: string }> };
};

const props = defineProps<{
    historyUrl: string;
    overview: Overview;
    baseUrl: string;
    labels: { price_list_type: Record<string, string>; price_list_state: Record<string, string> };
    priceLists: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        customer_group: string | null;
        priority: number;
        status: string;
        state: string;
        starts_at: string | null;
        ends_at: string | null;
        prices_count: number;
        managed: { owner: string; url: string | null } | null;
    }>;
    canManage: boolean;
    /** Bảng loại niêm yết ngoài bảng chính (dữ liệu cũ) — cần đổi sang Sale/Thành viên hoặc xoá. */
    extraBaseLists: Array<{ id: number; name: string; code: string }>;
}>();

const columns = [
    { key: 'name', label: t('pricing::admin.price_lists_index.price_lists') },
    { key: 'state', label: t('pricing::admin.price_lists_index.status') },
    { key: 'type', label: t('pricing::admin.price_lists_index.type') },
    { key: 'period', label: t('pricing::admin.price_lists_index.active_period'), class: 'text-xs' },
    { key: 'prices_count', label: t('pricing::admin.price_lists_index.skus') },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head :title="t('pricing::admin.price_lists_index.price_lists')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('pricing::admin.price_lists_index.selling_price') }}</p>
    <PageHeader :title="t('pricing::admin.price_lists_index.price_lists')" :subtitle="t('pricing::admin.price_lists_index.click_price_list_view_edit')">
        <Link :href="historyUrl" :class="secondaryButton" data-history>{{ t('pricing::admin.price_lists_index.price_history') }}</Link>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('pricing::admin.price_lists_index.add_price_list') }}</Link>
    </PageHeader>
    <section class="mb-6 grid gap-3 md:grid-cols-3" data-price-overview>
        <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
            <h2 class="mb-2 font-medium">{{ t('pricing::admin.price_lists_index.running') }}</h2>
            <p v-if="!overview.running.length" class="text-slate-500">{{ t('pricing::admin.price_lists_index.no_sale_member_price_lists') }}</p>
            <ul v-else class="space-y-1">
                <li v-for="item in overview.running" :key="item.id">
                    <Link :href="`${baseUrl}/${item.id}/prices`" class="text-indigo-700 hover:underline">{{ item.name }}</Link>
                    <span class="text-slate-500"> · {{ item.prices }} SKU</span>
                    <span v-if="item.ends_at" :class="item.ending_soon ? 'font-medium text-amber-700' : 'text-slate-500'"> {{ t('pricing::admin.price_lists_index.ends_ends', { ends_at: item.ends_at }) }}</span>
                </li>
            </ul>
            <template v-if="overview.upcoming.length">
                <h3 class="mt-3 mb-1 font-medium">{{ t('pricing::admin.price_lists_index.starting_soon_7_days') }}</h3>
                <ul class="space-y-1">
                    <li v-for="item in overview.upcoming" :key="item.id">
                        <Link :href="`${baseUrl}/${item.id}/prices`" class="text-indigo-700 hover:underline">{{ item.name }}</Link>
                        <span class="text-slate-500"> {{ t('pricing::admin.price_lists_index.prices_skus_from_starts', { prices: item.prices, starts_at: item.starts_at }) }}</span>
                    </li>
                </ul>
            </template>
        </div>
        <div class="rounded-lg border bg-white p-4 text-sm" :class="overview.missing_base.count ? 'border-amber-300' : 'border-slate-200'" data-missing-base>
            <h2 class="mb-2 font-medium">{{ t('pricing::admin.price_lists_index.skus_sale_without_list_price', { count: overview.missing_base.count }) }}</h2>
            <p v-if="!overview.missing_base.count" class="text-slate-500">{{ t('pricing::admin.price_lists_index.every_sku_sale_has_price') }}</p>
            <ul v-else class="space-y-1">
                <li v-for="sample in overview.missing_base.samples" :key="sample.sku">
                    <Link :href="sample.url" class="font-mono text-xs text-indigo-700 hover:underline">{{ sample.sku }}</Link>
                    <span class="text-slate-500"> · {{ sample.product }}</span>
                </li>
            </ul>
        </div>
        <div class="rounded-lg border bg-white p-4 text-sm" :class="Object.keys(overview.warnings.labels).length ? 'border-red-300' : 'border-slate-200'" data-price-warnings>
            <h2 class="mb-2 font-medium">{{ t('pricing::admin.price_lists_index.warnings_running_prices') }}</h2>
            <p v-if="!Object.keys(overview.warnings.labels).length" class="text-slate-500">{{ t('pricing::admin.price_lists_index.no_warnings_checked_prices_checked', { checked: overview.warnings.checked }) }}</p>
            <template v-else>
                <ul class="mb-2 space-y-1">
                    <li v-for="(count, label) in overview.warnings.labels" :key="label" class="font-medium text-red-700">{{ label }}: {{ count }} SKU</li>
                </ul>
                <ul class="space-y-1 text-xs text-slate-600">
                    <li v-for="sample in overview.warnings.samples" :key="`${sample.sku}-${sample.list}-${sample.label}`"><span class="font-mono">{{ sample.sku }}</span> · {{ sample.list }}</li>
                </ul>
            </template>
            <p v-if="overview.warnings.capped" class="mt-2 text-xs text-slate-500">{{ t('pricing::admin.price_lists_index.only_first_checked_prices_were', { checked: overview.warnings.checked }) }}</p>
        </div>
    </section>
    <div v-if="extraBaseLists.length" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" data-extra-base>
        {{ t('pricing::admin.price_lists_index.store_uses_only_one_list') }} <span class="font-mono">base</span>{{ t('pricing::admin.price_lists_index.following_lists_surplus_list_price') }}
        <strong>Sale</strong> {{ t('pricing::admin.price_lists_index.text') }} <strong>{{ t('pricing::admin.price_lists_index.member') }}</strong>{{ t('pricing::admin.price_lists_index.delete_them_if_unused') }}
        <span v-for="(list, index) in extraBaseLists" :key="list.id"><template v-if="index">, </template><Link :href="`${baseUrl}/${list.id}/edit`" class="font-medium underline">{{ list.name }}</Link></span>.
    </div>
    <DataTable :columns="columns" :rows="priceLists" :row-label="(list) => list.name" :row-click="(list) => router.visit(list.managed?.url ?? `${props.baseUrl}/${list.id}/prices`)" :empty-text="t('pricing::admin.price_lists_index.no_price_lists_yet')">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}</div>
            <StatusBadge v-if="row.managed" :label="t('pricing::admin.price_lists_index.managed')" tone="info" />
        </template>
        <template #cell-state="{ row }"><StatusBadge :label="labels.price_list_state[row.state] ?? row.state" :tone="stateTone(row.state)" /></template>
        <template #cell-type="{ row }">
            {{ labels.price_list_type[row.type] ?? row.type }}
            <div v-if="row.customer_group" class="text-xs text-slate-500">{{ t('pricing::admin.price_lists_index.group_customer_group', { customer_group: row.customer_group }) }}</div>
        </template>
        <template #cell-period="{ row }">
            <template v-if="row.starts_at || row.ends_at">{{ row.starts_at ?? '—' }} → {{ row.ends_at ?? '—' }}</template>
            <span v-else class="text-slate-400">{{ t('pricing::admin.price_lists_index.unlimited') }}</span>
        </template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage && !row.managed" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline" @click.stop>{{ t('pricing::admin.price_lists_index.information') }}</Link>
        </template>
    </DataTable>
</template>
