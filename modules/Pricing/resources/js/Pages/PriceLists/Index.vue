<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { stateTone } from '../../priceListState';

const props = defineProps<{
    baseUrl: string;
    labels: { price_list_type: Record<string, string>; price_list_state: Record<string, string> };
    priceLists: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        customer_group: string | null;
        priority: number;
        status: string;
        state: string;
        starts_at: string | null;
        ends_at: string | null;
        prices_count: number;
    }>;
    canManage: boolean;
    /** Bảng loại niêm yết ngoài bảng chính (dữ liệu cũ) — cần đổi sang Sale/Thành viên hoặc xoá. */
    extraBaseLists: Array<{ id: number; name: string; code: string }>;
}>();

const columns = [
    { key: 'name', label: 'Bảng giá' },
    { key: 'state', label: 'Trạng thái' },
    { key: 'type', label: 'Loại' },
    { key: 'period', label: 'Hiệu lực', class: 'text-xs' },
    { key: 'prices_count', label: 'Số SKU' },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head title="Bảng giá" />
    <p class="mb-2 text-sm text-slate-500">Giá bán</p>
    <PageHeader title="Bảng giá" subtitle="Bấm vào bảng giá để xem và sửa giá. Bảng giá ưu tiên cao hơn thắng; giá niêm yết làm giá gạch khi đang giảm.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm bảng giá</Link>
    </PageHeader>
    <div v-if="extraBaseLists.length" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" data-extra-base>
        Cửa hàng chỉ dùng một bảng giá niêm yết (mã <span class="font-mono">base</span>). Các bảng sau đang là loại niêm yết thừa — mở và chọn
        <strong>Sale</strong> hoặc <strong>Thành viên</strong>, hoặc xoá nếu không dùng:
        <span v-for="(list, index) in extraBaseLists" :key="list.id"><template v-if="index">, </template><Link :href="`${baseUrl}/${list.id}/edit`" class="font-medium underline">{{ list.name }}</Link></span>.
    </div>
    <DataTable :columns="columns" :rows="priceLists" :row-label="(list) => list.name" :row-click="(list) => router.visit(`${props.baseUrl}/${list.id}/prices`)" empty-text="Chưa có bảng giá nào.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}</div>
        </template>
        <template #cell-state="{ row }"><StatusBadge :label="labels.price_list_state[row.state] ?? row.state" :tone="stateTone(row.state)" /></template>
        <template #cell-type="{ row }">
            {{ labels.price_list_type[row.type] ?? row.type }}
            <div v-if="row.customer_group" class="text-xs text-slate-500">Nhóm: {{ row.customer_group }}</div>
        </template>
        <template #cell-period="{ row }">
            <template v-if="row.starts_at || row.ends_at">{{ row.starts_at ?? '—' }} → {{ row.ends_at ?? '—' }}</template>
            <span v-else class="text-slate-400">Không giới hạn</span>
        </template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline" @click.stop>Thông tin</Link>
        </template>
    </DataTable>
</template>
