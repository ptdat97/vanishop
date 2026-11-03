<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass } from '@admin/styles';
import { Head, router } from '@inertiajs/vue3';
import SettingsForm, { type SettingField } from '../../Components/SettingsForm.vue';

const props = defineProps<{
    baseUrl: string;
    namespaces: string[];
    namespace: string;
    fields: SettingField[];
}>();

function go(changes: { namespace?: string }): void {
    router.get(props.baseUrl, { namespace: props.namespace, ...changes });
}
</script>

<template>
    <Head title="Cấu hình" />
    <PageHeader
        title="Cấu hình"
        subtitle="Cấu hình của cửa hàng. Để trống = dùng giá trị mặc định. Secret không hiển thị lại."
    />

    <div class="mb-4 flex flex-wrap gap-2">
        <select :class="[inputClass, 'w-64']" :value="namespace" @change="go({ namespace: ($event.target as HTMLSelectElement).value })">
            <option v-for="item in namespaces" :key="item" :value="item">{{ item === 'core' ? 'Core' : `Plugin ${item}` }}</option>
        </select>
    </div>

    <SettingsForm :key="namespace" :action="baseUrl" :namespace="namespace" :fields="fields" />
</template>
