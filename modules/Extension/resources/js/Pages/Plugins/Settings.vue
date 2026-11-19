<script setup lang="ts">
import { t } from '@admin/i18n';
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
    <Head :title="t('extension::admin.plugins_settings.settings_name', { name: plugin.name })" />
    <PageHeader :title="t('extension::admin.plugins_settings.settings_name', { name: plugin.name })" :subtitle="t('extension::admin.plugins_settings.leave_empty_use_default_value')">
        <Link :href="`${baseUrl}/${plugin.id}`" :class="secondaryButton">{{ t('extension::admin.plugins_settings.back_plugin') }}</Link>
    </PageHeader>
    <SettingsForm :action="`${baseUrl}/${plugin.id}/settings`" :namespace="plugin.id" :fields="fields" />
</template>
