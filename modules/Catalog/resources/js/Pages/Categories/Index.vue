<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { primaryButton } from '@admin/styles';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import CategoryTreeRow from '../../Components/CategoryTreeRow.vue';
import type { BrandRef, CategoryNode, NavItem } from '../../types';

const props = defineProps<{ brand: BrandRef; nav: NavItem[]; tree: CategoryNode[]; canManage: boolean }>();
const page = usePage();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'categories')?.url ?? page.url);
</script>

<template>
    <Head :title="`Danh mục · ${brand.name}`" />
    <CatalogTabs :brand="brand" :nav="nav" active="categories" />
    <PageHeader title="Danh mục">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm danh mục</Link>
    </PageHeader>
    <div class="rounded-lg border border-slate-200 bg-white px-4">
        <ul v-if="tree.length">
            <CategoryTreeRow v-for="node in tree" :key="node.id" :node="node" :base-url="baseUrl" :can-manage="canManage" />
        </ul>
        <p v-else class="py-6 text-center text-sm text-slate-500">Chưa có danh mục nào.</p>
    </div>
</template>
