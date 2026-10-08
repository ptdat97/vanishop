<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { stateClasses, stateLabels } from './labels';

defineProps<{
    baseUrl: string;
    promotionsUrl: string;
    campaigns: Array<{ id: number; code: string; name: string; state: string; starts_at: string; ends_at: string; promotions_count: number; price_lists_count: number }>;
    canManage: boolean;
}>();
</script>

<template>
    <Head title="Campaign" />
    <PageHeader title="Campaign" subtitle="Gói khuyến mãi + bảng giá sale/thành viên chạy chung một lịch; kích hoạt hoặc dừng tất cả bằng một nút.">
        <Link :href="promotionsUrl" :class="secondaryButton">Khuyến mãi</Link>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm campaign</Link>
    </PageHeader>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Campaign</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th class="px-4 py-2">Thời gian (giờ VN)</th>
                <th class="px-4 py-2">Thành viên</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="campaign in campaigns" :key="campaign.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <Link :href="`${baseUrl}/${campaign.id}/edit`" class="font-medium text-indigo-600 hover:underline">{{ campaign.name }}</Link>
                    <div class="font-mono text-xs text-slate-400">{{ campaign.code }}</div>
                </td>
                <td class="px-4 py-2"><span class="rounded px-2 py-0.5 text-xs" :class="stateClasses[campaign.state]">{{ stateLabels[campaign.state] ?? campaign.state }}</span></td>
                <td class="px-4 py-2 text-xs">{{ campaign.starts_at }} → {{ campaign.ends_at }}</td>
                <td class="px-4 py-2 text-xs">{{ campaign.promotions_count }} khuyến mãi · {{ campaign.price_lists_count }} bảng giá</td>
            </tr>
            <tr v-if="!campaigns.length">
                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có campaign.</td>
            </tr>
        </tbody>
    </table>
</template>
