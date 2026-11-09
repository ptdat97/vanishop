<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { campaignStateTone, type PromotionLabels } from '../../labels';

const props = defineProps<{
    baseUrl: string;
    labels: PromotionLabels;
    promotionsUrl: string;
    campaigns: Array<{ id: number; code: string; name: string; state: string; starts_at: string; ends_at: string; promotions_count: number; price_lists_count: number }>;
    canManage: boolean;
}>();

const columns = [
    { key: 'name', label: 'Campaign' },
    { key: 'state', label: 'Trạng thái' },
    { key: 'period', label: 'Thời gian (giờ cửa hàng)', class: 'text-xs' },
    { key: 'members', label: 'Thành viên', class: 'text-xs' },
];
</script>

<template>
    <Head title="Campaign" />
    <PageHeader title="Campaign" subtitle="Gói khuyến mãi + bảng giá sale/thành viên chạy chung một lịch; kích hoạt hoặc dừng tất cả bằng một nút.">
        <Link :href="promotionsUrl" :class="secondaryButton">Khuyến mãi</Link>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm campaign</Link>
    </PageHeader>
    <DataTable :columns="columns" :rows="campaigns" :row-label="(campaign) => campaign.code" :row-click="(campaign) => router.visit(`${props.baseUrl}/${campaign.id}/edit`)" empty-text="Chưa có campaign.">
        <template #cell-name="{ row }">
            <span class="font-medium">{{ row.name }}</span>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}</div>
        </template>
        <template #cell-state="{ row }"><StatusBadge :label="labels.campaign_state[row.state] ?? row.state" :tone="campaignStateTone(row.state)" /></template>
        <template #cell-period="{ row }">{{ row.starts_at }} → {{ row.ends_at }}</template>
        <template #cell-members="{ row }">{{ row.promotions_count }} khuyến mãi · {{ row.price_lists_count }} bảng giá</template>
    </DataTable>
</template>
