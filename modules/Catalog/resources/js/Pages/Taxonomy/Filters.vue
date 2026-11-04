<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem } from '../../types';

interface Facet {
    key: string;
    type: 'brand' | 'color' | 'attribute';
    label: string;
    displays: string[];
    enabled: boolean;
    display: string;
}

/** Bộ lọc của trang danh sách (§3.5-1): bật/tắt, thứ tự (nút ↑ ↓), kiểu hiển thị. Thuộc tính lấy từ "dùng để lọc". */
const props = defineProps<{ nav: NavItem[]; facets: Facet[]; displayLabels: Record<string, string>; canManage: boolean }>();

const form = useForm<{ facets: Facet[] }>({ facets: props.facets.map((facet) => ({ ...facet })) });
const errors = computed(() => form.errors as Record<string, string | undefined>);
const baseUrl = computed(() => props.nav.find((item) => item.key === 'filters')?.url ?? '');
const displayOptions = (facet: Facet) => facet.displays.map((value) => ({ value, label: props.displayLabels[value] ?? value }));
const kindLabel: Record<Facet['type'], string> = { brand: 'Thương hiệu', color: 'Nhóm màu', attribute: 'Thuộc tính' };

function move(index: number, delta: number): void {
    const [facet] = form.facets.splice(index, 1);
    form.facets.splice(index + delta, 0, facet);
}

function save(): void {
    form.transform((data) => ({ facets: data.facets.map(({ key, enabled, display }) => ({ key, enabled, display })) })).put(baseUrl.value, { preserveScroll: true });
}
</script>

<template>
    <Head title="Bộ lọc" />
    <CatalogTabs :nav="nav" active="filters" />
    <PageHeader title="Bộ lọc" subtitle="Bộ lọc ở trang danh mục, thương hiệu, tìm kiếm. Bộ lọc không có giá trị nào trong kết quả tự ẩn.">
        <button v-if="canManage" type="button" :class="primaryButton" :disabled="form.processing" @click="save">Lưu</button>
    </PageHeader>

    <p class="mb-4 text-sm text-slate-500">Thêm bộ lọc theo thuộc tính: bật "Dùng để lọc" ở thuộc tính loại spec. Lọc theo giá và size chưa hỗ trợ.</p>

    <ol class="space-y-2">
        <li v-for="(facet, index) in form.facets" :key="facet.key" class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-white p-3" :data-facet="facet.key">
            <label class="flex min-w-48 flex-1 items-center gap-2 text-sm">
                <input v-model="facet.enabled" type="checkbox" :disabled="!canManage" />
                <span class="font-medium">{{ facet.label }}</span>
                <span class="text-xs text-slate-500">{{ kindLabel[facet.type] }}</span>
            </label>
            <div class="w-40">
                <Select v-model="facet.display" :options="displayOptions(facet)" :disabled="!canManage || facet.displays.length < 2" aria-label="Kiểu hiển thị" />
            </div>
            <div v-if="canManage" class="flex gap-1">
                <button type="button" :class="secondaryButton" :disabled="index === 0" aria-label="Lên" @click="move(index, -1)">↑</button>
                <button type="button" :class="secondaryButton" :disabled="index === form.facets.length - 1" aria-label="Xuống" @click="move(index, 1)">↓</button>
            </div>
            <p v-if="errors[`facets.${index}.key`] || errors[`facets.${index}.display`]" class="w-full text-sm text-red-600">{{ errors[`facets.${index}.key`] ?? errors[`facets.${index}.display`] }}</p>
        </li>
    </ol>
</template>
