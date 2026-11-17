<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router } from '@inertiajs/vue3';
import { stateTone } from '../../priceListState';

type Overview = {
    running: Array<{ id: number; name: string; ends_at: string | null; ending_soon: boolean; prices: number }>;
    upcoming: Array<{ id: number; name: string; starts_at: string | null; prices: number }>;
    missing_base: { count: number; samples: Array<{ sku: string; product: string; url: string }> };
    warnings: { checked: number; capped: boolean; labels: Record<string, number>; samples: Array<{ sku: string; list: string; label: string }> };
};

const props = defineProps<{
    historyUrl: string;
    overview: Overview;
    baseUrl: string;
    labels: { price_list_type: Record<string, string>; price_list_state: Record<string, string> };
    priceLists: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        customer_group: string | null;
        priority: number;
        status: string;
        state: string;
        starts_at: string | null;
        ends_at: string | null;
        prices_count: number;
    }>;
    canManage: boolean;
    /** Bảng loại niêm yết ngoài bảng chính (dữ liệu cũ) — cần đổi sang Sale/Thành viên hoặc xoá. */
    extraBaseLists: Array<{ id: number; name: string; code: string }>;
}>();

const columns = [
    { key: 'name', label: 'Bảng giá' },
    { key: 'state', label: 'Trạng thái' },
    { key: 'type', label: 'Loại' },
    { key: 'period', label: 'Hiệu lực', class: 'text-xs' },
    { key: 'prices_count', label: 'Số SKU' },
    { key: 'actions', label: '', align: 'right' as const },
];
</script>

<template>
    <Head title="Bảng giá" />
    <p class="mb-2 text-sm text-slate-500">Giá bán</p>
    <PageHeader title="Bảng giá" subtitle="Bấm vào bảng giá để xem và sửa giá. Bảng giá ưu tiên cao hơn thắng; giá niêm yết làm giá gạch khi đang giảm.">
        <Link :href="historyUrl" :class="secondaryButton" data-history>Lịch sử giá</Link>
        <Link v-if="canManage" :href="`${baseUrl}/create`" :class="primaryButton">Thêm bảng giá</Link>
    </PageHeader>
    <section class="mb-6 grid gap-3 md:grid-cols-3" data-price-overview>
        <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
            <h2 class="mb-2 font-medium">Đang chạy</h2>
            <p v-if="!overview.running.length" class="text-slate-500">Không có đợt sale/thành viên nào đang chạy.</p>
            <ul v-else class="space-y-1">
                <li v-for="item in overview.running" :key="item.id">
                    <Link :href="`${baseUrl}/${item.id}/prices`" class="text-indigo-700 hover:underline">{{ item.name }}</Link>
                    <span class="text-slate-500"> · {{ item.prices }} SKU</span>
                    <span v-if="item.ends_at" :class="item.ending_soon ? 'font-medium text-amber-700' : 'text-slate-500'"> · hết {{ item.ends_at }}</span>
                </li>
            </ul>
            <template v-if="overview.upcoming.length">
                <h3 class="mt-3 mb-1 font-medium">Sắp bắt đầu (7 ngày)</h3>
                <ul class="space-y-1">
                    <li v-for="item in overview.upcoming" :key="item.id">
                        <Link :href="`${baseUrl}/${item.id}/prices`" class="text-indigo-700 hover:underline">{{ item.name }}</Link>
                        <span class="text-slate-500"> · {{ item.prices }} SKU · từ {{ item.starts_at }}</span>
                    </li>
                </ul>
            </template>
        </div>
        <div class="rounded-lg border bg-white p-4 text-sm" :class="overview.missing_base.count ? 'border-amber-300' : 'border-slate-200'" data-missing-base>
            <h2 class="mb-2 font-medium">SKU đang bán chưa có giá niêm yết: {{ overview.missing_base.count }}</h2>
            <p v-if="!overview.missing_base.count" class="text-slate-500">Mọi SKU đang bán đều có giá.</p>
            <ul v-else class="space-y-1">
                <li v-for="sample in overview.missing_base.samples" :key="sample.sku">
                    <Link :href="sample.url" class="font-mono text-xs text-indigo-700 hover:underline">{{ sample.sku }}</Link>
                    <span class="text-slate-500"> · {{ sample.product }}</span>
                </li>
            </ul>
        </div>
        <div class="rounded-lg border bg-white p-4 text-sm" :class="Object.keys(overview.warnings.labels).length ? 'border-red-300' : 'border-slate-200'" data-price-warnings>
            <h2 class="mb-2 font-medium">Cảnh báo trên giá đang chạy</h2>
            <p v-if="!Object.keys(overview.warnings.labels).length" class="text-slate-500">Không có cảnh báo ({{ overview.warnings.checked }} giá đã kiểm).</p>
            <template v-else>
                <ul class="mb-2 space-y-1">
                    <li v-for="(count, label) in overview.warnings.labels" :key="label" class="font-medium text-red-700">{{ label }}: {{ count }} SKU</li>
                </ul>
                <ul class="space-y-1 text-xs text-slate-600">
                    <li v-for="sample in overview.warnings.samples" :key="`${sample.sku}-${sample.list}-${sample.label}`"><span class="font-mono">{{ sample.sku }}</span> · {{ sample.list }}</li>
                </ul>
            </template>
            <p v-if="overview.warnings.capped" class="mt-2 text-xs text-slate-500">Chỉ kiểm {{ overview.warnings.checked }} giá đầu tiên.</p>
        </div>
    </section>
    <div v-if="extraBaseLists.length" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" data-extra-base>
        Cửa hàng chỉ dùng một bảng giá niêm yết (mã <span class="font-mono">base</span>). Các bảng sau đang là loại niêm yết thừa — mở và chọn
        <strong>Sale</strong> hoặc <strong>Thành viên</strong>, hoặc xoá nếu không dùng:
        <span v-for="(list, index) in extraBaseLists" :key="list.id"><template v-if="index">, </template><Link :href="`${baseUrl}/${list.id}/edit`" class="font-medium underline">{{ list.name }}</Link></span>.
    </div>
    <DataTable :columns="columns" :rows="priceLists" :row-label="(list) => list.name" :row-click="(list) => router.visit(`${props.baseUrl}/${list.id}/prices`)" empty-text="Chưa có bảng giá nào.">
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.code }}</div>
        </template>
        <template #cell-state="{ row }"><StatusBadge :label="labels.price_list_state[row.state] ?? row.state" :tone="stateTone(row.state)" /></template>
        <template #cell-type="{ row }">
            {{ labels.price_list_type[row.type] ?? row.type }}
            <div v-if="row.customer_group" class="text-xs text-slate-500">Nhóm: {{ row.customer_group }}</div>
        </template>
        <template #cell-period="{ row }">
            <template v-if="row.starts_at || row.ends_at">{{ row.starts_at ?? '—' }} → {{ row.ends_at ?? '—' }}</template>
            <span v-else class="text-slate-400">Không giới hạn</span>
        </template>
        <template #cell-actions="{ row }">
            <Link v-if="canManage" :href="`${baseUrl}/${row.id}/edit`" class="text-indigo-600 hover:underline" @click.stop>Thông tin</Link>
        </template>
    </DataTable>
</template>
