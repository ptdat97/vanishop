<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';
import SettingsForm, { type SettingField } from '../../Components/SettingsForm.vue';

defineProps<{
    baseUrl: string;
    plugin: { id: string; name: string };
    fields: SettingField[];
}>();
</script>

<template>
    <Head :title="`Cấu hình: ${plugin.name}`" />
    <PageHeader :title="`Cấu hình: ${plugin.name}`" subtitle="Để trống = dùng giá trị mặc định. Secret không hiển thị lại.">
        <Link :href="`${baseUrl}/${plugin.id}`" :class="secondaryButton">Quay lại plugin</Link>
    </PageHeader>
    <SettingsForm :action="`${baseUrl}/${plugin.id}/settings`" :namespace="plugin.id" :fields="fields" />
</template>
