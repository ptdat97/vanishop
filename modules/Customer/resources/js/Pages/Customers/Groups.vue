<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
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

function destroy(group: GroupRow): void {
    if (confirm(`Xoá nhóm ${group.name}?`)) {
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
        <table class="w-full rounded-lg border border-slate-200 bg-white text-sm lg:col-span-2">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Tên</th>
                    <th class="px-4 py-2">Mã</th>
                    <th class="px-4 py-2">Khách</th>
                    <th />
                </tr>
            </thead>
            <tbody>
                <tr v-for="group in groups" :key="group.id" class="border-t border-slate-100">
                    <td class="px-4 py-2">
                        <div class="font-medium">{{ group.name }}</div>
                        <div v-if="group.description" class="text-xs text-slate-500">{{ group.description }}</div>
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">{{ group.code }}</td>
                    <td class="px-4 py-2">
                        <Link :href="`${customersUrl}?group=${group.id}`" class="text-indigo-600 hover:underline">{{ group.customers_count }}</Link>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <template v-if="canManage">
                            <button type="button" class="text-indigo-600 hover:underline" @click="edit(group)">Sửa</button>
                            <button v-if="group.customers_count === 0" type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(group)">Xoá</button>
                        </template>
                    </td>
                </tr>
                <tr v-if="!groups.length">
                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có nhóm khách.</td>
                </tr>
            </tbody>
        </table>

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
