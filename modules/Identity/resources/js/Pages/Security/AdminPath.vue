<script setup lang="ts">
import { t } from '@admin/i18n';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

/** Đường dẫn Admin (ADR-020), chỉ super admin. Đổi xong phải đăng nhập lại ở đường dẫn mới. */
const props = defineProps<{ path: string; defaultPath: string; baseUrl: string }>();

const form = useForm({ path: props.path });
const newUrl = computed(() => `${props.baseUrl}${form.path.trim().toLowerCase()}`);
const unchanged = computed(() => form.path.trim().toLowerCase() === props.path);

async function save(): Promise<void> {
    if (unchanged.value) return;
    const confirmed = await confirmAction({
        title: t('identity::admin.admin_path.confirm_title'),
        message: t('identity::admin.admin_path.confirm_message', { url: newUrl.value }),
        confirmLabel: t('identity::admin.admin_path.save'),
        tone: 'danger',
    });
    if (confirmed !== false) {
        form.put(window.location.pathname, { preserveScroll: true });
    }
}

function useDefault(): void {
    form.path = props.defaultPath;
    void save();
}
</script>

<template>
    <Head :title="t('identity::admin.admin_path.title')" />
    <PageHeader :title="t('identity::admin.admin_path.title')" :subtitle="t('identity::admin.admin_path.subtitle')" />

    <form class="max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-5" data-admin-path-form @submit.prevent="save">
        <p class="text-sm text-slate-600">
            {{ t('identity::admin.admin_path.current') }}
            <code class="rounded bg-slate-100 px-1.5 py-0.5">{{ baseUrl }}{{ path }}</code>
        </p>
        <FormField :label="t('identity::admin.admin_path.new_path')" for="admin-path" :hint="t('identity::admin.admin_path.hint')" :error="form.errors.path">
            <div class="flex items-center gap-2">
                <span class="shrink-0 text-sm text-slate-500">{{ baseUrl }}</span>
                <input id="admin-path" v-model="form.path" :class="inputClass" class="font-mono" autocomplete="off" spellcheck="false" />
            </div>
        </FormField>
        <p class="rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">{{ t('identity::admin.admin_path.warning') }}</p>
        <div class="flex flex-wrap gap-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing || unchanged">{{ t('identity::admin.admin_path.save') }}</button>
            <button v-if="path !== defaultPath" type="button" :class="secondaryButton" :disabled="form.processing" @click="useDefault">
                {{ t('identity::admin.admin_path.use_default', { path: defaultPath }) }}
            </button>
        </div>
    </form>
</template>
