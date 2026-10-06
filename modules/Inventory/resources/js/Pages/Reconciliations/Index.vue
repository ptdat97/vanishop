<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

type ReconciliationRun = {
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

const props = defineProps<{
    baseUrl: string;
    runs: ReconciliationRun[];
}>();
</script>

<template>
    <Head title="Đối soát tồn kho" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho</p>
    <PageHeader
        title="Đối soát tồn kho"
        subtitle="Kết quả của vani:inventory:verify (chạy hằng ngày) và vani:inventory:reconcile (nguồn ngoài). Chênh lệch còn mở phải xử lý tay."
    />

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Nguồn</th>
                <th class="px-4 py-2">Bắt đầu</th>
                <th class="px-4 py-2 text-right">Đã kiểm</th>
                <th class="px-4 py-2 text-right">Chênh lệch</th>
                <th class="px-4 py-2 text-right">Đã xử lý</th>
                <th class="px-4 py-2 text-right">Còn mở</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="run in runs" :key="run.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <Link :href="`${baseUrl}/${run.id}`" class="text-indigo-600 hover:underline">{{ run.source_label }}</Link>
                </td>
                <td class="px-4 py-2 text-slate-500">{{ run.started_at }}</td>
                <td class="px-4 py-2 text-right">{{ run.checked }}</td>
                <td class="px-4 py-2 text-right">{{ run.discrepancies }}</td>
                <td class="px-4 py-2 text-right">{{ run.repaired }}</td>
                <td :class="run.open > 0 ? 'px-4 py-2 text-right font-medium text-red-600' : 'px-4 py-2 text-right text-green-600'">
                    {{ run.open }}
                </td>
            </tr>
            <tr v-if="!runs.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Chưa có phiên đối soát nào.</td>
            </tr>
        </tbody>
    </table>
</template>
