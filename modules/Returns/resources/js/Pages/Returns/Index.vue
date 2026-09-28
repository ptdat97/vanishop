<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    brand: { name: string; slug: string };
    baseUrl: string;
    status: string;
    statuses: string[];
    returns: Array<{ id: number; number: string; status: string; reason_code: string; refund_amount: number; source: string; created_at: string | null }>;
}>();

const labels: Record<string, string> = { requested: 'Chờ duyệt', approved: 'Đã duyệt', in_transit: 'Đang gửi về', received: 'Đã nhận hàng', resolved: 'Hoàn tất', rejected: 'Từ chối', cancelled: 'Đã huỷ' };
const reasons: Record<string, string> = { wrong_size: 'Sai size', not_as_described: 'Không đúng mô tả', defective: 'Lỗi sản phẩm', changed_mind: 'Đổi ý', other: 'Khác' };
const vnd = (amount: number): string => `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;

function filter(status: string): void {
    router.get(props.baseUrl, status ? { status } : {}, { preserveState: true });
}
</script>

<template>
    <Head :title="`Đổi/trả · ${brand.name}`" />
    <p class="mb-2 text-sm text-slate-500">Đổi/trả · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
    <PageHeader title="Yêu cầu đổi/trả" subtitle="Tiền hoàn tính từ thành tiền dòng đã trừ giảm giá; không hoàn phí giao." />

    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" :class="status ? secondaryButton : primaryButton" @click="filter('')">Tất cả</button>
        <button v-for="value in statuses" :key="value" type="button" :class="status === value ? primaryButton : secondaryButton" @click="filter(value)">{{ labels[value] ?? value }}</button>
    </div>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr><th class="px-4 py-2">Yêu cầu</th><th class="px-4 py-2">Lý do</th><th class="px-4 py-2">Tiền hoàn</th><th class="px-4 py-2">Trạng thái</th></tr>
        </thead>
        <tbody>
            <tr v-for="item in returns" :key="item.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <Link :href="`${baseUrl}/${item.id}`" class="font-mono text-indigo-600 hover:underline">{{ item.number }}</Link>
                    <div class="text-xs text-slate-400">{{ item.created_at }} · {{ item.source === 'customer' ? 'khách gửi' : 'nhân viên tạo' }}</div>
                </td>
                <td class="px-4 py-2">{{ reasons[item.reason_code] ?? item.reason_code }}</td>
                <td class="px-4 py-2">{{ vnd(item.refund_amount) }}</td>
                <td class="px-4 py-2">{{ labels[item.status] ?? item.status }}</td>
            </tr>
            <tr v-if="!returns.length"><td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có yêu cầu nào.</td></tr>
        </tbody>
    </table>
</template>
