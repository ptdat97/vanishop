<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Line = {
    id: number;
    location: string;
    sku: string;
    classification: string;
    expected: number;
    actual: number;
    difference: number;
    note: string | null;
    detected_at: string | null;
    resolved_at: string | null;
    resolution: string | null;
};

const props = defineProps<{
    backUrl: string;
    run: {
        id: number;
        source: string;
        source_label: string;
        checked: number;
        discrepancies: number;
        repaired: number;
        open: number;
        started_at: string | null;
        finished_at: string | null;
    };
    lines: Line[];
}>();

const classificationLabels: Record<string, string> = {
    external_mismatch: 'Nguồn ngoài ≠ VaniShop',
    reserved_mismatch: 'Hàng giữ ≠ reserved',
    reserved_off_ledger: 'reserved lệch sổ biến động',
    on_hand_off_ledger: 'Tồn lệch sổ biến động',
    negative_on_hand: 'Tồn âm',
};

const resolutionLabels: Record<string, string> = {
    repaired: 'Đã sửa reserved',
    applied: 'Đã áp số nguồn',
    rejected: 'Bị từ chối — xem ghi chú',
};

const title = computed(() => `Đối soát #${props.run.id} — ${props.run.source_label}`);
</script>

<template>
    <Head title="Đối soát tồn kho" />
    <p class="mb-2 text-sm text-slate-500">
        <Link :href="backUrl" class="text-indigo-600 hover:underline">Tồn kho</Link>
    </p>
    <PageHeader :title="title" subtitle="Chênh lệch expected − actual; dòng còn mở cần xử lý tay (kiểm kê hoặc duyệt áp số nguồn)." />

    <dl class="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-white p-5 text-sm sm:grid-cols-5">
        <div><dt class="text-slate-500">Nguồn</dt><dd class="mt-1 font-medium">{{ run.source_label }}</dd></div>
        <div><dt class="text-slate-500">Bắt đầu</dt><dd class="mt-1">{{ run.started_at }}</dd></div>
        <div><dt class="text-slate-500">Đã kiểm</dt><dd class="mt-1">{{ run.checked }}</dd></div>
        <div><dt class="text-slate-500">Chênh lệch</dt><dd class="mt-1">{{ run.discrepancies }}</dd></div>
        <div><dt class="text-slate-500">Đã xử lý / còn mở</dt><dd class="mt-1">{{ run.repaired }} / {{ run.open }}</dd></div>
    </dl>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Kho</th>
                <th class="px-4 py-2">SKU</th>
                <th class="px-4 py-2">Loại chênh lệch</th>
                <th class="px-4 py-2 text-right">Đúng ra</th>
                <th class="px-4 py-2 text-right">Hiện tại</th>
                <th class="px-4 py-2 text-right">Chênh</th>
                <th class="px-4 py-2">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="line in lines" :key="line.id" class="border-t border-slate-100 align-top">
                <td class="px-4 py-2 font-mono text-xs">{{ line.location }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ line.sku }}</td>
                <td class="px-4 py-2">{{ classificationLabels[line.classification] ?? line.classification }}</td>
                <td class="px-4 py-2 text-right">{{ line.expected }}</td>
                <td class="px-4 py-2 text-right">{{ line.actual }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ line.difference > 0 ? `+${line.difference}` : line.difference }}</td>
                <td class="px-4 py-2">
                    <span v-if="line.resolution" class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">
                        {{ resolutionLabels[line.resolution] ?? line.resolution }}
                    </span>
                    <span v-else class="rounded bg-red-50 px-2 py-0.5 text-xs text-red-700">Còn mở</span>
                    <div v-if="line.note" class="mt-1 max-w-md text-xs text-slate-500">{{ line.note }}</div>
                </td>
            </tr>
            <tr v-if="!lines.length">
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">Phiên này không có chênh lệch nào.</td>
            </tr>
        </tbody>
    </table>
</template>
