<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    brand: { name: string; slug: string };
    backUrl: string;
    variant: { sku: string; style_code: string };
    movements: Array<{
        id: number;
        at: string | null;
        location: string;
        type: string;
        on_hand_delta: number;
        reserved_delta: number;
        on_hand_after: number;
        reserved_after: number;
        reason: string | null;
        reference: string | null;
        actor: string | null;
    }>;
}>();

const signed = (value: number): string => (value > 0 ? `+${value}` : value === 0 ? '—' : String(value));
</script>

<template>
    <Head :title="`Lịch sử tồn · ${variant.sku}`" />
    <p class="mb-2 text-sm text-slate-500">Tồn kho · <span class="font-medium text-slate-700">{{ brand.name }}</span></p>
    <PageHeader :title="`Lịch sử tồn: ${variant.sku}`" subtitle="Sổ cái chỉ ghi thêm; 100 dòng gần nhất.">
        <Link :href="backUrl" :class="secondaryButton">Quay lại</Link>
    </PageHeader>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Thời điểm</th>
                <th class="px-4 py-2">Location</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Tồn</th>
                <th class="px-4 py-2">Giữ</th>
                <th class="px-4 py-2">Lý do / tham chiếu</th>
                <th class="px-4 py-2">Người thực hiện</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="movement in movements" :key="movement.id" class="border-t border-slate-100">
                <td class="px-4 py-2 text-xs">{{ movement.at }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ movement.location }}</td>
                <td class="px-4 py-2">{{ movement.type }}</td>
                <td class="px-4 py-2">{{ signed(movement.on_hand_delta) }} → {{ movement.on_hand_after }}</td>
                <td class="px-4 py-2">{{ signed(movement.reserved_delta) }} → {{ movement.reserved_after }}</td>
                <td class="px-4 py-2 text-xs">{{ movement.reason ?? '' }} <span class="font-mono text-slate-400">{{ movement.reference ?? '' }}</span></td>
                <td class="px-4 py-2 text-xs">{{ movement.actor ?? '—' }}</td>
            </tr>
            <tr v-if="!movements.length">
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">Chưa có biến động nào.</td>
            </tr>
        </tbody>
    </table>
</template>
