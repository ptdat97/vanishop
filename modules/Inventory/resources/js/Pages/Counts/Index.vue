<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { useAdminForm } from '@admin/forms';
import { useListQuery } from '@admin/list';
import { labelOptions, recordOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Count = { id: number; number: string; location: string | null; note: string | null; lines: number; status: string; created_at: string; applied_at: string | null };

/** Kiểm kê nhanh (A7): danh sách phiên + mở phiên mới. */
const props = defineProps<{
    filters: { status: string | null };
    statusLabels: Record<string, string>;
    counts: { data: Count[]; current: number; last: number; from: number | null; to: number | null; total: number; per_page: number };
    locations: Array<{ id: number; label: string }>;
    canManage: boolean;
    urls: { base: string; store: string; stock: string };
}>();

const status = ref(props.filters.status ?? '');
const list = useListQuery(() => props.urls.base);
const { form, save } = useAdminForm({ location_id: props.locations[0]?.id ?? null, note: '' }, { leaveGuard: false });
const columns = [
    { key: 'number', label: 'Phiên', class: 'font-mono' },
    { key: 'location', label: 'Kho' },
    { key: 'note', label: 'Ghi chú' },
    { key: 'lines', label: 'SKU đã đếm', align: 'right' as const, class: 'tabular-nums' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'date', label: 'Ngày', class: 'whitespace-nowrap tabular-nums' },
];

function filter(page = 1): void {
    list.visit({ status: status.value, page });
}
</script>

<template>
    <Head title="Kiểm kê" />
    <PageHeader title="Kiểm kê" subtitle="Đếm trong lúc vẫn bán: chỉ phần chênh giữa số đếm và tồn lúc đếm được ghi khi áp dụng.">
        <Link :href="urls.stock" :class="secondaryButton">Tồn kho</Link>
    </PageHeader>

    <form v-if="canManage && locations.length" class="mb-6 flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-white p-4 text-sm" data-start-count @submit.prevent="save('post', urls.store)">
        <label class="w-64"><span class="mb-1 block font-medium">Kho</span><Select v-model="form.location_id" :options="recordOptions(locations)" aria-label="Kho kiểm kê" /></label>
        <label class="min-w-64 flex-1"><span class="mb-1 block font-medium">Ghi chú (khu vực, kệ…)</span><input v-model="form.note" :class="inputClass" maxlength="500" /></label>
        <button type="submit" :class="primaryButton" :disabled="form.processing">Bắt đầu kiểm kê</button>
    </form>

    <form class="mb-4 flex gap-2" @submit.prevent="filter()">
        <div class="w-44"><Select v-model="status" :options="labelOptions(statusLabels)" empty-label="Mọi trạng thái" aria-label="Trạng thái" /></div>
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>
    <DataTable :columns="columns" :rows="counts.data" :loading="list.loading.value" :row-label="(row) => row.number" :row-click="(row) => router.visit(`${urls.base}/${row.id}`)" empty-text="Chưa có phiên kiểm kê nào.">
        <template #cell-note="{ row }">{{ row.note ?? '—' }}</template>
        <template #cell-status="{ row }">
            <StatusBadge :label="statusLabels[row.status] ?? row.status" :tone="row.status === 'applied' ? 'success' : row.status === 'counting' ? 'warning' : 'neutral'" />
        </template>
        <template #cell-date="{ row }">{{ row.applied_at ?? row.created_at }}</template>
    </DataTable>
    <Pagination :current="counts.current" :last="counts.last" :from="counts.from" :to="counts.to" :total="counts.total" :per-page="counts.per_page" @change="filter" />
</template>
