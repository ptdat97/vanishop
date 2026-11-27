<script setup lang="ts">
import { t } from '@admin/i18n';
import { useAdminForm } from '@admin/forms';
import DateInput from '@admin/Components/DateInput.vue';
import WeeklyScheduleInput, { type WeeklyWindow } from '@admin/Components/WeeklyScheduleInput.vue';
import ExtensionTabs from '@admin/Components/Extensions/ExtensionTabs.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionDetail } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { campaignStateTone, type PromotionLabels } from '../../labels';

const props = defineProps<{
    baseUrl: string;
    labels: PromotionLabels;
    campaign: null | {
        id: number;
        code: string;
        name: string;
        description: string | null;
        starts_at: string;
        ends_at: string;
        recurrence: WeeklyWindow[] | null;
        status: 'draft' | 'active' | 'stopped';
        state: string;
        lock_version: number;
        promotion_ids: number[];
        price_list_ids: number[];
    };
    extensions: { tabs: ExtensionDetail['tabs'] };
    promotions: Array<{ id: number; name: string; requires_voucher: boolean }>;
    priceLists: Array<{ id: number; code: string; name: string; type: string; status: string }>;
    can: { manage: boolean; price_lists: boolean };
}>();

const { form, save } = useAdminForm({
    code: props.campaign?.code ?? '',
    name: props.campaign?.name ?? '',
    description: props.campaign?.description ?? '',
    starts_at: props.campaign?.starts_at ?? '',
    ends_at: props.campaign?.ends_at ?? '',
    recurrence: (props.campaign?.recurrence ?? null) as WeeklyWindow[] | null,
    promotion_ids: props.campaign?.promotion_ids ?? ([] as number[]),
    price_list_ids: props.campaign?.price_list_ids ?? ([] as number[]),
    lock_version: props.campaign?.lock_version ?? 0,
}, { lockVersion: () => props.campaign?.lock_version });
const errors = computed(() => form.errors as Record<string, string | undefined>);
const editable = computed(() => props.can.manage && props.campaign?.status !== 'stopped');

function submit(): void {
    if (props.campaign) {
        save('put', `${props.baseUrl}/${props.campaign.id}`);
    } else {
        save('post', props.baseUrl);
    }
}

async function act(action: 'activate' | 'stop'): Promise<void> {
    const options =
        action === 'stop'
            ? { title: t('promotion::admin.campaigns_form.stop_campaign_now'), message: t('promotion::admin.campaigns_form.every_promotion_price_list_campaign'), confirmLabel: t('promotion::admin.campaigns_form.stop_now'), tone: 'danger' as const }
            : { title: t('promotion::admin.campaigns_form.activate_campaign'), message: t('promotion::admin.campaigns_form.promotions_price_lists_run_schedule'), confirmLabel: t('promotion::admin.campaigns_form.activate') };
    if (props.campaign && (await confirmAction(options)) !== false) {
        router.post(`${props.baseUrl}/${props.campaign.id}/${action}`, {}, { preserveScroll: true });
    }
}

async function destroy(): Promise<void> {
    if (props.campaign && (await confirmAction({ title: t('promotion::admin.campaigns_form.delete_draft_campaign'), confirmLabel: t('promotion::admin.campaigns_form.delete'), tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${props.campaign.id}`);
    }
}
</script>

<template>
    <Head :title="campaign ? campaign.name : t('promotion::admin.campaigns_form.add_campaign')" />
    <PageHeader :title="campaign ? campaign.name : t('promotion::admin.campaigns_form.add_campaign')">
        <StatusBadge v-if="campaign" :label="labels.campaign_state[campaign.state] ?? campaign.state" :tone="campaignStateTone(campaign.state)" />
        <Link :href="baseUrl" :class="secondaryButton">{{ t('promotion::admin.campaigns_form.list') }}</Link>
        <template v-if="campaign && can.manage">
            <button v-if="campaign.status === 'draft'" type="button" :class="primaryButton" @click="act('activate')">{{ t('promotion::admin.campaigns_form.activate') }}</button>
            <button v-if="campaign.status !== 'stopped'" type="button" :class="dangerButton" @click="act('stop')">{{ t('promotion::admin.campaigns_form.stop_now') }}</button>
            <button v-if="campaign.status === 'draft'" type="button" :class="secondaryButton" @click="destroy">{{ t('promotion::admin.campaigns_form.delete') }}</button>
        </template>
    </PageHeader>


    <!-- Kết quả chiến dịch: tab do plugin báo cáo góp vào (vd. vani.reports). -->
    <div v-if="extensions.tabs.length" class="mb-6"><ExtensionTabs :tabs="extensions.tabs" /></div>

    <form class="grid gap-6 lg:grid-cols-2" @submit.prevent="submit">
        <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
            <FormField :label="t('promotion::admin.campaigns_form.name')" :error="errors.name"><input v-model="form.name" :class="inputClass" :disabled="!editable" placeholder="vd. Sale 11.11" /></FormField>
            <FormField :label="t('promotion::admin.campaigns_form.code')" :hint="t('promotion::admin.campaigns_form.lowercase_sale_1111')" :error="errors.code"><input v-model="form.code" :class="inputClass" :disabled="!editable" /></FormField>
            <FormField :label="t('promotion::admin.campaigns_form.description')" :error="errors.description"><textarea v-model="form.description" rows="2" :class="inputClass" :disabled="!editable" /></FormField>
            <FormField :label="t('promotion::admin.campaigns_form.starts_store_time')" :error="errors.starts_at"><DateInput v-model="form.starts_at" with-time :disabled="!editable" /></FormField>
            <FormField :label="t('promotion::admin.campaigns_form.ends_store_time')" :error="errors.ends_at"><DateInput v-model="form.ends_at" with-time :disabled="!editable" /></FormField>
            <div class="sm:col-span-2"><WeeklyScheduleInput v-model="form.recurrence" :disabled="!editable" :error="errors.recurrence" /></div>
            <button v-if="editable" type="submit" :class="primaryButton" :disabled="form.processing">{{ t('promotion::admin.campaigns_form.save') }}</button>
            <p class="text-xs text-slate-500">{{ t('promotion::admin.campaigns_form.campaign_controls_schedule_status_all') }}</p>
        </div>

        <div class="space-y-4">
            <fieldset class="rounded-lg border border-slate-200 bg-white p-5">
                <legend class="px-1 text-sm font-medium">{{ t('promotion::admin.campaigns_form.promotion') }}</legend>
                <p v-if="errors.promotion_ids" class="mb-2 text-sm text-red-600">{{ errors.promotion_ids }}</p>
                <label v-for="promotion in promotions" :key="promotion.id" class="flex items-center gap-2 py-1 text-sm">
                    <input v-model="form.promotion_ids" type="checkbox" :value="promotion.id" :disabled="!editable" />
                    {{ promotion.name }}<span v-if="promotion.requires_voucher" class="text-xs text-slate-400">(voucher)</span>
                </label>
                <p v-if="!promotions.length" class="text-sm text-slate-500">{{ t('promotion::admin.campaigns_form.no_promotions_outside_other_campaigns') }}</p>
            </fieldset>
            <fieldset class="rounded-lg border border-slate-200 bg-white p-5">
                <legend class="px-1 text-sm font-medium">{{ t('promotion::admin.campaigns_form.sale_member_price_lists') }}</legend>
                <p v-if="!can.price_lists" class="mb-2 text-xs text-slate-500">{{ t('promotion::admin.campaigns_form.requires_price_list_permission_pricing') }}</p>
                <p v-if="errors.price_list_ids" class="mb-2 text-sm text-red-600">{{ errors.price_list_ids }}</p>
                <label v-for="list in priceLists" :key="list.id" class="flex items-center gap-2 py-1 text-sm">
                    <input v-model="form.price_list_ids" type="checkbox" :value="list.id" :disabled="!editable || !can.price_lists" />
                    {{ list.name }} <span class="font-mono text-xs text-slate-400">{{ list.code }} · {{ list.type }}</span>
                </label>
                <p v-if="!priceLists.length" class="text-sm text-slate-500">{{ t('promotion::admin.campaigns_form.no_sale_member_price_lists') }}</p>
            </fieldset>
        </div>
    </form>
</template>
