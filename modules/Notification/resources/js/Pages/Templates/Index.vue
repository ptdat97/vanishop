<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { useStoreLocales } from '@admin/locales';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Template = {
    id: number;
    type: string;
    channel: string;
    locale: string;
    subject: string | null;
    body: string | null;
    meta: Record<string, unknown> | null;
    active: boolean;
    lock_version: number;
};

const props = defineProps<{
    baseUrl: string;
    types: Record<string, { label: string; variables: string[] }>;
    channels: string[];
    templates: Template[];
    can: { manage: boolean };
}>();

const { locales, defaultLocale } = useStoreLocales();
const editing = ref<Template | null>(null);
const creating = ref(false);
const form = useForm({
    type: 'order_placed',
    channel: 'mail',
    locale: defaultLocale,
    subject: '',
    body: '',
    meta_json: '',
    active: true,
    lock_version: 0,
});
const metaError = ref<string | null>(null);
const typeOptions = computed(() => Object.entries(props.types).map(([value, type]) => ({ value, label: type.label })));
const localeOptions = locales.map((option) => ({ value: option.code, label: option.label }));
const columns = [
    { key: 'type', label: t('notification::admin.templates_index.message_type') },
    { key: 'channel', label: t('notification::admin.templates_index.channel'), class: 'font-mono text-xs' },
    { key: 'body', label: t('notification::admin.templates_index.content'), class: 'max-w-md text-xs whitespace-pre-line text-slate-600' },
    { key: 'actions', label: '', align: 'right' as const, class: 'whitespace-nowrap' },
];
const variableHint = computed(() => (props.types[form.type]?.variables ?? []).map((name) => '{' + '{ ' + name + ' }' + '}').join(', '));

function open(template: Template | null): void {
    editing.value = template;
    creating.value = template === null;
    form.defaults({
        type: template?.type ?? 'order_placed',
        channel: template?.channel ?? 'mail',
        locale: template?.locale ?? defaultLocale,
        subject: template?.subject ?? '',
        body: template?.body ?? '',
        meta_json: template?.meta ? JSON.stringify(template.meta, null, 2) : '',
        active: template?.active ?? true,
        lock_version: template?.lock_version ?? 0,
    });
    form.reset();
    form.clearErrors();
    metaError.value = null;
}

function submit(): void {
    let meta: Record<string, unknown> | null = null;
    try {
        meta = form.meta_json.trim() === '' ? null : JSON.parse(form.meta_json);
        metaError.value = null;
    } catch {
        metaError.value = t('notification::admin.templates_index.invalid_json');
        return;
    }
    const payload = form.transform((data) => ({
        ...data,
        meta,
        meta_json: undefined,
    }));
    const options = {
        preserveScroll: true,
        onSuccess: () => ((editing.value = null), (creating.value = false)),
    };
    if (editing.value) payload.put(`${props.baseUrl}/${editing.value.id}`, options);
    else payload.post(props.baseUrl, options);
}

async function remove(template: Template): Promise<void> {
    if ((await confirmAction({ title: t('notification::admin.templates_index.delete_message_template'), confirmLabel: t('notification::admin.templates_index.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${template.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('notification::admin.templates_index.message_templates')" />
    <PageHeader :title="t('notification::admin.templates_index.message_templates')" :subtitle="t('notification::admin.templates_index.transactional_messages_type_channel_variables')">
        <Link :href="baseUrl.replace(/templates$/, 'logs')" :class="secondaryButton">{{ t('notification::admin.templates_index.send_log') }}</Link>
        <button v-if="can.manage" :class="primaryButton" @click="open(null)">{{ t('notification::admin.templates_index.add_template') }}</button>
    </PageHeader>

    <form v-if="creating || editing" class="mb-6 space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="submit">
        <div class="grid gap-3 md:grid-cols-3">
            <label>
                {{ t('notification::admin.templates_index.message_type') }}
                <Select v-model="form.type" :options="typeOptions" :aria-label="t('notification::admin.templates_index.message_type')" />
            </label>
            <label>
                {{ t('notification::admin.templates_index.channel') }}
                <input v-model="form.channel" :class="inputClass" list="notification-channels" />
                <datalist id="notification-channels">
                    <option v-for="channel in channels" :key="channel" :value="channel" />
                </datalist>
            </label>
            <label>
                {{ t('notification::admin.templates_index.language') }}
                <Select v-model="form.locale" :options="localeOptions" :aria-label="t('notification::admin.templates_index.language')" />
            </label>
        </div>
        <label class="block">
            {{ t('notification::admin.templates_index.subject_email') }}
            <input v-model="form.subject" :class="inputClass" />
            <span v-if="form.errors.subject" class="text-xs text-red-600">{{ form.errors.subject }}</span>
        </label>
        <label class="block">
            {{ t('notification::admin.templates_index.content') }}
            <textarea v-model="form.body" rows="5" :class="inputClass" />
            <span v-if="form.errors.body" class="text-xs text-red-600">{{ form.errors.body }}</span>
            <span v-if="form.errors.type" class="text-xs text-red-600">{{ form.errors.type }}</span>
        </label>
        <label class="block">
            {{ t('notification::admin.templates_index.channel_parameters_json_zns_template') }}
            <textarea v-model="form.meta_json" rows="3" :class="[inputClass, 'font-mono text-xs']" />
            <span v-if="metaError" class="text-xs text-red-600">{{ metaError }}</span>
        </label>
        <p class="text-xs text-slate-500">{{ t('notification::admin.templates_index.available_variables_variable_hint', { variable_hint: variableHint }) }}</p>
        <label class="flex items-center gap-2"><input v-model="form.active" type="checkbox" /> {{ t('notification::admin.templates_index.use') }}</label>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('notification::admin.templates_index.save') }}</button>
            <button type="button" :class="secondaryButton" @click="((editing = null), (creating = false))">{{ t('notification::admin.templates_index.cancel') }}</button>
        </div>
    </form>

    <DataTable :columns="columns" :rows="templates" :row-class="() => 'align-top'" :row-click="can.manage ? open : undefined" :empty-text="t('notification::admin.templates_index.no_message_templates_yet')">
        <template #cell-type="{ row }">
            {{ types[row.type]?.label ?? row.type }}
            <div class="font-mono text-xs text-slate-400">{{ row.type }} · {{ row.locale }}<span v-if="!row.active"> {{ t('notification::admin.templates_index.off') }}</span></div>
        </template>
        <template #cell-body="{ row }">{{ row.subject ? `${row.subject}\n` : '' }}{{ row.body }}</template>
        <template #cell-actions="{ row }">
            <button v-if="can.manage" :class="dangerButton" @click="remove(row)">{{ t('notification::admin.templates_index.delete') }}</button>
        </template>
    </DataTable>
</template>
