<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem, Translations } from '../../types';

interface LinkTypeRow {
    id: number;
    code: string;
    is_active: boolean;
    position: number;
    groups_count: number;
    translations: Translations<'name'>;
}

const props = defineProps<{ nav: NavItem[]; linkTypes: LinkTypeRow[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'link-types')?.url ?? '');
const editingId = ref<number | null>(null);
const columns = [
    { key: 'name', label: t('catalog::admin.taxonomy_link_types.name') },
    { key: 'code', label: t('catalog::admin.taxonomy_link_types.code'), class: 'font-mono text-xs' },
    { key: 'groups_count', label: t('catalog::admin.taxonomy_link_types.link_groups') },
    { key: 'is_active', label: t('catalog::admin.taxonomy_link_types.status') },
    { key: 'actions', label: '', align: 'right' as const },
];

const { locales, defaultLocale } = useStoreLocales();
const blank = () => ({ code: '', is_active: true, position: 0, translations: translationsFor(locales, { name: '' }) });
const form = useForm(blank());
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(type: LinkTypeRow): void {
    editingId.value = type.id;
    form.defaults({ code: type.code, is_active: type.is_active, position: type.position, translations: translationsFor(locales, { name: '' }, type.translations) });
    form.reset();
}

function cancel(): void {
    editingId.value = null;
    form.defaults(blank());
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

async function destroy(type: LinkTypeRow): Promise<void> {
    if ((await confirmAction({ title: t('catalog::admin.taxonomy_link_types.delete_link_type_code', { code: type.code }), confirmLabel: t('catalog::admin.taxonomy_link_types.delete'), tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${type.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('catalog::admin.taxonomy_link_types.link_type')" />
    <CatalogTabs :nav="nav" active="link-types" />
    <PageHeader :title="t('catalog::admin.taxonomy_link_types.link_type')" :subtitle="t('catalog::admin.taxonomy_link_types.groups_suggested_products_product_page')" />


    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="linkTypes" :row-label="(type) => type.code" :empty-text="t('catalog::admin.taxonomy_link_types.no_link_types_yet')">
                <template #cell-name="{ row }">{{ row.translations[defaultLocale]?.name }}</template>
                <template #cell-is_active="{ row }"><StatusBadge :label="row.is_active ? t('catalog::admin.taxonomy_link_types.active') : t('catalog::admin.taxonomy_link_types.off')" :tone="row.is_active ? 'success' : 'neutral'" /></template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">{{ t('catalog::admin.taxonomy_link_types.edit') }}</button>
                        <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">{{ t('catalog::admin.taxonomy_link_types.delete') }}</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? t('catalog::admin.taxonomy_link_types.edit_link_type') : t('catalog::admin.taxonomy_link_types.add_link_type') }}</h2>
            <FormField v-for="option in locales" :key="option.code" :label="t('catalog::admin.taxonomy_link_types.display_name_label', { label: option.label })" :error="errors[`translations.${option.code}.name`]">
                <input v-model="form.translations[option.code].name" :class="inputClass" />
            </FormField>
            <FormField :label="t('catalog::admin.taxonomy_link_types.code')" :hint="t('catalog::admin.taxonomy_link_types.lowercase_letters_digits_hyphens_similar')" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField :label="t('catalog::admin.taxonomy_link_types.order_product_page')" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> {{ t('catalog::admin.taxonomy_link_types.shown_storefront') }}</label>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('catalog::admin.taxonomy_link_types.save') }}</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">{{ t('catalog::admin.taxonomy_link_types.cancel') }}</button>
            </div>
        </form>
    </div>
</template>
