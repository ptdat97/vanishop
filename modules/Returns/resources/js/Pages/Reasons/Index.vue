<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { useStoreLocales } from '@admin/locales';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Reason {
    id: number;
    code: string;
    names: Record<string, string>;
    position: number;
    is_active: boolean;
    store_fault: boolean;
    used: boolean;
}

/** Danh mục lý do đổi trả (§3.13-1). Lý do đã dùng: không đổi mã, không xoá — chỉ tắt. */
const props = defineProps<{ baseUrl: string; returnsUrl: string; reasons: Reason[]; canManage: boolean }>();

const { locales, defaultLocale } = useStoreLocales();
const editing = ref<Reason | null>(null);
const columns = [
    { key: 'name', label: t('returns::admin.reasons_index.name') },
    { key: 'code', label: t('returns::admin.reasons_index.code'), class: 'font-mono text-xs' },
    { key: 'store_fault', label: t('returns::admin.reasons_index.store_s_fault') },
    { key: 'is_active', label: t('returns::admin.reasons_index.status') },
    { key: 'actions', label: '', align: 'right' as const },
];
const blank = () => ({ code: '', position: props.reasons.length, is_active: true, store_fault: false, names: Object.fromEntries(locales.map((locale) => [locale.code, ''])) as Record<string, string> });
const form = useForm(blank());
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(reason: Reason): void {
    editing.value = reason;
    form.defaults({ code: reason.code, position: reason.position, is_active: reason.is_active, store_fault: reason.store_fault, names: Object.fromEntries(locales.map((locale) => [locale.code, reason.names[locale.code] ?? ''])) });
    form.reset();
}

function cancel(): void {
    editing.value = null;
    form.defaults(blank());
    form.reset();
    form.clearErrors();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: cancel };
    if (editing.value) {
        form.put(`${props.baseUrl}/${editing.value.id}`, options);
    } else {
        form.post(props.baseUrl, options);
    }
}

async function destroy(reason: Reason): Promise<void> {
    if ((await confirmAction({ title: t('returns::admin.reasons_index.delete_reason_code', { code: reason.code }), confirmLabel: t('returns::admin.reasons_index.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${reason.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('returns::admin.reasons_index.return_reasons')" />
    <p class="mb-2 text-sm text-slate-500">{{ t('returns::admin.reasons_index.returns') }}</p>
    <PageHeader :title="t('returns::admin.reasons_index.return_reasons')" :subtitle="t('returns::admin.reasons_index.customers_choose_one_when_sending')">
        <Link :href="returnsUrl" :class="secondaryButton">{{ t('returns::admin.reasons_index.return_requests') }}</Link>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="reasons" :row-label="(reason) => reason.code" :empty-text="t('returns::admin.reasons_index.no_reasons_yet')">
                <template #cell-name="{ row }">{{ row.names[defaultLocale] ?? row.code }}</template>
                <template #cell-store_fault="{ row }">{{ row.store_fault ? t('returns::admin.reasons_index.yes') : '—' }}</template>
                <template #cell-is_active="{ row }"><StatusBadge :label="row.is_active ? t('returns::admin.reasons_index.use') : t('returns::admin.reasons_index.off')" :tone="row.is_active ? 'success' : 'neutral'" /></template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">{{ t('returns::admin.reasons_index.edit') }}</button>
                        <button v-if="!row.used" type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">{{ t('returns::admin.reasons_index.delete') }}</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editing ? t('returns::admin.reasons_index.edit_reason') : t('returns::admin.reasons_index.add_reason') }}</h2>
            <FormField v-for="locale in locales" :key="locale.code" :label="t('returns::admin.reasons_index.name_label', { label: locale.label }) + (locale.code === defaultLocale ? ' *' : '')" :error="errors[`names.${locale.code}`]">
                <input v-model="form.names[locale.code]" :class="inputClass" />
            </FormField>
            <FormField :label="t('returns::admin.reasons_index.code')" :hint="t('returns::admin.reasons_index.lowercase_letters_digits_underscore_wrong')" :error="errors.code">
                <input v-model="form.code" :class="inputClass" :disabled="editing?.used" />
            </FormField>
            <FormField :label="t('returns::admin.reasons_index.order')" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> {{ t('returns::admin.reasons_index.selectable_customers') }}</label>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.store_fault" type="checkbox" /> {{ t('returns::admin.reasons_index.store_s_fault_used_return') }}</label>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('returns::admin.reasons_index.save') }}</button>
                <button v-if="editing" type="button" :class="secondaryButton" @click="cancel">{{ t('returns::admin.reasons_index.cancel') }}</button>
            </div>
        </form>
    </div>
</template>
