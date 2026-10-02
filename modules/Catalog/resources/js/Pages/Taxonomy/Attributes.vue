<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    attributes: Array<{ id: number; code: string; name: string | null; kind: string; input_type: string; is_filterable: boolean; values_count: number }>;
    canManage: boolean;
}>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'attributes')?.url ?? '');
</script>

<template>
    <Head title="Thuộc tính" />
    <CatalogTabs :nav="nav" active="attributes" />
    <PageHeader title="Thuộc tính" subtitle="Chất liệu, form dáng, mùa… Loại internal không hiển thị ra storefront.">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm thuộc tính</Link>
    </PageHeader>
    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Tên</th>
                <th class="px-4 py-2">Mã</th>
                <th class="px-4 py-2">Loại</th>
                <th class="px-4 py-2">Kiểu nhập</th>
                <th class="px-4 py-2">Lọc</th>
                <th class="px-4 py-2">Giá trị</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="attribute in attributes" :key="attribute.id" class="border-t border-slate-100">
                <td class="px-4 py-2 font-medium">{{ attribute.name }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ attribute.code }}</td>
                <td class="px-4 py-2">{{ attribute.kind }}</td>
                <td class="px-4 py-2">{{ attribute.input_type }}</td>
                <td class="px-4 py-2">{{ attribute.is_filterable ? 'Có' : '—' }}</td>
                <td class="px-4 py-2">{{ attribute.values_count || '—' }}</td>
                <td class="px-4 py-2 text-right">
                    <Link v-if="canManage" :href="`${baseUrl}/${attribute.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
                </td>
            </tr>
            <tr v-if="!attributes.length">
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">Chưa có thuộc tính nào.</td>
            </tr>
        </tbody>
    </table>
</template>
