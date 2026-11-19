<script setup lang="ts">
import { t } from '@admin/i18n';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionTabs from '@admin/Components/Extensions/ExtensionTabs.vue';
import DataTable from '@admin/Components/DataTable.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { recordOptions } from '@admin/options';
import { dangerButton, inputClass, secondaryButton } from '@admin/styles';
import type { ExtensionDetail } from '@admin/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    baseUrl: string;
    listUrl: string;
    labels: { customer_status: Record<string, string> };
    customer: {
        id: string;
        phone: string | null;
        email: string | null;
        full_name: string | null;
        status: string;
        registered: boolean;
        created_at: string | null;
        birth_date: string | null;
        gender: string | null;
        last_login_at: string | null;
        merged_into: string | null;
    };
    addresses: Array<{
        id: number;
        label: string | null;
        full_name: string;
        phone: string;
        street_line: string;
        ward_name: string;
        province_name: string;
        is_default: boolean;
    }>;
    consents: Array<{
        channel: string;
        purpose: string;
        granted: boolean;
        granted_at: string | null;
        revoked_at: string | null;
        source: string;
    }>;
    stats: {
        orders_count: number;
        total_spent: number;
        first_order_at: string | null;
        last_order_at: string | null;
    };
    orders: Array<{
        number: string;
        status: string;
        total: number;
        placed_at: string;
    }>;
    can: { merge: boolean; anonymize: boolean; segment: boolean };
    segment: { customer_group_id: number | null; tags: string[]; groups: Array<{ id: number; code: string; name: string }> };
    extensions: ExtensionDetail & { id: number };
    /** Ghi chú CSKH (nhân viên + plugin, EP v3 X6). */
    notes: Array<{ note: string; source: string; at: string | null }>;
}>();
const noteForm = useForm({ note: '' });
function addNote(): void {
    noteForm.post(`${props.baseUrl}/${props.customer.id}/notes`, { preserveScroll: true, onSuccess: () => noteForm.reset() });
}

const orderColumns = [
    { key: 'number', label: t('customer::admin.customers_show.order'), class: 'font-mono text-xs' },
    { key: 'status', label: t('customer::admin.customers_show.status') },
    { key: 'placed_at', label: t('customer::admin.customers_show.placed'), class: 'text-xs text-slate-500' },
    { key: 'total', label: t('customer::admin.customers_show.total'), align: 'right' as const },
];
const mergeForm = useForm({ target: '' });
const segmentForm = useForm({ customer_group_id: props.segment.customer_group_id, tags: props.segment.tags.join(', ') });

function saveSegment(): void {
    segmentForm.put(`${props.baseUrl}/${props.customer.id}/segment`, { preserveScroll: true });
}

async function merge(): Promise<void> {
    const confirmed = await confirmAction({
        title: t('customer::admin.customers_show.merge_into_target', { target: mergeForm.target }),
        message: t('customer::admin.customers_show.all_orders_addresses_consents_customer'),
        confirmLabel: t('customer::admin.customers_show.merge'),
        tone: 'danger',
    });
    if (confirmed !== false) {
        mergeForm.post(`${props.baseUrl}/${props.customer.id}/merge`);
    }
}

async function anonymize(): Promise<void> {
    const confirmed = await confirmAction({
        title: t('customer::admin.customers_show.anonymise_customer'),
        message: t('customer::admin.customers_show.removes_personal_information_keeps_orders'),
        confirmLabel: t('customer::admin.customers_show.anonymise'),
        tone: 'danger',
    });
    if (confirmed !== false) {
        router.post(`${props.baseUrl}/${props.customer.id}/anonymize`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="customer.full_name ?? customer.id" />
    <PageHeader
        :title="customer.full_name ?? t('customer::admin.customers_show.no_name_yet')"
        :subtitle="`${customer.id} · ${labels.customer_status[customer.status] ?? customer.status} · ${customer.registered ? t('customer::admin.customers_show.account') : t('customer::admin.customers_show.guest')}`"
    >
        <Link :href="listUrl" :class="secondaryButton">{{ t('customer::admin.customers_show.list') }}</Link>
        <ExtensionActions :actions="extensions.actions" :ids="[extensions.id]" />
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <ExtensionTabs :tabs="extensions.tabs" />
            <section class="rounded-lg border border-slate-200 bg-white p-4" data-customer-notes>
                <h2 class="mb-2 font-semibold">{{ t('customer::admin.customers_show.customer_service_notes') }}</h2>
                <form v-if="can.segment" class="mb-3 flex gap-2" @submit.prevent="addNote">
                    <input v-model="noteForm.note" :class="inputClass" maxlength="500" :placeholder="t('customer::admin.customers_show.customer_pick_up_after_6pm')" :aria-label="t('customer::admin.customers_show.customer_service_notes')" />
                    <button type="submit" :class="secondaryButton" :disabled="noteForm.processing || !noteForm.note.trim()">{{ t('customer::admin.customers_show.add') }}</button>
                </form>
                <p v-if="!notes.length" class="text-sm text-slate-500">{{ t('customer::admin.customers_show.no_notes_yet') }}</p>
                <div v-for="(note, index) in notes" :key="index" class="border-t border-slate-100 py-1.5 text-sm">
                    {{ note.note }}
                    <div class="text-xs text-slate-400">{{ note.at }}<template v-if="note.source !== 'staff'"> · {{ note.source }}</template></div>
                </div>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">{{ t('customer::admin.customers_show.recent_orders') }}</h2>
                <DataTable :columns="orderColumns" :rows="orders" :row-key="(order) => order.number" :empty-text="t('customer::admin.customers_show.no_orders_yet')" bare>
                    <template #cell-status="{ row }"><StatusBadge :label="row.status" /></template>
                    <template #cell-total="{ row }"><MoneyText :amount="row.total" /></template>
                </DataTable>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">{{ t('customer::admin.customers_show.purchase_stats') }}</h2>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">{{ t('customer::admin.customers_show.orders') }}</dt>
                    <dd>{{ stats.orders_count }}</dd>
                    <dt class="text-slate-500">{{ t('customer::admin.customers_show.total_spent') }}</dt>
                    <dd><MoneyText :amount="stats.total_spent" /></dd>
                    <dt class="text-slate-500">{{ t('customer::admin.customers_show.first_latest_purchase') }}</dt>
                    <dd class="text-xs">{{ stats.first_order_at ?? '—' }} / {{ stats.last_order_at ?? '—' }}</dd>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-semibold">Consent</h2>
                <div v-for="consent in consents" :key="`${consent.channel}-${consent.purpose}`" class="text-sm">
                    {{ consent.channel }} · {{ consent.purpose }}:
                    <strong :class="consent.granted ? 'text-green-700' : 'text-slate-500'">{{ consent.granted ? t('customer::admin.customers_show.consented') : t('customer::admin.customers_show.withdrawn') }}</strong>
                    <span class="text-xs text-slate-400"> ({{ consent.source }})</span>
                </div>
                <p v-if="!consents.length" class="text-sm text-slate-500">{{ t('customer::admin.customers_show.no_consents_yet') }}</p>
            </section>
        </div>

        <div class="space-y-6">
            <form class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="saveSegment">
                <h2 class="font-semibold">{{ t('customer::admin.customers_show.group_tags') }}</h2>
                <label class="block">
                    <span class="text-slate-500">{{ t('customer::admin.customers_show.customer_group_determines_member_prices') }}</span>
                    <Select v-model="segmentForm.customer_group_id" :options="recordOptions(segment.groups, (group) => group.name)" :empty-label="t('customer::admin.customers_show.no_group')" :empty-value="null" :disabled="!can.segment" />
                </label>
                <label class="block">
                    <span class="text-slate-500">{{ t('customer::admin.customers_show.tags_comma_separated') }}</span>
                    <input v-model="segmentForm.tags" :class="inputClass" :disabled="!can.segment" placeholder="vd. khach-si, kol" />
                </label>
                <p v-for="(error, key) in segmentForm.errors" :key="key" class="text-red-600">{{ error }}</p>
                <button v-if="can.segment" type="submit" :class="secondaryButton" :disabled="segmentForm.processing">{{ t('customer::admin.customers_show.save') }}</button>
            </form>
            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="mb-3 font-semibold">{{ t('customer::admin.customers_show.profile') }}</h2>
                <dl class="space-y-1">
                    <div>
                        <dt class="inline text-slate-500">{{ t('customer::admin.customers_show.phone') }}</dt>
                        <dd class="inline font-mono">
                            {{ customer.phone ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="inline text-slate-500">Email:</dt>
                        <dd class="inline">{{ customer.email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-slate-500">{{ t('customer::admin.customers_show.date_birth') }}</dt>
                        <dd class="inline">{{ customer.birth_date ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-slate-500">{{ t('customer::admin.customers_show.last_sign') }}</dt>
                        <dd class="inline">
                            {{ customer.last_login_at ?? '—' }}
                        </dd>
                    </div>
                    <div v-if="customer.merged_into">
                        <dt class="inline text-slate-500">{{ t('customer::admin.customers_show.merged_into') }}</dt>
                        <dd class="inline">
                            <Link :href="`${baseUrl}/${customer.merged_into}`" class="text-indigo-600 hover:underline">{{ customer.merged_into }}</Link>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="mb-3 font-semibold">{{ t('customer::admin.customers_show.address_book') }}</h2>
                <div v-for="address in addresses" :key="address.id" class="mb-2">
                    <div class="font-medium">
                        {{ address.full_name }} · {{ address.phone }}<span v-if="address.is_default" class="text-xs text-indigo-600"> {{ t('customer::admin.customers_show.default') }}</span>
                    </div>
                    <div class="text-slate-500">
                        {{ address.street_line }}, {{ address.ward_name }},
                        {{ address.province_name }}
                    </div>
                </div>
                <p v-if="!addresses.length" class="text-slate-500">{{ t('customer::admin.customers_show.no_addresses_yet') }}</p>
            </section>

            <section
                v-if="customer.status === 'active' && (can.merge || can.anonymize)"
                class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm"
            >
                <h2 class="font-semibold">{{ t('customer::admin.customers_show.actions') }}</h2>
                <form v-if="can.merge" class="space-y-2" @submit.prevent="merge">
                    <label class="block text-slate-500">{{ t('customer::admin.customers_show.merge_customer_into_target_customer') }}</label>
                    <input v-model="mergeForm.target" :class="inputClass" placeholder="01J…" />
                    <p v-if="mergeForm.errors.target" class="text-xs text-red-600">
                        {{ mergeForm.errors.target }}
                    </p>
                    <button type="submit" :class="secondaryButton" :disabled="mergeForm.processing">{{ t('customer::admin.customers_show.merge') }}</button>
                </form>
                <button v-if="can.anonymize" :class="dangerButton" @click="anonymize">{{ t('customer::admin.customers_show.anonymise') }}</button>
            </section>
        </div>
    </div>
</template>
