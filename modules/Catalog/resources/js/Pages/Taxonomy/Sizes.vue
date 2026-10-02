<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem } from '../../types';

interface SizeRow {
    id: number;
    size_system: string;
    code: string;
    sort_order: number;
}

const props = defineProps<{ nav: NavItem[]; sizes: SizeRow[]; systems: string[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'sizes')?.url ?? '');
const editingId = ref<number | null>(null);
const form = useForm({ size_system: 'alpha', code: '', sort_order: 0 });

function edit(size: SizeRow): void {
    editingId.value = size.id;
    form.defaults({ size_system: size.size_system, code: size.code, sort_order: size.sort_order });
    form.reset();
}

function cancel(): void {
    editingId.value = null;
    form.defaults({ size_system: form.size_system, code: '', sort_order: form.sort_order + 10 });
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

function destroy(size: SizeRow): void {
    if (confirm(`Xoá size ${size.code}?`)) {
        router.delete(`${baseUrl.value}/${size.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Size" />
    <CatalogTabs :nav="nav" active="sizes" />
    <PageHeader title="Size" subtitle="Thứ tự sắp xếp quyết định cách hiển thị: XS < S < M < L…" />

    <div class="grid gap-6 lg:grid-cols-3">
        <table class="w-full rounded-lg border border-slate-200 bg-white text-sm lg:col-span-2">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Hệ size</th>
                    <th class="px-4 py-2">Mã</th>
                    <th class="px-4 py-2">Thứ tự</th>
                    <th />
                </tr>
            </thead>
            <tbody>
                <tr v-for="size in sizes" :key="size.id" class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ size.size_system }}</td>
                    <td class="px-4 py-2 font-medium">{{ size.code }}</td>
                    <td class="px-4 py-2">{{ size.sort_order }}</td>
                    <td class="px-4 py-2 text-right">
                        <template v-if="canManage">
                            <button type="button" class="text-indigo-600 hover:underline" @click="edit(size)">Sửa</button>
                            <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(size)">Xoá</button>
                        </template>
                    </td>
                </tr>
                <tr v-if="!sizes.length">
                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có size nào.</td>
                </tr>
            </tbody>
        </table>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa size' : 'Thêm size' }}</h2>
            <FormField label="Hệ size" :error="form.errors.size_system">
                <select v-model="form.size_system" :class="inputClass">
                    <option v-for="system in systems" :key="system" :value="system">{{ system }}</option>
                </select>
            </FormField>
            <FormField label="Mã" hint="Ví dụ: M, 28, 38.5" :error="form.errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField label="Thứ tự" :error="form.errors.sort_order"><input v-model.number="form.sort_order" :class="inputClass" type="number" min="0" /></FormField>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">Huỷ</button>
            </div>
        </form>
    </div>
</template>
