<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { useMoney } from '@admin/money';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import type { PromotionLabels } from '../../labels';

defineProps<{
    baseUrl: string;
    labels: PromotionLabels;
    promotions: Array<{
        id: number;
        name: string;
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
}>();

const money = useMoney();
const columns = [
    { key: 'name', label: 'Khuyến mãi' },
    { key: 'action', label: 'Giảm' },
    { key: 'period', label: 'Hiệu lực', class: 'text-xs' },
    { key: 'usage', label: 'Đã dùng' },
    { key: 'vouchers', label: 'Voucher' },
    { key: 'actions', label: '', align: 'right' as const },
];

function describe(type: string, config: Record<string, number>): string {
    if (type === 'percent_off') return `Giảm ${(config.basis_points ?? 0) / 100}%`;
    if (type === 'amount_off') return `Giảm ${money(config.amount ?? 0)}`;
    return type;
}
</script>

<template>
    <Head title="Khuyến mãi" />
    <p class="mb-2 text-sm text-slate-500">Khuyến mãi</p>
    <PageHeader title="Khuyến mãi" subtitle="Priority cao đánh giá trước. Loại độc quyền chỉ áp khi chưa có khuyến mãi nào khác; tổng giảm mỗi dòng không vượt giá sàn.">
        <Link :href="campaignsUrl" :class="secondaryButton">Campaign</Link>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm khuyến mãi</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="promotions" :row-label="(promotion) => promotion.name" empty-text="Chưa có khuyến mãi nào.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="mt-1 flex flex-wrap items-center gap-1 text-xs text-slate-400">
                <StatusBadge :label="row.running ? 'Đang chạy' : row.status === 'active' ? 'Ngoài thời gian' : (labels.promotion_status[row.status] ?? row.status)" :tone="row.running ? 'success' : 'neutral'" />
                {{ row.stacking === 'exclusive' ? 'độc quyền' : 'cộng dồn' }} · priority {{ row.priority }}
            </div>
        </template>
        <template #cell-action="{ row }">{{ describe(row.action_type, row.action_config) }}</template>
        <template #cell-period="{ row }">{{ row.starts_at ?? '—' }} → {{ row.ends_at ?? '—' }}</template>
        <template #cell-usage="{ row }">{{ row.usage_count }}<span v-if="row.usage_limit"> / {{ row.usage_limit }}</span></template>
        <template #cell-vouchers="{ row }">{{ row.requires_voucher ? row.vouchers_count : 'Tự động' }}</template>
        <template #cell-actions="{ row }"><Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link></template>
    </DataTable>
</template>
