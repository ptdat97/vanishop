<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { slugify } from '@admin/slug';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem, Translations } from '../../types';

const props = defineProps<{
    contentUrl: string | null;
    nav: NavItem[];
    labels: CatalogLabels;
    collection: null | { id: number; slug: string; status: string; position: number; translations: Translations<'name' | 'slug' | 'description'>; style_codes: string };
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'collections')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();
const { form, save } = useAdminForm({
    slug: props.collection?.slug ?? '',
    status: props.collection?.status ?? 'active',
    position: props.collection?.position ?? 0,
    translations: translationsFor(locales, { name: '', slug: '', description: '' }, props.collection?.translations),
    style_codes: props.collection?.style_codes ?? '',
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.collection) {
        save('put', `${baseUrl.value}/${props.collection.id}`);
    } else {
        save('post', baseUrl.value);
    }
}

async function destroy(): Promise<void> {
    if (props.collection && (await confirmAction({ title: t('catalog::admin.collections_form.delete_collection'), message: t('catalog::admin.collections_form.products_not_deleted'), confirmLabel: t('catalog::admin.collections_form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${props.collection.id}`);
    }
}
</script>

<template>
    <Head :title="collection ? t('catalog::admin.collections_form.edit_collection') : t('catalog::admin.collections_form.add_collection')" />
    <CatalogTabs :nav="nav" active="collections" />
    <PageHeader :title="collection ? t('catalog::admin.collections_form.edit_collection') : t('catalog::admin.collections_form.add_collection')">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('catalog::admin.collections_form.back') }}</Link>
        <button v-if="collection" type="button" :class="dangerButton" @click="destroy">{{ t('catalog::admin.collections_form.delete') }}</button>
        <Link v-if="contentUrl" :href="contentUrl" :class="secondaryButton">{{ t('catalog::admin.collections_form.edit_content') }}</Link>
    </PageHeader>

    <form class="grid max-w-4xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField v-for="option in locales" :key="`name-${option.code}`" :label="t('catalog::admin.collections_form.name_label_default_locale', { label: option.label, default_locale: option.code === defaultLocale ? ' *' : '' })" :error="errors[`translations.${option.code}.name`]">
            <input v-model="form.translations[option.code].name" :class="inputClass" />
        </FormField>
        <template v-for="option in locales" :key="`slug-${option.code}`">
            <FormField v-if="option.code !== defaultLocale" :label="`Slug (${option.label})`" :hint="t('catalog::admin.collections_form.empty_use_base_slug_changing')" :error="errors[`translations.${option.code}.slug`]">
                <div class="flex gap-2">
                    <input v-model="form.translations[option.code].slug" :class="inputClass" :placeholder="slugify(form.translations[option.code].name ?? '') || form.slug" />
                    <button type="button" :class="secondaryButton" :disabled="!form.translations[option.code].name" @click="form.translations[option.code].slug = slugify(form.translations[option.code].name ?? '')">{{ t('catalog::admin.collections_form.generate_from_name') }}</button>
                </div>
            </FormField>
        </template>
        <FormField v-for="option in locales" :key="`description-${option.code}`" :label="t('catalog::admin.collections_form.description_label', { label: option.label })">
            <textarea v-model="form.translations[option.code].description" :class="inputClass" rows="3" />
        </FormField>
        <FormField label="Slug" :error="errors.slug"><input v-model="form.slug" :class="inputClass" /></FormField>
        <div class="grid grid-cols-2 gap-4">
            <FormField :label="t('catalog::admin.collections_form.status')" :error="errors.status">
                <Select v-model="form.status" :options="labelOptions(labels.visibility)" />
            </FormField>
            <FormField :label="t('catalog::admin.collections_form.order')" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
        </div>
        <FormField class="sm:col-span-2" :label="t('catalog::admin.collections_form.product_codes_display_order')" :hint="t('catalog::admin.collections_form.one_code_per_line_separated')" :error="errors.style_codes">
            <textarea v-model="form.style_codes" :class="inputClass" class="font-mono" rows="8" />
        </FormField>
        <div class="sm:col-span-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('catalog::admin.collections_form.save') }}</button>
        </div>
    </form>
</template>
