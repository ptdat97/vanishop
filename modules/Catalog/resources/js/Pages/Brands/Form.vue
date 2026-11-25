<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import ExtensionFormSections from '@admin/Components/Extensions/ExtensionFormSections.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaField from '@admin/Components/Media/MediaField.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { inputClass, secondaryButton } from '@admin/styles';
import { initialExtensionValues, type ExtensionLink, type ExtensionSection } from '@admin/types';
import { slugify } from '@admin/slug';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import SeoFields from '../../Components/Taxonomy/SeoFields.vue';
import TaxonomyFormLayout from '../../Components/Taxonomy/TaxonomyFormLayout.vue';
import type { CatalogLabels, NavItem } from '../../types';

/** Thương hiệu một ngôn ngữ, một slug cho mọi ngôn ngữ (ADR-028); cùng khuôn trang với danh mục và bộ sưu tập. */
const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    brand: null | {
        id: number;
        code: string;
        slug: string;
        name: string;
        description: string | null;
        meta_title: string | null;
        meta_description: string | null;
        logo_media_id: number | null;
        status: 'active' | 'hidden';
        position: number;
        lock_version: number;
        styles_count: number;
    };
    extensionSections: ExtensionSection[];
    extensionLinks: ExtensionLink[];
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'brands')?.url ?? '');
const title = computed(() => (props.brand ? t('catalog::admin.brands.edit') : t('catalog::admin.brands.add')));

const { form, save } = useAdminForm({
    code: props.brand?.code ?? '',
    slug: props.brand?.slug ?? '',
    name: props.brand?.name ?? '',
    description: props.brand?.description ?? '',
    meta_title: props.brand?.meta_title ?? '',
    meta_description: props.brand?.meta_description ?? '',
    logo_media_id: props.brand?.logo_media_id ?? null,
    status: props.brand?.status ?? 'active',
    position: props.brand?.position ?? 0,
    lock_version: props.brand?.lock_version ?? null,
    extensions: initialExtensionValues(props.extensionSections),
}, { lockVersion: () => props.brand?.lock_version });
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.brand) {
        save('put', `${baseUrl.value}/${props.brand.id}`);
    } else {
        save('post', baseUrl.value);
    }
}

async function destroy(): Promise<void> {
    if (props.brand && (await confirmAction({ title: t('catalog::admin.brands.delete_confirm', { name: props.brand.name }), confirmLabel: t('catalog::admin.taxonomy.delete'), tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${props.brand.id}`);
    }
}
</script>

<template>
    <Head :title="title" />
    <CatalogTabs :nav="nav" active="brands" />
    <TaxonomyFormLayout
        :title="title"
        :back-url="baseUrl"
        :links="extensionLinks"
        :processing="form.processing"
        :can-delete="brand !== null && brand.styles_count === 0"
        :delete-hint="brand !== null && brand.styles_count > 0 ? t('catalog::admin.brands.in_use') : null"
        @submit="submit"
        @delete="destroy"
    >
        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
            <p v-if="errors.brand" class="text-xs text-red-600">{{ errors.brand }}</p>
            <FormField :label="t('catalog::admin.taxonomy.name_required')" :error="errors.name">
                <input v-model="form.name" :class="inputClass" type="text" />
            </FormField>
            <FormField :label="t('catalog::admin.taxonomy.description')" :error="errors.description">
                <textarea v-model="form.description" :class="inputClass" rows="4" />
            </FormField>
            <p v-if="brand" class="text-xs text-slate-500">{{ t('catalog::admin.taxonomy.products_count', { count: brand.styles_count }) }}</p>
        </div>
        <SeoFields v-model:title="form.meta_title" v-model:description="form.meta_description" :title-error="errors.meta_title" :description-error="errors.meta_description" />
        <ExtensionFormSections v-model="form.extensions" :sections="extensionSections" :errors="errors" />

        <template #aside>
            <FormField :label="t('catalog::admin.taxonomy.slug')" :hint="t('catalog::admin.taxonomy.slug_hint')" :error="errors.slug">
                <div class="flex gap-2">
                    <input v-model="form.slug" :class="inputClass" type="text" />
                    <button v-if="!brand" type="button" :class="secondaryButton" :disabled="!form.name" @click="form.slug = slugify(form.name)">{{ t('catalog::admin.taxonomy.generate_from_name') }}</button>
                </div>
            </FormField>
            <FormField :label="t('catalog::admin.brands.code')" :hint="t('catalog::admin.brands.code_hint')" :error="errors.code">
                <input v-model="form.code" :class="inputClass" class="font-mono" type="text" />
            </FormField>
            <div class="grid grid-cols-2 gap-4">
                <FormField :label="t('catalog::admin.taxonomy.status')" :hint="t('catalog::admin.brands.hidden_hint')" :error="errors.status">
                    <Select v-model="form.status" :options="labelOptions(labels.visibility) as Array<{ value: 'active' | 'hidden'; label: string }>" />
                </FormField>
                <FormField :label="t('catalog::admin.taxonomy.order')" :error="errors.position">
                    <input v-model.number="form.position" :class="inputClass" type="number" min="0" />
                </FormField>
            </div>
            <FormField :label="t('catalog::admin.brands.logo')" :hint="t('catalog::admin.brands.logo_hint')" :error="errors.logo_media_id">
                <MediaField v-model="form.logo_media_id" :label="t('catalog::admin.brands.logo')" />
            </FormField>
        </template>
    </TaxonomyFormLayout>
</template>
