<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem, Translations } from '../../types';

interface ValueRow {
    code: string;
    translations: Record<string, { label: string }>;
}

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    attribute: null | {
        id: number;
        code: string;
        kind: string;
        input_type: string;
        is_filterable: boolean;
        position: number;
        lock_version: number;
        translations: Translations<'name'>;
        values: Array<{ code: string; translations: Translations<'label'> }>;
    };
    kinds: string[];
    inputTypes: string[];
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'attributes')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();

const { form, save } = useAdminForm({
    code: props.attribute?.code ?? '',
    kind: props.attribute?.kind ?? 'spec',
    input_type: props.attribute?.input_type ?? 'select',
    is_filterable: props.attribute?.is_filterable ?? false,
    position: props.attribute?.position ?? 0,
    lock_version: props.attribute?.lock_version ?? null,
    translations: translationsFor(locales, { name: '' }, props.attribute?.translations),
    values: (props.attribute?.values ?? []).map<ValueRow>((value) => ({
        code: value.code,
        translations: translationsFor(locales, { label: '' }, value.translations),
    })),
}, { lockVersion: () => props.attribute?.lock_version });

const errors = computed(() => form.errors as Record<string, string | undefined>);
const hasOptions = computed(() => form.input_type === 'select' || form.input_type === 'multiselect');

function addValue(): void {
    form.values.push({ code: '', translations: translationsFor(locales, { label: '' }) });
}

function removeValue(index: number): void {
    form.values.splice(index, 1);
}

function submit(): void {
    if (props.attribute) {
        save('put', `${baseUrl.value}/${props.attribute.id}`);
    } else {
        save('post', baseUrl.value);
    }
}

async function destroy(): Promise<void> {
    if (props.attribute && (await confirmAction({ title: t('catalog::admin.taxonomy_attribute_form.delete_attribute'), confirmLabel: t('catalog::admin.taxonomy_attribute_form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${props.attribute.id}`);
    }
}
</script>

<template>
    <Head :title="attribute ? t('catalog::admin.taxonomy_attribute_form.edit_attribute') : t('catalog::admin.taxonomy_attribute_form.add_attribute')" />
    <CatalogTabs :nav="nav" active="attributes" />
    <PageHeader :title="attribute ? t('catalog::admin.taxonomy_attribute_form.edit_attribute') : t('catalog::admin.taxonomy_attribute_form.add_attribute')">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('catalog::admin.taxonomy_attribute_form.back') }}</Link>
        <button v-if="attribute" type="button" :class="dangerButton" @click="destroy">{{ t('catalog::admin.taxonomy_attribute_form.delete') }}</button>
    </PageHeader>

    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <FormField v-for="option in locales" :key="option.code" :label="t('catalog::admin.taxonomy_attribute_form.name_label_default_locale', { label: option.label, default_locale: option.code === defaultLocale ? ' *' : '' })" :error="errors[`translations.${option.code}.name`]">
                <input v-model="form.translations[option.code].name" :class="inputClass" type="text" />
            </FormField>
            <FormField :label="t('catalog::admin.taxonomy_attribute_form.code')" :hint="t('catalog::admin.taxonomy_attribute_form.lowercase_letters_digits_underscore_example')" :error="errors.code">
                <input v-model="form.code" :class="inputClass" type="text" />
            </FormField>
            <FormField :label="t('catalog::admin.taxonomy_attribute_form.order')" :error="errors.position">
                <input v-model.number="form.position" :class="inputClass" type="number" min="0" />
            </FormField>
            <FormField :label="t('catalog::admin.taxonomy_attribute_form.type')" :error="errors.kind">
                <Select v-model="form.kind" :options="codeOptions(kinds, labels.attribute_kind)" />
            </FormField>
            <FormField :label="t('catalog::admin.taxonomy_attribute_form.input_type')" :error="errors.input_type">
                <Select v-model="form.input_type" :options="codeOptions(inputTypes)" />
            </FormField>
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input v-model="form.is_filterable" type="checkbox" />
                {{ t('catalog::admin.taxonomy_attribute_form.use_storefront_filter') }}
            </label>
        </div>

        <div v-if="hasOptions" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium">{{ t('catalog::admin.taxonomy_attribute_form.value') }}</h2>
                <button type="button" :class="secondaryButton" @click="addValue">{{ t('catalog::admin.taxonomy_attribute_form.add_value') }}</button>
            </div>
            <p v-if="errors.values" class="text-sm text-red-600">{{ errors.values }}</p>
            <div v-for="(value, index) in form.values" :key="index" class="grid items-start gap-2 sm:grid-cols-[repeat(var(--cols),minmax(0,1fr))_auto]" :style="{ '--cols': locales.length + 1 }">
                <FormField :label="t('catalog::admin.taxonomy_attribute_form.code')" :error="errors[`values.${index}.code`]">
                    <input v-model="value.code" :class="inputClass" type="text" />
                </FormField>
                <FormField v-for="option in locales" :key="option.code" :label="t('catalog::admin.taxonomy_attribute_form.label_label', { label: option.label })" :error="errors[`values.${index}.translations.${option.code}.label`]">
                    <input v-model="value.translations[option.code].label" :class="inputClass" type="text" />
                </FormField>
                <button type="button" class="mt-6 text-sm text-red-600" @click="removeValue(index)">{{ t('catalog::admin.taxonomy_attribute_form.remove') }}</button>
            </div>
        </div>

        <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('catalog::admin.taxonomy_attribute_form.save') }}</button>
    </form>
</template>
