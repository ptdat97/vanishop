<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { inputClass, primaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Row {
    id: number;
    name: string | null;
    slug: string;
    images_count: number;
    active: boolean;
    position: number;
    cover_url: string | null;
}

/** Admin → Nội dung → Lookbook (vani.lookbook): danh sách, tìm theo tên/đường dẫn. */
const props = defineProps<{
    search: string;
    lookbooks: { data: Row[]; current_page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    urls: { index: string; create: string };
    can: { manage: boolean };
}>();

const q = ref(props.search);
const perPageOptions = [20, 50, 100];
const columns = [
    { key: 'cover', label: '', class: 'w-16' },
    { key: 'name', label: 'Lookbook', class: 'font-medium' },
    { key: 'images_count', label: 'Số ảnh', align: 'right' as const },
    { key: 'position', label: 'Thứ tự', align: 'right' as const },
    { key: 'active', label: 'Trạng thái' },
];

function search(page = 1, pageSize = props.lookbooks.per_page): void {
    router.get(props.urls.index, { q: q.value || undefined, page: page > 1 ? page : undefined, per_page: pageSize }, { preserveState: true, replace: true });
}

function changePageSize(pageSize: number): void {
    search(1, pageSize);
}
</script>

<template>
    <Head title="Lookbook" />
    <PageHeader title="Lookbook" subtitle="Bộ ảnh theo mùa/chủ đề ở /lookbook.">
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">Thêm lookbook</Link>
    </PageHeader>
    <form class="mb-4 max-w-sm" @submit.prevent="search()">
        <input v-model="q" :class="inputClass" placeholder="Tìm theo tên hoặc đường dẫn…" aria-label="Tìm lookbook" />
    </form>
    <DataTable :columns="columns" :rows="lookbooks.data" :row-label="(row) => row.name ?? row.slug" empty-text="Chưa có lookbook.">
        <template #cell-cover="{ row }"><img v-if="row.cover_url" :src="row.cover_url" alt="" class="h-12 w-12 rounded object-cover" /></template>
        <template #cell-name="{ row }">
            <Link :href="`${urls.index}/${row.id}`" class="hover:underline">{{ row.name ?? row.slug }}</Link>
            <div class="text-xs text-slate-400">/lookbook/{{ row.slug }}</div>
        </template>
        <template #cell-active="{ row }"><StatusBadge :label="row.active ? 'Đang bật' : 'Tắt'" :tone="row.active ? 'success' : 'neutral'" /></template>
    </DataTable>
    <Pagination :current="lookbooks.current_page" :last="lookbooks.last_page" :from="lookbooks.from" :to="lookbooks.to" :total="lookbooks.total" :per-page="lookbooks.per_page" :per-page-options="perPageOptions" @change="search" @per-page-change="changePageSize" />
</template>
