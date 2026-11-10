<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import SettingsForm, { type SettingField } from '../../Components/SettingsForm.vue';

defineProps<{
    baseUrl: string;
    action: string;
    namespace: string;
    title: string;
    subtitle: string;
    fields: SettingField[];
}>();
</script>

<template>
    <Head :title="`Cấu hình: ${title}`" />
    <PageHeader :title="title" subtitle="Để trống = dùng giá trị mặc định. Secret không hiển thị lại.">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại danh sách</Link>
    </PageHeader>
    <SettingsForm :action="action" :namespace="namespace" :fields="fields" />
</template>
