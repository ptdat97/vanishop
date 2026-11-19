<script setup lang="ts">
import { t } from '@admin/i18n';
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
    { key: 'name', label: t('customer::admin.customers_groups.name') },
    { key: 'code', label: t('customer::admin.customers_groups.code'), class: 'font-mono text-xs' },
    { key: 'customers_count', label: t('customer::admin.customers_groups.customer') },
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
    if ((await confirmAction({ title: t('customer::admin.customers_groups.delete_group_name', { name: group.name }), confirmLabel: t('customer::admin.customers_groups.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${group.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('customer::admin.customers_groups.customer_groups')" />
    <PageHeader :title="t('customer::admin.customers_groups.customer_groups')" :subtitle="t('customer::admin.customers_groups.each_customer_belongs_most_one')">
        <Link :href="customersUrl" :class="secondaryButton">{{ t('customer::admin.customers_groups.customers') }}</Link>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="groups" :row-label="(group) => group.name" :empty-text="t('customer::admin.customers_groups.no_customer_groups_yet')">
                <template #cell-name="{ row }">
                    <div class="font-medium">{{ row.name }}</div>
                    <div v-if="row.description" class="text-xs text-slate-500">{{ row.description }}</div>
                </template>
                <template #cell-customers_count="{ row }">
                    <Link :href="`${customersUrl}?group=${row.id}`" class="text-indigo-600 hover:underline">{{ row.customers_count }}</Link>
                </template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">{{ t('customer::admin.customers_groups.edit') }}</button>
                        <button v-if="row.customers_count === 0" type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">{{ t('customer::admin.customers_groups.delete') }}</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? t('customer::admin.customers_groups.edit_group') : t('customer::admin.customers_groups.add_group') }}</h2>
            <p v-if="errors.group" class="text-xs text-red-600">{{ errors.group }}</p>
            <FormField :label="t('customer::admin.customers_groups.name')" :error="errors.name"><input v-model="form.name" :class="inputClass" placeholder="vd. VIP" /></FormField>
            <FormField :label="t('customer::admin.customers_groups.code')" :hint="t('customer::admin.customers_groups.lowercase_vip')" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField :label="t('customer::admin.customers_groups.description')" :error="errors.description"><input v-model="form.description" :class="inputClass" /></FormField>
            <FormField :label="t('customer::admin.customers_groups.order')" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('customer::admin.customers_groups.save') }}</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">{{ t('customer::admin.customers_groups.cancel') }}</button>
            </div>
        </form>
    </div>
</template>
