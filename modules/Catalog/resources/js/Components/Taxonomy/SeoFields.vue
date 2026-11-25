<script setup lang="ts">
import { t } from '@admin/i18n';
import FormField from '@admin/Components/FormField.vue';
import { inputClass } from '@admin/styles';

/** Tiêu đề/mô tả SEO; trống thì storefront dùng tên và đoạn đầu mô tả (PageMeta). */
defineProps<{ titleError?: string; descriptionError?: string }>();
const title = defineModel<string | null | undefined>('title');
const description = defineModel<string | null | undefined>('description');
</script>

<template>
    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="text-sm font-medium">{{ t('catalog::admin.taxonomy.seo') }}</h2>
        <FormField :label="t('catalog::admin.taxonomy.meta_title')" :hint="t('catalog::admin.taxonomy.meta_title_hint')" :error="titleError">
            <input v-model="title" :class="inputClass" type="text" maxlength="255" />
        </FormField>
        <FormField :label="t('catalog::admin.taxonomy.meta_description')" :hint="t('catalog::admin.taxonomy.meta_description_hint')" :error="descriptionError">
            <textarea v-model="description" :class="inputClass" rows="2" maxlength="512" />
        </FormField>
    </div>
</template>
