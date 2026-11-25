<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import ExtensionFormSections from '@admin/Components/Extensions/ExtensionFormSections.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions, recordOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { initialExtensionValues, type ExtensionLink, type ExtensionSection } from '@admin/types';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { slugify } from '@admin/slug';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import LocaleTabs from '../../Components/Taxonomy/LocaleTabs.vue';
import SeoFields from '../../Components/Taxonomy/SeoFields.vue';
import TaxonomyFormLayout from '../../Components/Taxonomy/TaxonomyFormLayout.vue';
import type { CatalogLabels, NavItem, Translations } from '../../types';

type Fields = 'name' | 'slug' | 'description' | 'meta_title' | 'meta_description';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    category: null | {
        id: number;
        slug: string;
        parent_id: number | null;
        status: string;
        position: number;
        lock_version: number;
        translations: Translations<Fields>;
        image_media_id: number | null;
    };
    parents: Array<{ id: number; label: string }>;
    extensionSections: ExtensionSection[];
    extensionLinks: ExtensionLink[];
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'categories')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();
const locale = ref(defaultLocale);
const title = computed(() => (props.category ? t('catalog::admin.categories.edit') : t('catalog::admin.categories.add')));

const emptyTranslation = { name: '', slug: '', description: '', meta_title: '', meta_description: '' };
const { form, save } = useAdminForm({
    slug: props.category?.slug ?? '',
    parent_id: props.category?.parent_id ?? null,
    status: props.category?.status ?? 'active',
    position: props.category?.position ?? 0,
    image_media_id: props.category?.image_media_id ?? null,
    lock_version: props.category?.lock_version ?? null,
    translations: translationsFor(locales, emptyTranslation, props.category?.translations),
    extensions: initialExtensionValues(props.extensionSections),
}, { lockVersion: () => props.category?.lock_version });
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.category) {
        save('put', `${baseUrl.value}/${props.category.id}`);
    } else {
        save('post', baseUrl.value);
    }
}

async function destroy(): Promise<void> {
    if (props.category && (await confirmAction({ title: t('catalog::admin.categories.delete_confirm'), confirmLabel: t('catalog::admin.taxonomy.delete'), tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${props.category.id}`);
    }
}
</script>

<template>
    <Head :title="title" />
    <CatalogTabs :nav="nav" active="categories" />
    <TaxonomyFormLayout :title="title" :back-url="baseUrl" :links="extensionLinks" :processing="form.processing" :can-delete="category !== null" @submit="submit" @delete="destroy">
        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
            <LocaleTabs v-model="locale" :locales="locales" />
            <FormField :label="locale === defaultLocale ? t('catalog::admin.taxonomy.name_required') : t('catalog::admin.taxonomy.name')" :error="errors[`translations.${locale}.name`]">
                <input v-model="form.translations[locale].name" :class="inputClass" type="text" />
            </FormField>
            <FormField v-if="locale !== defaultLocale" :label="t('catalog::admin.taxonomy.slug_language')" :hint="t('catalog::admin.taxonomy.slug_language_hint')" :error="errors[`translations.${locale}.slug`]">
                <div class="flex gap-2">
                    <input v-model="form.translations[locale].slug" :class="inputClass" :placeholder="slugify(form.translations[locale].name ?? '') || form.slug" />
                    <button type="button" :class="secondaryButton" :disabled="!form.translations[locale].name" @click="form.translations[locale].slug = slugify(form.translations[locale].name ?? '')">{{ t('catalog::admin.taxonomy.generate_from_name') }}</button>
                </div>
            </FormField>
            <FormField :label="t('catalog::admin.taxonomy.description')" :error="errors[`translations.${locale}.description`]">
                <textarea v-model="form.translations[locale].description" :class="inputClass" rows="4" />
            </FormField>
        </div>
        <SeoFields v-model:title="form.translations[locale].meta_title" v-model:description="form.translations[locale].meta_description" :title-error="errors[`translations.${locale}.meta_title`]" :description-error="errors[`translations.${locale}.meta_description`]" />
        <ExtensionFormSections v-model="form.extensions" :sections="extensionSections" :errors="errors" />

        <template #aside>
            <FormField :label="t('catalog::admin.taxonomy.slug')" :hint="t('catalog::admin.taxonomy.slug_hint')" :error="errors.slug">
                <div class="flex gap-2">
                    <input v-model="form.slug" :class="inputClass" type="text" />
                    <button v-if="!category" type="button" :class="secondaryButton" :disabled="!form.translations[defaultLocale].name" @click="form.slug = slugify(form.translations[defaultLocale].name ?? '')">{{ t('catalog::admin.taxonomy.generate_from_name') }}</button>
                </div>
            </FormField>
            <FormField :label="t('catalog::admin.categories.parent')" :error="errors.parent_id">
                <Select v-model="form.parent_id" :options="recordOptions(parents)" :empty-label="t('catalog::admin.categories.root')" :empty-value="null" />
            </FormField>
            <div class="grid grid-cols-2 gap-4">
                <FormField :label="t('catalog::admin.taxonomy.status')" :error="errors.status">
                    <Select v-model="form.status" :options="labelOptions(labels.visibility)" />
                </FormField>
                <FormField :label="t('catalog::admin.taxonomy.order')" :error="errors.position">
                    <input v-model.number="form.position" :class="inputClass" type="number" min="0" />
                </FormField>
            </div>
            <FormField :label="t('catalog::admin.taxonomy.image')" :hint="t('catalog::admin.categories.image_hint')" :error="errors.image_media_id">
                <MediaField v-model="form.image_media_id" :label="t('catalog::admin.taxonomy.image')" />
            </FormField>
        </template>
    </TaxonomyFormLayout>
</template>
