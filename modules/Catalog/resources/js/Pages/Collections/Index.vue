<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    collections: Array<{ id: number; slug: string; name: string | null; status: string; styles_count: number }>;
    canManage: boolean;
}>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'collections')?.url ?? '');
</script>

<template>
    <Head title="Bộ sưu tập" />
    <CatalogTabs :nav="nav" active="collections" />
    <PageHeader title="Bộ sưu tập" subtitle="Tập hợp sản phẩm cho landing page, campaign.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm bộ sưu tập</Link>
    </PageHeader>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Tên</th>
                <th class="px-4 py-2">Slug</th>
                <th class="px-4 py-2">Sản phẩm</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="collection in collections" :key="collection.id" class="border-t border-slate-100">
                <td class="px-4 py-2 font-medium">{{ collection.name }}</td>
                <td class="px-4 py-2 text-slate-500">/{{ collection.slug }}</td>
                <td class="px-4 py-2">{{ collection.styles_count }}</td>
                <td class="px-4 py-2">{{ collection.status === 'active' ? 'Hiển thị' : 'Ẩn' }}</td>
                <td class="px-4 py-2 text-right">
                    <Link v-if="canManage" :href="`${baseUrl}/${collection.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
                </td>
            </tr>
            <tr v-if="!collections.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Chưa có bộ sưu tập nào.</td>
            </tr>
        </tbody>
    </table>
</template>
