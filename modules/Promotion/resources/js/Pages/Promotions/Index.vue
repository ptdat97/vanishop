<script setup lang="ts">
import { t } from '@admin/i18n';
import { useListQuery, type ListSortState } from '@admin/list';
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import ExtensionTools from '@admin/Components/Extensions/ExtensionTools.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { useMoney } from '@admin/money';
import { primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';
import type { PromotionLabels } from '../../labels';

const props = defineProps<{
    baseUrl: string;
    labels: PromotionLabels;
    promotions: Array<{
        id: number;
        name: string;
        managed: boolean;
        status: string;
        running: boolean;
        action_type: string;
        action_config: Record<string, number>;
        requires_voucher: boolean;
        stacking: string;
        priority: number;
        usage_count: number;
        usage_limit: number | null;
        vouchers_count: number;
        starts_at: string | null;
        ends_at: string | null;
    }>;
    canManage: boolean;
    campaignsUrl: string;
    sort: ListSortState | null;
    /** Cột/lọc/thao tác hàng loạt/công cụ của plugin (EP v3 X1). */
    extensions: ExtensionList;
}>();
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
const list = useListQuery(() => props.baseUrl);
const reload = (sort = props.sort): void => list.visit({ ext: { ...ext }, ...list.sortQuery(sort) });
watch(ext, () => reload());

const money = useMoney();
const columns = [
    { key: 'name', label: t('promotion::admin.promotions_index.promotion'), sortable: true },
    { key: 'action', label: t('promotion::admin.promotions_index.reduce') },
    { key: 'period', label: t('promotion::admin.promotions_index.active_period'), class: 'text-xs', sortable: true },
    { key: 'usage', label: t('promotion::admin.promotions_index.used'), sortable: true },
    { key: 'vouchers', label: 'Voucher' },
];

function describe(type: string, config: Record<string, number>): string {
    if (type === 'percent_off') return t('promotion::admin.promotions_index.basis_points_off', { basis_points: (config.basis_points ?? 0) / 100 });
    if (type === 'amount_off') return t('promotion::admin.promotions_index.amount_off', { amount: money(config.amount ?? 0) });
    return type;
}
</script>

<template>
    <Head :title="t('promotion::admin.promotions_index.promotion')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('promotion::admin.promotions_index.promotion') }}</p>
    <PageHeader :title="t('promotion::admin.promotions_index.promotion')" :subtitle="t('promotion::admin.promotions_index.higher_priority_evaluated_first_exclusive')">
        <Link :href="campaignsUrl" :class="secondaryButton">Campaign</Link>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">{{ t('promotion::admin.promotions_index.add_promotion') }}</Link>
    </PageHeader>
    <ExtensionTools :tools="extensions.tools ?? []" />
    <div v-if="extensions.filters.length || extensions.actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <ExtensionFilters v-model="ext" :filters="extensions.filters" />
        <template v-if="extensions.actions.length">
            <span class="text-slate-500">{{ t('promotion::admin.promotions_index.count_selected', { count: selected.length }) }}</span>
            <ExtensionActions :actions="extensions.actions" :ids="selected" :selection-description="t('promotion::admin.promotions_index.promotions')" />
        </template>
    </div>
    <DataTable v-model:selected="selected" :sort="sort" :loading="list.loading.value" @sort="(next) => reload(next)" :extensions="extensions" :columns="columns" :rows="promotions" :row-label="(promotion) => promotion.name" :row-click="canManage ? (promotion) => router.visit(`${props.baseUrl}/${promotion.id}/edit`) : undefined" :empty-text="t('promotion::admin.promotions_index.no_promotions_yet')">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="mt-1 flex flex-wrap items-center gap-1 text-xs text-slate-400">
                <StatusBadge v-if="row.managed" :label="t('promotion::admin.promotions_index.managed')" tone="info" />
                <StatusBadge :label="row.running ? t('promotion::admin.promotions_index.running') : row.status === 'active' ? t('promotion::admin.promotions_index.outside_period') : (labels.promotion_status[row.status] ?? row.status)" :tone="row.running ? 'success' : 'neutral'" />
                {{ row.stacking === 'exclusive' ? t('promotion::admin.promotions_index.exclusive') : t('promotion::admin.promotions_index.stackable') }} · priority {{ row.priority }}
            </div>
        </template>
        <template #cell-action="{ row }">{{ describe(row.action_type, row.action_config) }}</template>
        <template #cell-period="{ row }">{{ row.starts_at ?? '—' }} → {{ row.ends_at ?? '—' }}</template>
        <template #cell-usage="{ row }">{{ row.usage_count }}<span v-if="row.usage_limit"> / {{ row.usage_limit }}</span></template>
        <template #cell-vouchers="{ row }">{{ row.requires_voucher ? row.vouchers_count : t('promotion::admin.promotions_index.automatic') }}</template>
    </DataTable>
</template>
