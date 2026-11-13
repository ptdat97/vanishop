<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import ExtensionFormSections from '@admin/Components/Extensions/ExtensionFormSections.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { initialExtensionValues, type ExtensionSection } from '@admin/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem } from '../../types';

interface BrandRow {
    id: number;
    code: string;
    slug: string;
    name: string;
    description: string | null;
    logo_media_id: number | null;
    status: 'active' | 'hidden';
    position: number;
    lock_version: number;
    styles_count: number;
}

// extensionSections: phần form của plugin — khoá `new` cho form thêm, id thương hiệu cho form sửa.
const props = defineProps<{ nav: NavItem[]; labels: CatalogLabels; brands: BrandRow[]; canManage: boolean; extensionSections: Record<string, ExtensionSection[]> }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'brands')?.url ?? '');
const editingId = ref<number | null>(null);
const columns = [
    { key: 'name', label: 'Tên', class: 'font-medium' },
    { key: 'code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'slug', label: 'Slug', class: 'font-mono text-xs' },
    { key: 'styles_count', label: 'Sản phẩm' },
    { key: 'status', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const },
];

const sectionsFor = (id: number | null): ExtensionSection[] => props.extensionSections[id === null ? 'new' : String(id)] ?? [];
const sections = computed(() => sectionsFor(editingId.value));
const empty = () => ({ code: '', slug: '', name: '', description: '' as string | null, logo_media_id: null as number | null, status: 'active' as 'active' | 'hidden', position: 0, lock_version: null as number | null, extensions: initialExtensionValues(sectionsFor(null)) });
const form = useForm(empty());
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(brand: BrandRow): void {
    editingId.value = brand.id;
    form.defaults({ code: brand.code, slug: brand.slug, name: brand.name, description: brand.description, logo_media_id: brand.logo_media_id, status: brand.status, position: brand.position, lock_version: brand.lock_version, extensions: initialExtensionValues(sectionsFor(brand.id)) });
    form.reset();
}

function cancel(): void {
    editingId.value = null;
    form.defaults(empty());
    form.reset();
    form.clearErrors();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: cancel };
    if (editingId.value) {
        form.put(`${baseUrl.value}/${editingId.value}`, options);
    } else {
        form.post(baseUrl.value, options);
    }
}

async function destroy(brand: BrandRow): Promise<void> {
    if ((await confirmAction({ title: `Xoá thương hiệu ${brand.name}?`, confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${brand.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Thương hiệu" />
    <CatalogTabs :nav="nav" active="brands" />
    <PageHeader title="Thương hiệu" subtitle="Thương hiệu là thuộc tính của sản phẩm: trang thương hiệu, bộ lọc, khuyến mãi và báo cáo theo brand." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="brands" :row-label="(brand) => brand.name" empty-text="Chưa có thương hiệu nào.">
                <template #cell-status="{ row }"><StatusBadge :label="labels.visibility[row.status] ?? row.status" :tone="row.status === 'active' ? 'success' : 'neutral'" /></template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">Sửa</button>
                        <button v-if="row.styles_count === 0" type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">Xoá</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa thương hiệu' : 'Thêm thương hiệu' }}</h2>
            <p v-if="errors.brand" class="text-xs text-red-600">{{ errors.brand }}</p>
            <FormField label="Tên" :error="errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
            <FormField label="Mã" hint="Chữ in hoa, ví dụ LM" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField label="Slug" hint="Dùng cho URL /thuong-hieu/{slug}" :error="errors.slug"><input v-model="form.slug" :class="inputClass" /></FormField>
            <FormField label="Mô tả" :error="errors.description"><textarea v-model="form.description" rows="3" :class="inputClass" /></FormField>
            <FormField label="Logo" hint="Chọn từ Thư viện ảnh; hiện ở trang thương hiệu và khối Thương hiệu." :error="errors.logo_media_id"><MediaField v-model="form.logo_media_id" label="logo" /></FormField>
            <FormField label="Trạng thái" hint="Ẩn: ẩn trang thương hiệu, sản phẩm vẫn bán." :error="errors.status">
                <Select v-model="form.status" :options="labelOptions(labels.visibility) as Array<{ value: 'active' | 'hidden'; label: string }>" />
            </FormField>
            <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <ExtensionFormSections v-model="form.extensions" :sections="sections" :errors="errors" />
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">Huỷ</button>
            </div>
        </form>
    </div>
</template>
