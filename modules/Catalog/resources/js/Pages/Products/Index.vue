<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { BrandRef, NavItem } from '../../types';

const props = defineProps<{
    brand: BrandRef;
    nav: NavItem[];
    filters: { q: string; status: string | null };
    products: {
        data: Array<{ id: number; style_code: string; name: string | null; status: string; colors: number; image_url: string | null }>;
        links: { prev: string | null; next: string | null };
        total: number;
    };
    canManage: boolean;
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'products')?.url ?? '');
const q = ref(props.filters.q);
const status = ref(props.filters.status ?? '');
const statusLabels: Record<string, string> = { draft: 'Nháp', active: 'Đang bán', archived: 'Lưu trữ' };

function search(): void {
    router.get(baseUrl.value, { q: q.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head :title="`Sản phẩm · ${brand.name}`" />
    <CatalogTabs :brand="brand" :nav="nav" active="products" />
    <PageHeader title="Sản phẩm" :subtitle="`${products.total} sản phẩm`">
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm sản phẩm</Link>
    </PageHeader>

    <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="search">
        <input v-model="q" :class="inputClass" class="max-w-xs" type="search" placeholder="Tìm theo tên hoặc mã (không cần dấu)" />
        <select v-model="status" :class="inputClass" class="max-w-40">
            <option value="">Mọi trạng thái</option>
            <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
        </select>
        <button type="submit" :class="secondaryButton">Lọc</button>
    </form>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="w-16 px-4 py-2" />
                <th class="px-4 py-2">Sản phẩm</th>
                <th class="px-4 py-2">Mã</th>
                <th class="px-4 py-2">Màu</th>
                <th class="px-4 py-2">Trạng thái</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="product in products.data" :key="product.id" class="border-t border-slate-100">
                <td class="px-4 py-2">
                    <img v-if="product.image_url" :src="product.image_url" alt="" class="h-12 w-10 rounded object-cover" />
                    <div v-else class="h-12 w-10 rounded bg-slate-100" />
                </td>
                <td class="px-4 py-2 font-medium">{{ product.name }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ product.style_code }}</td>
                <td class="px-4 py-2">{{ product.colors }}</td>
                <td class="px-4 py-2">{{ statusLabels[product.status] ?? product.status }}</td>
                <td class="px-4 py-2 text-right">
                    <Link v-if="canManage" :href="`${baseUrl}/${product.id}/edit`" class="text-indigo-600 hover:underline">Sửa</Link>
                </td>
            </tr>
            <tr v-if="!products.data.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Không có sản phẩm nào.</td>
            </tr>
        </tbody>
    </table>

    <div class="mt-4 flex justify-between">
        <Link v-if="products.links.prev" :href="products.links.prev" :class="secondaryButton">← Trước</Link>
        <span v-else />
        <Link v-if="products.links.next" :href="products.links.next" :class="secondaryButton">Sau →</Link>
    </div>
</template>
