<script setup lang="ts">
import FlashMessage from '@admin/Components/FlashMessage.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    charts: Array<{ id: number; name: string; unit: string; is_default: boolean; active: boolean; assignments: Record<string, number> }>;
    can: { manage: boolean };
    urls: { create: string; base: string };
}>();

const targetLabels: Record<string, string> = { product: 'sản phẩm', category: 'danh mục', brand: 'thương hiệu' };
</script>

<template>
    <Head title="Bảng size" />
    <PageHeader title="Bảng size" subtitle="Gán bảng ở form sản phẩm, danh mục hoặc thương hiệu. Sản phẩm chưa có bảng nào dùng bảng mặc định.">
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">Thêm bảng size</Link>
    </PageHeader>
    <FlashMessage />

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Tên</th>
                    <th class="px-4 py-2">Đơn vị</th>
                    <th class="px-4 py-2">Áp dụng cho</th>
                    <th class="px-4 py-2">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="chart in charts" :key="chart.id" class="border-t border-slate-100">
                    <td class="px-4 py-2">
                        <Link :href="`${urls.base}/${chart.id}`" class="font-medium text-indigo-600">{{ chart.name }}</Link>
                        <span v-if="chart.is_default" class="ml-2 rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700">Mặc định</span>
                    </td>
                    <td class="px-4 py-2">{{ chart.unit }}</td>
                    <td class="px-4 py-2">
                        <span v-if="!Object.keys(chart.assignments).length" class="text-slate-400">—</span>
                        <span v-for="(count, target) in chart.assignments" :key="target" class="mr-2">{{ count }} {{ targetLabels[target] ?? target }}</span>
                    </td>
                    <td class="px-4 py-2">{{ chart.active ? 'Đang bật' : 'Tắt' }}</td>
                </tr>
                <tr v-if="!charts.length">
                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có bảng size.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
