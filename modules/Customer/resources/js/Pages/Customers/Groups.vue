<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface GroupRow {
    id: number;
    code: string;
    name: string;
    description: string | null;
    position: number;
    customers_count: number;
}

const props = defineProps<{ baseUrl: string; customersUrl: string; groups: GroupRow[]; canManage: boolean }>();
const editingId = ref<number | null>(null);
const empty = () => ({ code: '', name: '', description: '' as string | null, position: 0 });
const form = useForm(empty());
const columns = [
    { key: 'name', label: 'Tên' },
    { key: 'code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'customers_count', label: 'Khách' },
    { key: 'actions', label: '', align: 'right' as const },
];
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(group: GroupRow): void {
    editingId.value = group.id;
    form.defaults({ code: group.code, name: group.name, description: group.description, position: group.position });
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
        form.put(`${props.baseUrl}/${editingId.value}`, options);
    } else {
        form.post(props.baseUrl, options);
    }
}

async function destroy(group: GroupRow): Promise<void> {
    if ((await confirmAction({ title: `Xoá nhóm ${group.name}?`, confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${group.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Nhóm khách" />
    <PageHeader title="Nhóm khách" subtitle="Mỗi khách thuộc tối đa một nhóm. Gắn bảng giá thành viên cho nhóm ở Bảng giá (loại member).">
        <Link :href="customersUrl" :class="secondaryButton">Khách hàng</Link>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="groups" :row-label="(group) => group.name" empty-text="Chưa có nhóm khách.">
                <template #cell-name="{ row }">
                    <div class="font-medium">{{ row.name }}</div>
                    <div v-if="row.description" class="text-xs text-slate-500">{{ row.description }}</div>
                </template>
                <template #cell-customers_count="{ row }">
                    <Link :href="`${customersUrl}?group=${row.id}`" class="text-indigo-600 hover:underline">{{ row.customers_count }}</Link>
                </template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">Sửa</button>
                        <button v-if="row.customers_count === 0" type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">Xoá</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa nhóm' : 'Thêm nhóm' }}</h2>
            <p v-if="errors.group" class="text-xs text-red-600">{{ errors.group }}</p>
            <FormField label="Tên" :error="errors.name"><input v-model="form.name" :class="inputClass" placeholder="vd. VIP" /></FormField>
            <FormField label="Mã" hint="chữ thường, vd. vip" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField label="Mô tả" :error="errors.description"><input v-model="form.description" :class="inputClass" /></FormField>
            <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">Huỷ</button>
            </div>
        </form>
    </div>
</template>
