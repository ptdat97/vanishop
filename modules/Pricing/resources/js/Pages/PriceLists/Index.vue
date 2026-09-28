<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    brand: { name: string; slug: string };
    baseUrl: string;
    priceLists: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        priority: number;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
        prices_count: number;
        channels_count: number;
    }>;
    canManage: boolean;
}>();

const typeLabels: Record<string, string> = { base: 'Giá niêm yết', sale: 'Khuyến mãi', member: 'Thành viên' };
</script>

<template>
    <Head :title="`Bảng giá · ${brand.name}`" />
    <p class="mb-2 text-sm text-slate-500">Giá bán · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
    <PageHeader title="Bảng giá" subtitle="Bảng giá priority cao hơn thắng; giá niêm yết (base) được dùng làm giá gốc khi đang khuyến mãi.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm bảng giá</Link>
    </PageHeader>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Bảng giá</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Priority</th>
                <th class="px-4 py-2">Hiệu lực</th>
                <th class="px-4 py-2">Kênh</th>
                <th class="px-4 py-2">Số giá</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="list in priceLists" :key="list.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <div class="font-medium">{{ list.name }}</div>
                    <div class="font-mono text-xs text-slate-400">{{ list.code }}<span v-if="list.status !== 'active'"> · tắt</span></div>
                </td>
                <td class="px-4 py-2">{{ typeLabels[list.type] ?? list.type }}</td>
                <td class="px-4 py-2">{{ list.priority }}</td>
                <td class="px-4 py-2 text-xs">{{ list.starts_at ?? '—' }} → {{ list.ends_at ?? '—' }}</td>
                <td class="px-4 py-2">{{ list.channels_count }}</td>
                <td class="px-4 py-2">{{ list.prices_count }}</td>
                <td class="px-4 py-2 text-right">
                    <Link :href="`${baseUrl}/${list.id}/prices`" class="text-indigo-600 hover:underline">Giá</Link>
                    <Link v-if="canManage" :href="`${baseUrl}/${list.id}/edit`" class="ml-3 text-indigo-600 hover:underline">Sửa</Link>
                </td>
            </tr>
            <tr v-if="!priceLists.length">
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">Chưa có bảng giá nào.</td>
            </tr>
        </tbody>
    </table>
</template>
