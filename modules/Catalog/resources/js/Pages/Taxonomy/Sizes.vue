<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions } from '@admin/options';
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
const columns = [
    { key: 'size_system', label: 'Hệ size' },
    { key: 'code', label: 'Mã', class: 'font-medium' },
    { key: 'sort_order', label: 'Thứ tự' },
    { key: 'actions', label: '', align: 'right' as const },
];

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

async function destroy(size: SizeRow): Promise<void> {
    if ((await confirmAction({ title: `Xoá size ${size.code}?`, confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${size.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Size" />
    <CatalogTabs :nav="nav" active="sizes" />
    <PageHeader title="Size" subtitle="Thứ tự sắp xếp quyết định cách hiển thị: XS < S < M < L…" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="sizes" :row-label="(size) => size.code" empty-text="Chưa có size nào.">
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">Sửa</button>
                        <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">Xoá</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa size' : 'Thêm size' }}</h2>
            <FormField label="Hệ size" :error="form.errors.size_system">
                <Select v-model="form.size_system" :options="codeOptions(systems)" />
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
