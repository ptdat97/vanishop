<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    baseUrl: string;
    locations: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        priority: number;
        status: string;
        ships_online_orders: boolean;
        stock_authority: string;
    }>;
}>();

const typeLabels: Record<string, string> = { warehouse: 'Kho', store: 'Cửa hàng', virtual: 'Ảo' };
</script>

<template>
    <Head title="Kho & cửa hàng" />
    <PageHeader title="Kho & cửa hàng" subtitle="Location dùng chung cho các brand. Priority cao hơn được giữ hàng trước khi bán online.">
        <Link :href="`${baseUrl}/create`" :class="primaryButton">Thêm location</Link>
    </PageHeader>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Location</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Priority</th>
                <th class="px-4 py-2">Bán online</th>
                <th class="px-4 py-2">Quản lý tồn</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="location in locations" :key="location.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <div class="font-medium">{{ location.name }}</div>
                    <div class="font-mono text-xs text-slate-400">{{ location.code }}<span v-if="location.status !== 'active'"> · tắt</span></div>
                </td>
                <td class="px-4 py-2">{{ typeLabels[location.type] ?? location.type }}</td>
                <td class="px-4 py-2">{{ location.priority }}</td>
                <td class="px-4 py-2">{{ location.ships_online_orders ? 'Có' : '—' }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ location.stock_authority }}</td>
                <td class="px-4 py-2 text-right">
                    <Link :href="`${baseUrl}/${location.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
                </td>
            </tr>
            <tr v-if="!locations.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Chưa có location nào.</td>
            </tr>
        </tbody>
    </table>
</template>
