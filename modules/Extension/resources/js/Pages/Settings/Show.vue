<script setup lang="ts">
import { t } from '@admin/i18n';
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
    <Head :title="t('extension::admin.settings_show.settings_title', { title })" />
    <PageHeader :title="title" :subtitle="t('extension::admin.settings_show.leave_empty_use_default_value')">
        <Link :href="baseUrl" :class="secondaryButton">{{ t('extension::admin.settings_show.back_list') }}</Link>
    </PageHeader>
    <SettingsForm :action="action" :namespace="namespace" :fields="fields" />
</template>
