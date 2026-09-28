<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    brand: { name: string; slug: string };
    baseUrl: string;
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
}>();

const vnd = new Intl.NumberFormat('vi-VN');
function describe(type: string, config: Record<string, number>): string {
    if (type === 'percent_off') return `Giảm ${(config.basis_points ?? 0) / 100}%`;
    if (type === 'amount_off') return `Giảm ${vnd.format(config.amount ?? 0)} ₫`;
    return type;
}
</script>

<template>
    <Head :title="`Khuyến mãi · ${brand.name}`" />
    <p class="mb-2 text-sm text-slate-500">Khuyến mãi · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
    <PageHeader title="Khuyến mãi" subtitle="Priority cao đánh giá trước. Loại độc quyền chỉ áp khi chưa có khuyến mãi nào khác; tổng giảm mỗi dòng không vượt giá sàn.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm khuyến mãi</Link>
    </PageHeader>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Khuyến mãi</th>
                <th class="px-4 py-2">Giảm</th>
                <th class="px-4 py-2">Hiệu lực</th>
                <th class="px-4 py-2">Đã dùng</th>
                <th class="px-4 py-2">Voucher</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="promotion in promotions" :key="promotion.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <div class="font-medium">{{ promotion.name }}</div>
                    <div class="text-xs text-slate-400">
                        {{ promotion.running ? 'Đang chạy' : promotion.status === 'active' ? 'Ngoài thời gian' : 'Tắt' }} ·
                        {{ promotion.stacking === 'exclusive' ? 'độc quyền' : 'cộng dồn' }} · priority {{ promotion.priority }}
                    </div>
                </td>
                <td class="px-4 py-2">{{ describe(promotion.action_type, promotion.action_config) }}</td>
                <td class="px-4 py-2 text-xs">{{ promotion.starts_at ?? '—' }} → {{ promotion.ends_at ?? '—' }}</td>
                <td class="px-4 py-2">{{ promotion.usage_count }}<span v-if="promotion.usage_limit"> / {{ promotion.usage_limit }}</span></td>
                <td class="px-4 py-2">{{ promotion.requires_voucher ? promotion.vouchers_count : 'Tự động' }}</td>
                <td class="px-4 py-2 text-right">
                    <Link v-if="canManage" :href="`${baseUrl}/${promotion.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
                </td>
            </tr>
            <tr v-if="!promotions.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Chưa có khuyến mãi nào.</td>
            </tr>
        </tbody>
    </table>
</template>
