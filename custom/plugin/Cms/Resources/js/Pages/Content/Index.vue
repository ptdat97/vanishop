<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { kindLabels as kindLabelsOf, statusLabels as statusLabelsOf, type Kind } from './labels';
const kindLabels = kindLabelsOf();
const statusLabels = statusLabelsOf();

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
    { key: 'title', label: t('vani-cms::admin.content_index.title') },
    { key: 'status', label: t('vani-cms::admin.content_index.status') },
    { key: 'date', label: props.kind === 'posts' ? t('vani-cms::admin.content_index.published') : t('vani-cms::admin.content_index.updated'), class: 'text-slate-600' },
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
    <Head :title="t('vani-cms::admin.content_index.content_plural', { plural: kindLabels[kind].plural })" />
    <PageHeader :title="t('vani-cms::admin.content_index.content')" :subtitle="t('vani-cms::admin.content_index.store_pages_about_policies_news')">
        <Link v-if="kind === 'posts' && urls.categories" :href="urls.categories" :class="secondaryButton">{{ t('vani-cms::admin.content_index.category') }}</Link>
        <Link v-if="urls.banners" :href="urls.banners" :class="secondaryButton">{{ t('vani-cms::admin.content_index.category_banners') }}</Link>
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton">{{ t('vani-cms::admin.content_index.add_singular', { singular: kindLabels[kind].singular }) }}</Link>
    </PageHeader>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <Link v-for="(labels, key) in kindLabels" :key="key" :href="urls[key]" :class="[secondaryButton, kind === key ? 'border-slate-900 text-slate-900' : '']">
            {{ labels.plural }}
        </Link>
        <form class="ml-auto flex gap-2" @submit.prevent="submitSearch()">
            <input v-model="q" type="search" :placeholder="t('vani-cms::admin.content_index.search_title')" :class="[inputClass, 'w-56']" />
            <button type="submit" :class="secondaryButton">{{ t('vani-cms::admin.content_index.search') }}</button>
        </form>
    </div>

    <DataTable :columns="columns" :rows="items.data" :row-label="(item) => item.title" :empty-text="t('vani-cms::admin.content_index.no_singular_yet', { singular: kindLabels[kind].singular })">
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
