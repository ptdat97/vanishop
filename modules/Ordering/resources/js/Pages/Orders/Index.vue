<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import DateInput from '@admin/Components/DateInput.vue';
import ExtensionActions from '@admin/Components/Extensions/ExtensionActions.vue';
import ExtensionFilters from '@admin/Components/Extensions/ExtensionFilters.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import Select from '@admin/Components/Select.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { ExtensionList } from '@admin/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { paymentStatusTone, stageTone } from '../../statusTones';

/**
 * Danh sách đơn theo hàng đợi việc (0.3.96): thẻ giai đoạn vận hành (OrderStage) có số đếm, bộ lọc ngày/nguồn/thanh toán,
 * cột tuổi đơn, sản phẩm, tỉnh; cột "Vận đơn" và thao tác hàng loạt (Tạo vận đơn, Đã bàn giao) do Fulfillment đóng góp.
 */
type Filters = { stage: string; status: string; payment_status: string; payment_method: string; source: string; from: string; to: string; q: string };

const props = defineProps<{
    baseUrl: string;
    filters: Filters;
    stages: string[];
    actionableStages: string[];
    stageCounts: Record<string, number>;
    statuses: string[];
    paymentStatuses: string[];
    labels: {
        order_status: Record<string, string>;
        payment_status: Record<string, string>;
        stage: Record<string, string>;
        source: Record<string, string>;
        payment_method: Record<string, string>;
    };
    orders: Array<{
        id: number;
        number: string;
        placed_at: string;
        age_hours: number | null;
        customer: string;
        phone: string;
        province: string;
        items: Array<{ sku: string; quantity: number }>;
        total: number;
        stage: string;
        order_status: string;
        payment_status: string;
        payment_method: string;
        source: string;
        label: string;
    }>;
    pagination: { current: number; last: number; total: number };
    extensions: ExtensionList;
}>();

const form = reactive<Filters>({ ...props.filters });
const ext = reactive<Record<string, string>>({ ...props.extensions.filterValues });
const selected = ref<number[]>([]);
const moreFilters = ref(['status', 'payment_status', 'payment_method', 'source', 'from', 'to'].some((key) => props.filters[key as keyof Filters] !== ''));
const options = (values: string[] | Record<string, string>, labels: Record<string, string>) =>
    (Array.isArray(values) ? values : Object.keys(values)).map((value) => ({ value, label: labels[value] ?? value }));
const totalCount = computed(() => Object.values(props.stageCounts).reduce((sum, count) => sum + count, 0));
const columns = [
    { key: 'number', label: 'Đơn' },
    { key: 'customer', label: 'Khách' },
    { key: 'items', label: 'Sản phẩm' },
    { key: 'total', label: 'Tổng', align: 'right' as const },
    { key: 'stage', label: 'Giai đoạn' },
    { key: 'payment_status', label: 'Thanh toán' },
];

function search(page = 1): void {
    selected.value = [];
    router.get(props.baseUrl, { ...form, ext, page }, { preserveState: true, preserveScroll: true });
}

function openStage(stage: string): void {
    form.stage = stage;
    search();
}

/** Tuổi đơn: đơn cần xử lý để lâu đổi màu (quá 24 giờ: cam, quá 48 giờ: đỏ). */
function ageText(hours: number | null): string {
    if (hours === null) return '';
    return hours < 24 ? `${hours} giờ` : `${Math.floor(hours / 24)} ngày`;
}
function ageClass(row: { age_hours: number | null; stage: string }): string {
    if (row.age_hours === null || !props.actionableStages.includes(row.stage)) return 'text-slate-400';
    return row.age_hours >= 48 ? 'font-medium text-red-600' : row.age_hours >= 24 ? 'text-amber-600' : 'text-slate-400';
}
</script>

<template>
    <Head title="Đơn hàng" />
    <p class="mb-2 text-sm text-slate-500">Đơn hàng</p>
    <PageHeader title="Đơn hàng" :subtitle="`${pagination.total} đơn`" />

    <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200 text-sm" aria-label="Hàng đợi đơn">
        <button
            type="button"
            class="shrink-0 border-b-2 px-3 py-2"
            :class="form.stage === '' ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
            :aria-current="form.stage === '' ? 'page' : undefined"
            @click="openStage('')"
        >
            Tất cả <span class="text-xs text-slate-400">{{ totalCount }}</span>
        </button>
        <button
            v-for="stage in stages"
            :key="stage"
            type="button"
            class="flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2"
            :class="form.stage === stage ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
            :aria-current="form.stage === stage ? 'page' : undefined"
            :data-stage="stage"
            @click="openStage(stage)"
        >
            {{ labels.stage[stage] ?? stage }}
            <span
                v-if="actionableStages.includes(stage) && stageCounts[stage] > 0"
                class="rounded-full bg-amber-100 px-1.5 text-xs font-medium text-amber-800"
                >{{ stageCounts[stage] }}</span
            >
            <span v-else class="text-xs text-slate-400">{{ stageCounts[stage] ?? 0 }}</span>
        </button>
    </nav>

    <form class="mb-4 space-y-2" @submit.prevent="search()">
        <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto]">
            <input v-model="form.q" :class="inputClass" placeholder="Số đơn hoặc SĐT" aria-label="Tìm đơn" />
            <button type="button" :class="secondaryButton" :aria-expanded="moreFilters" @click="moreFilters = !moreFilters">Bộ lọc</button>
            <button type="submit" :class="primaryButton">Lọc</button>
        </div>
        <div v-if="moreFilters" class="grid gap-2 sm:grid-cols-3 lg:grid-cols-6">
            <ExtensionFilters v-model="ext" :filters="extensions.filters" />
            <Select v-model="form.payment_method" :options="options(labels.payment_method, labels.payment_method)" empty-label="Mọi cách thanh toán" aria-label="Cách thanh toán" />
            <Select v-model="form.payment_status" :options="options(paymentStatuses, labels.payment_status)" empty-label="Mọi trạng thái thanh toán" aria-label="Trạng thái thanh toán" />
            <Select v-model="form.source" :options="options(labels.source, labels.source)" empty-label="Mọi nguồn đơn" aria-label="Nguồn đơn" />
            <Select v-model="form.status" :options="options(statuses, labels.order_status)" empty-label="Mọi trạng thái đơn" aria-label="Trạng thái đơn" />
            <DateInput v-model="form.from" aria-label="Đặt từ ngày" />
            <DateInput v-model="form.to" aria-label="Đặt đến ngày" />
        </div>
    </form>

    <div v-if="extensions.actions.length" class="mb-2 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500">Đã chọn {{ selected.length }}</span>
        <ExtensionActions :actions="extensions.actions" :ids="selected" />
    </div>
    <DataTable v-model:selected="selected" :columns="columns" :rows="orders" :extensions="extensions" :row-label="(order) => order.number" empty-text="Không có đơn nào.">
        <template #cell-number="{ row }">
            <Link :href="`${baseUrl}/${row.id}`" class="font-mono text-indigo-600 hover:underline">{{ row.number }}</Link>
            <div class="text-xs text-slate-400">{{ row.placed_at }}</div>
            <div class="text-xs" :class="ageClass(row)">{{ ageText(row.age_hours) }}<template v-if="row.source !== 'web'"> · {{ labels.source[row.source] ?? row.source }}</template></div>
        </template>
        <template #cell-customer="{ row }">
            {{ row.customer }}
            <div class="text-xs text-slate-400">{{ row.phone }}<template v-if="row.province"> · {{ row.province }}</template></div>
        </template>
        <template #cell-items="{ row }">
            <div v-for="item in row.items.slice(0, 2)" :key="item.sku" class="font-mono text-xs">{{ item.sku }} × {{ item.quantity }}</div>
            <div v-if="row.items.length > 2" class="text-xs text-slate-400">+{{ row.items.length - 2 }} sản phẩm</div>
        </template>
        <template #cell-total="{ row }"><MoneyText :amount="row.total" /></template>
        <template #cell-stage="{ row }">
            <StatusBadge :label="labels.stage[row.stage] ?? row.stage" :tone="stageTone(row.stage)" />
            <div class="mt-1 text-xs text-slate-400">{{ labels.order_status[row.order_status] ?? row.order_status }}</div>
        </template>
        <template #cell-payment_status="{ row }">
            <StatusBadge :label="labels.payment_status[row.payment_status] ?? row.payment_status" :tone="paymentStatusTone(row.payment_status)" />
            <div class="mt-1 text-xs text-slate-400">{{ labels.payment_method[row.payment_method] ?? row.payment_method }}</div>
        </template>
    </DataTable>

    <Pagination :current="pagination.current" :last="pagination.last" @change="search" />
</template>
