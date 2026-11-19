<script setup lang="ts">
import { t } from '@admin/i18n';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { SharedProps } from '../types';
import Select from './Select.vue';

/**
 * Chọn ngôn ngữ giao diện Admin (ADR-049): nhân viên lưu vào hồ sơ, trang đăng nhập lưu vào phiên. Server trả lại chính
 * trang này theo ngôn ngữ mới (chuỗi server + bản dịch Vue nhóm `*::admin`). Ẩn khi cửa hàng chỉ có một ngôn ngữ.
 */
const page = usePage<SharedProps>();
const saving = ref(false);
const options = computed(() => page.props.app.locales.map((locale) => ({ value: locale.code, label: locale.label })));

function change(locale: string): void {
    if (locale === page.props.app.locale || saving.value) return;
    router.put(page.props.app.localeUrl, { locale }, { preserveScroll: true, onStart: () => (saving.value = true), onFinish: () => (saving.value = false) });
}
</script>

<template>
    <div v-if="options.length > 1" class="w-36" data-admin-locale-switcher>
        <Select :model-value="page.props.app.locale" :options="options" :aria-label="t('shared::admin.locale.label')" :disabled="saving" @change="change" />
    </div>
</template>
