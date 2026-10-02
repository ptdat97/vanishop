<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem } from '../../types';

interface BrandRow {
    id: number;
    code: string;
    slug: string;
    name: string;
    description: string | null;
    status: 'active' | 'hidden';
    position: number;
    styles_count: number;
}

const props = defineProps<{ nav: NavItem[]; brands: BrandRow[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'brands')?.url ?? '');
const editingId = ref<number | null>(null);

const empty = () => ({ code: '', slug: '', name: '', description: '' as string | null, status: 'active' as 'active' | 'hidden', position: 0 });
const form = useForm(empty());
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(brand: BrandRow): void {
    editingId.value = brand.id;
    form.defaults({ code: brand.code, slug: brand.slug, name: brand.name, description: brand.description, status: brand.status, position: brand.position });
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

function destroy(brand: BrandRow): void {
    if (confirm(`Xoá thương hiệu ${brand.name}?`)) {
        router.delete(`${baseUrl.value}/${brand.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Thương hiệu" />
    <CatalogTabs :nav="nav" active="brands" />
    <PageHeader title="Thương hiệu" subtitle="Thương hiệu là thuộc tính của sản phẩm: trang thương hiệu, bộ lọc, khuyến mãi và báo cáo theo brand." />

    <div class="grid gap-6 lg:grid-cols-3">
        <table class="w-full rounded-lg border border-slate-200 bg-white text-sm lg:col-span-2">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Tên</th>
                    <th class="px-4 py-2">Mã</th>
                    <th class="px-4 py-2">Slug</th>
                    <th class="px-4 py-2">Sản phẩm</th>
                    <th class="px-4 py-2">Trạng thái</th>
                    <th />
                </tr>
            </thead>
            <tbody>
                <tr v-for="brand in brands" :key="brand.id" class="border-t border-slate-100">
                    <td class="px-4 py-2 font-medium">{{ brand.name }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ brand.code }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ brand.slug }}</td>
                    <td class="px-4 py-2">{{ brand.styles_count }}</td>
                    <td class="px-4 py-2">{{ brand.status === 'active' ? 'Đang hiện' : 'Ẩn' }}</td>
                    <td class="px-4 py-2 text-right">
                        <template v-if="canManage">
                            <button type="button" class="text-indigo-600 hover:underline" @click="edit(brand)">Sửa</button>
                            <button v-if="brand.styles_count === 0" type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(brand)">Xoá</button>
                        </template>
                    </td>
                </tr>
                <tr v-if="!brands.length">
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Chưa có thương hiệu nào.</td>
                </tr>
            </tbody>
        </table>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa thương hiệu' : 'Thêm thương hiệu' }}</h2>
            <p v-if="errors.brand" class="text-xs text-red-600">{{ errors.brand }}</p>
            <FormField label="Tên" :error="errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
            <FormField label="Mã" hint="Chữ in hoa, ví dụ LM" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField label="Slug" hint="Dùng cho URL /thuong-hieu/{slug}" :error="errors.slug"><input v-model="form.slug" :class="inputClass" /></FormField>
            <FormField label="Mô tả" :error="errors.description"><textarea v-model="form.description" rows="3" :class="inputClass" /></FormField>
            <FormField label="Trạng thái" :error="errors.status">
                <select v-model="form.status" :class="inputClass">
                    <option value="active">Đang hiện</option>
                    <option value="hidden">Ẩn (ẩn trang brand, sản phẩm vẫn bán)</option>
                </select>
            </FormField>
            <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">Huỷ</button>
            </div>
        </form>
    </div>
</template>
