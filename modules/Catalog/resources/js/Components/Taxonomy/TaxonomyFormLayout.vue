<script setup lang="ts">
import { t } from '@admin/i18n';
import ExtensionLinks from '@admin/Components/Extensions/ExtensionLinks.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionLink } from '@admin/types';
import { Link } from '@inertiajs/vue3';

/**
 * Khuôn trang tạo/sửa chung của thương hiệu, danh mục, bộ sưu tập: đầu trang (quay lại, nút của plugin, xoá), cột chính
 * (nội dung, SEO, phần form của plugin) và cột phụ (đường dẫn, hiển thị, ảnh, nút Lưu).
 */
defineProps<{ title: string; backUrl: string; links: ExtensionLink[]; processing: boolean; canDelete?: boolean; deleteHint?: string | null }>();
defineEmits<{ submit: []; delete: [] }>();
</script>

<template>
    <div>
        <PageHeader :title="title">
            <Link :href="backUrl" :class="secondaryButton">{{ t('catalog::admin.taxonomy.back') }}</Link>
            <ExtensionLinks :links="links" />
            <button v-if="canDelete" type="button" :class="dangerButton" @click="$emit('delete')">{{ t('catalog::admin.taxonomy.delete') }}</button>
        </PageHeader>
        <p v-if="deleteHint" class="-mt-3 mb-4 text-xs text-slate-500">{{ deleteHint }}</p>

        <form class="grid gap-6 lg:grid-cols-3" data-taxonomy-form @submit.prevent="$emit('submit')">
            <div class="space-y-6 lg:col-span-2">
                <slot />
            </div>
            <div class="space-y-6">
                <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                    <slot name="aside" />
                    <button type="submit" :class="primaryButton" class="w-full" :disabled="processing">{{ t('catalog::admin.taxonomy.save') }}</button>
                </div>
                <slot name="aside-after" />
            </div>
        </form>
    </div>
</template>
