<script setup lang="ts">
import { syncLockVersion } from '@admin/forms';
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
        status: 'draft' | 'active' | 'stopped';
        state: string;
        lock_version: number;
        promotion_ids: number[];
        price_list_ids: number[];
    };
    report: null | { orders: number; revenue: number; customers: number; cancelled: number; promotion_discount: number; usages: number; price_list_orders: number };
    promotions: Array<{ id: number; name: string; requires_voucher: boolean }>;
    priceLists: Array<{ id: number; code: string; name: string; type: string; status: string }>;
    can: { manage: boolean; price_lists: boolean };
}>();

const form = useForm({
    code: props.campaign?.code ?? '',
    name: props.campaign?.name ?? '',
    description: props.campaign?.description ?? '',
    starts_at: props.campaign?.starts_at ?? '',
    ends_at: props.campaign?.ends_at ?? '',
    promotion_ids: props.campaign?.promotion_ids ?? ([] as number[]),
    price_list_ids: props.campaign?.price_list_ids ?? ([] as number[]),
    lock_version: props.campaign?.lock_version ?? 0,
});
const errors = computed(() => form.errors as Record<string, string | undefined>);
const editable = computed(() => props.can.manage && props.campaign?.status !== 'stopped');

function save(): void {
    if (props.campaign) {
        form.put(`${props.baseUrl}/${props.campaign.id}`, { preserveScroll: true, onSuccess: syncLockVersion(form, () => props.campaign?.lock_version) });
    } else {
        form.post(props.baseUrl);
    }
}

async function act(action: 'activate' | 'stop'): Promise<void> {
    const options =
        action === 'stop'
            ? { title: 'Dừng ngay campaign?', message: 'Mọi khuyến mãi và bảng giá của campaign sẽ tắt và không bật lại được.', confirmLabel: 'Dừng ngay', tone: 'danger' as const }
            : { title: 'Kích hoạt campaign?', message: 'Khuyến mãi và bảng giá sẽ chạy theo lịch.', confirmLabel: 'Kích hoạt' };
    if (props.campaign && (await confirmAction(options)) !== false) {
        router.post(`${props.baseUrl}/${props.campaign.id}/${action}`, {}, { preserveScroll: true });
    }
}

async function destroy(): Promise<void> {
    if (props.campaign && (await confirmAction({ title: 'Xoá campaign nháp?', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${props.campaign.id}`);
    }
}
</script>

<template>
    <Head :title="campaign ? campaign.name : 'Thêm campaign'" />
    <PageHeader :title="campaign ? campaign.name : 'Thêm campaign'">
        <StatusBadge v-if="campaign" :label="labels.campaign_state[campaign.state] ?? campaign.state" :tone="campaignStateTone(campaign.state)" />
        <Link :href="baseUrl" :class="secondaryButton">Danh sách</Link>
        <template v-if="campaign && can.manage">
            <button v-if="campaign.status === 'draft'" type="button" :class="primaryButton" @click="act('activate')">Kích hoạt</button>
            <button v-if="campaign.status !== 'stopped'" type="button" :class="dangerButton" @click="act('stop')">Dừng ngay</button>
            <button v-if="campaign.status === 'draft'" type="button" :class="secondaryButton" @click="destroy">Xoá</button>
        </template>
    </PageHeader>


    <section v-if="report" class="mb-6 grid gap-3 sm:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Đơn của campaign</div><div class="text-xl font-semibold">{{ report.orders }}</div><div class="text-xs text-slate-400">{{ report.usages }} lượt khuyến mãi · {{ report.price_list_orders }} đơn giá sale</div></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Doanh thu các đơn</div><div class="text-xl font-semibold"><MoneyText :amount="report.revenue" /></div></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Đã giảm (khuyến mãi)</div><div class="text-xl font-semibold"><MoneyText :amount="report.promotion_discount" /></div></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Khách / đơn huỷ</div><div class="text-xl font-semibold">{{ report.customers }} / {{ report.cancelled }}</div></div>
    </section>

    <form class="grid gap-6 lg:grid-cols-2" @submit.prevent="save">
        <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
            <FormField label="Tên" :error="errors.name"><input v-model="form.name" :class="inputClass" :disabled="!editable" placeholder="vd. Sale 11.11" /></FormField>
            <FormField label="Mã" hint="chữ thường, vd. sale-1111" :error="errors.code"><input v-model="form.code" :class="inputClass" :disabled="!editable" /></FormField>
            <FormField label="Mô tả" :error="errors.description"><textarea v-model="form.description" rows="2" :class="inputClass" :disabled="!editable" /></FormField>
            <FormField label="Bắt đầu (giờ cửa hàng)" :error="errors.starts_at"><DateInput v-model="form.starts_at" with-time :disabled="!editable" /></FormField>
            <FormField label="Kết thúc (giờ cửa hàng)" :error="errors.ends_at"><DateInput v-model="form.ends_at" with-time :disabled="!editable" /></FormField>
            <button v-if="editable" type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
            <p class="text-xs text-slate-500">Campaign quyết định lịch và trạng thái của mọi thành viên: nháp → thành viên tắt; kích hoạt → bật theo lịch này; dừng → tắt ngay.</p>
        </div>

        <div class="space-y-4">
            <fieldset class="rounded-lg border border-slate-200 bg-white p-5">
                <legend class="px-1 text-sm font-medium">Khuyến mãi</legend>
                <p v-if="errors.promotion_ids" class="mb-2 text-sm text-red-600">{{ errors.promotion_ids }}</p>
                <label v-for="promotion in promotions" :key="promotion.id" class="flex items-center gap-2 py-1 text-sm">
                    <input v-model="form.promotion_ids" type="checkbox" :value="promotion.id" :disabled="!editable" />
                    {{ promotion.name }}<span v-if="promotion.requires_voucher" class="text-xs text-slate-400">(voucher)</span>
                </label>
                <p v-if="!promotions.length" class="text-sm text-slate-500">Chưa có khuyến mãi nào chưa thuộc campaign khác.</p>
            </fieldset>
            <fieldset class="rounded-lg border border-slate-200 bg-white p-5">
                <legend class="px-1 text-sm font-medium">Bảng giá sale / thành viên</legend>
                <p v-if="!can.price_lists" class="mb-2 text-xs text-slate-500">Cần quyền bảng giá (pricing.manage) để thay đổi.</p>
                <p v-if="errors.price_list_ids" class="mb-2 text-sm text-red-600">{{ errors.price_list_ids }}</p>
                <label v-for="list in priceLists" :key="list.id" class="flex items-center gap-2 py-1 text-sm">
                    <input v-model="form.price_list_ids" type="checkbox" :value="list.id" :disabled="!editable || !can.price_lists" />
                    {{ list.name }} <span class="font-mono text-xs text-slate-400">{{ list.code }} · {{ list.type }}</span>
                </label>
                <p v-if="!priceLists.length" class="text-sm text-slate-500">Chưa có bảng giá sale/thành viên.</p>
            </fieldset>
        </div>
    </form>
</template>
