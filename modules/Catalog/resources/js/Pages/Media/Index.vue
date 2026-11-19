<script setup lang="ts">
import { t } from '@admin/i18n';
import MediaManager from '@admin/Components/Media/MediaManager.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ libraryUrl: string; sizesUrl: string; canManage: boolean }>();
</script>

<template>
    <Head :title="t('catalog::admin.media_index.media_library')" />
    <PageHeader
        :title="t('catalog::admin.media_index.media_library')"
        :subtitle="t('catalog::admin.media_index.images_shared_products_categories_content')"
    >
        <Link :href="sizesUrl" :class="secondaryButton">{{ t('catalog::admin.media_index.image_sizes') }}</Link>
    </PageHeader>
    <div class="h-[calc(100vh-12rem)] min-h-[32rem]">
        <MediaManager :library-url="libraryUrl" />
    </div>
</template>
