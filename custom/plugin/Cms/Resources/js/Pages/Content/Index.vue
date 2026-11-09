<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FlashMessage from '@admin/Components/FlashMessage.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { kindLabels, statusLabels, type Kind } from './labels';

const props = defineProps<{
    kind: Kind;
    search: string;
    items: {
        data: Array<{
            id: number;
            title: string;
            slug: string;
            status: string;
            published_at: string | null;
            updated_at: string | null;
            url: string | null;
        }>;
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
    };
    can: { manage: boolean };
    urls: { pages: string; posts: string; index: string; create: string; categories?: string; banners?: string };
}>();

const q = ref(props.search);
const perPageOptions = [20, 50, 100];
const columns = [
    { key: 'title', label: 'Tiêu đề' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'date', label: props.kind === 'posts' ? 'Ngày đăng' : 'Cập nhật', class: 'text-slate-600' },
    { key: 'actions', label: '', align: 'right' as const },
];

function submitSearch(page = 1, pageSize = props.items.per_page): void {
    router.get(props.urls.index, { q: q.value || undefined, page: page > 1 ? page : undefined, per_page: pageSize }, { preserveState: true });
}

function changePageSize(pageSize: number): void {
    submitSearch(1, pageSize);
}
</script>

<template>
    <Head :title="`Nội dung — ${kindLabels[kind].plural}`" />
    <PageHeader title="Nội dung" subtitle="Trang (giới thiệu, chính sách…) và tin tức của cửa hàng. Soạn bằng Markdown.">
        <Link v-if="kind === 'posts' && urls.categories" :href="urls.categories" :class="secondaryButton">Chuyên mục</Link>
        <Link v-if="urls.banners" :href="urls.banners" :class="secondaryButton">Banner danh mục</Link>
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">Thêm {{ kindLabels[kind].singular }}</Link>
    </PageHeader>
    <FlashMessage />

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <Link v-for="(labels, key) in kindLabels" :key="key" :href="urls[key]" :class="[secondaryButton, kind === key ? 'border-indigo-500 text-red-700' : '']">
            {{ labels.plural }}
        </Link>
        <form class="ml-auto flex gap-2" @submit.prevent="submitSearch()">
            <input v-model="q" type="search" placeholder="Tìm theo tiêu đề" :class="[inputClass, 'w-56']" />
            <button type="submit" :class="secondaryButton">Tìm</button>
        </form>
    </div>

    <DataTable :columns="columns" :rows="items.data" :row-label="(item) => item.title" :empty-text="`Chưa có ${kindLabels[kind].singular} nào.`">
        <template #cell-title="{ row }">
            <Link :href="`${urls.index}/${row.id}`" class="font-medium text-red-700 hover:underline">{{ row.title }}</Link>
            <div class="text-xs text-slate-400">/{{ kind === 'pages' ? 'trang' : 'tin-tuc' }}/{{ row.slug }}</div>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="statusLabels[row.status]?.label ?? row.status" :tone="statusLabels[row.status]?.tone ?? 'neutral'" />
        </template>
        <template #cell-date="{ row }">{{ (kind === 'posts' ? row.published_at : row.updated_at) ?? '—' }}</template>
        <template #cell-actions="{ row }">
            <a v-if="row.url" :href="row.url" target="_blank" rel="noopener" class="text-xs text-indigo-600 hover:underline">Xem</a>
        </template>
    </DataTable>

    <Pagination :current="items.current_page" :last="items.last_page" :from="items.from" :to="items.to" :total="items.total" :per-page="items.per_page" :per-page-options="perPageOptions" @change="submitSearch" @per-page-change="changePageSize" />
</template>
