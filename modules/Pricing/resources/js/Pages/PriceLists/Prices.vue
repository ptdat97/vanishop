<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import Dialog from '@admin/Components/Dialog.vue';
import MoneyText from '@admin/Components/MoneyText.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Pagination from '@admin/Components/Pagination.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { getJson, HttpError, postJson } from '@admin/http';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { toast } from '@admin/toast';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { stateTone, warningTone } from '../../priceListState';

/**
 * Trang làm việc của bảng giá (thao tác gọn — đợt 1): mọi SKU trong bảng, sửa giá trong ô, chọn nhiều để áp giá hàng loạt
 * (giảm %, giảm số tiền, đồng giá — tính từ giá niêm yết, có xem trước) hoặc gỡ; thêm sản phẩm bằng tìm theo tên/mã/SKU.
 * Cảnh báo (0đ, không thấp hơn giá niêm yết, giảm vượt mức trần) chỉ để nhắc, không chặn lưu.
 */
interface Row {
    variant_id: number;
    style_code: string;
    name: string;
    sku: string;
    variant_label: string;
    status: string;
    list_amount: number | null;
    amount: number | null;
    compare_at_amount: number | null;
    percent: number | null;
    warnings: string[];
}
interface PreviewRow {
    variant_id: number;
    name: string;
    sku: string;
    variant_label: string;
    list_amount: number | null;
    current_amount: number | null;
    new_amount: number | null;
    percent: number | null;
    warnings: string[];
}
interface Candidate {
    style_id: number;
    style_code: string;
    name: string;
    variants: number;
    in_list: number;
    list_min: number | null;
    list_max: number | null;
}
type Scope = { scope: 'variants'; variant_ids: number[] } | { scope: 'styles'; style_ids: number[] } | { scope: 'all'; q: string };

const props = defineProps<{
    baseUrl: string;
    labels: {
        price_list_type: Record<string, string>;
        price_list_state: Record<string, string>;
        bulk: { modes: Record<string, string>; warnings: Record<string, string> };
    };
    priceList: { id: number; code: string; name: string; type: string; state: string; starts_at: string | null; ends_at: string | null };
    q: string;
    rows: Row[];
    pagination: { page: number; last_page: number; from: number | null; to: number | null; total: number; per_page: number };
    perPageOptions: number[];
    summary: { products: number; variants: number; warnings: Record<string, number> };
    capPercent: number;
    canManage: boolean;
    /** Bảng giá niêm yết: chỉ xem — giá niêm yết sửa ở sản phẩm. */
    readOnly: boolean;
    productsUrl: string;
    /** Từ danh sách sản phẩm "Đưa vào bảng giá…": mở sẵn hộp thoại cách tính giá cho các sản phẩm này. */
    addStyleIds: number[];
}>();
const editable = computed(() => props.canManage && !props.readOnly);

const listUrl = computed(() => `${props.baseUrl}/${props.priceList.id}`);
const isBase = computed(() => props.priceList.type === 'base');
const search = ref(props.q);
const selected = ref<number[]>([]);

function visit(page?: number, perPage = props.pagination.per_page): void {
    router.get(`${listUrl.value}/prices`, { q: search.value.trim() || undefined, page, per_page: perPage === props.perPageOptions[0] ? undefined : perPage }, { preserveState: true, preserveScroll: true });
}

// Sửa giá trong ô: chỉ gửi các dòng đã đổi.
const edits = reactive<Record<number, { amount: number | null; compare_at_amount: number | null }>>({});
watch(
    () => props.rows,
    (rows) => {
        Object.keys(edits).forEach((key) => delete edits[Number(key)]);
        rows.forEach((row) => (edits[row.variant_id] = { amount: row.amount, compare_at_amount: row.compare_at_amount }));
    },
    { immediate: true },
);
const blank = (value: unknown): number | null => (value === '' || value === null || value === undefined ? null : Number(value));
const dirtyRows = computed(() =>
    props.rows
        .filter((row) => blank(edits[row.variant_id]?.amount) !== row.amount || blank(edits[row.variant_id]?.compare_at_amount) !== row.compare_at_amount)
        .map((row) => ({ variant_id: row.variant_id, amount: blank(edits[row.variant_id].amount), compare_at_amount: blank(edits[row.variant_id].compare_at_amount) })),
);
const saveForm = useForm({ prices: [] as Array<{ variant_id: number; amount: number | null; compare_at_amount: number | null }> });
function saveEdits(): void {
    saveForm.transform(() => ({ prices: dirtyRows.value })).put(`${listUrl.value}/prices`, { preserveScroll: true });
}

async function removeSelected(ids: number[], clear: () => void): Promise<void> {
    if ((await confirmAction({ title: `Gỡ ${ids.length} SKU khỏi bảng giá?`, message: 'Khách sẽ thấy giá của bảng giá khác (thường là giá niêm yết). Lịch sử giá vẫn giữ.', confirmLabel: 'Gỡ', tone: 'danger' })) !== false) {
        router.post(`${listUrl.value}/remove`, { variant_ids: ids }, { preserveScroll: true, onSuccess: clear });
    }
}

// Áp giá hàng loạt: chọn cách tính → xem trước → áp dụng.
const bulkOpen = ref(false);
const bulkScope = ref<Scope | null>(null);
const bulkTitle = ref('');
const rule = reactive({ mode: 'percent' as 'percent' | 'amount' | 'fixed', value: null as number | null, round: true });
const preview = ref<{ rows: PreviewRow[]; summary: { total: number; changed: number; skipped: number; warnings: Record<string, number> } } | null>(null);
const previewing = ref(false);
const applying = ref(false);
const ruleError = ref('');
const payload = () => ({ ...(bulkScope.value as Scope), mode: rule.mode, value: rule.value ?? 0, round_to: rule.round && rule.mode !== 'fixed' ? 1000 : 0 });

function openBulk(scope: Scope, title: string): void {
    bulkScope.value = scope;
    bulkTitle.value = title;
    preview.value = null;
    ruleError.value = '';
    bulkOpen.value = true;
}
watch(rule, () => (preview.value = null));
// Đến từ danh sách sản phẩm ("Đưa vào bảng giá…"): mở sẵn hộp thoại; khai báo sau các biến của hộp thoại vì chạy ngay.
watch(
    () => props.addStyleIds,
    (ids) => {
        if (ids.length && editable.value) {
            openBulk({ scope: 'styles', style_ids: [...ids] }, `Đưa ${ids.length} sản phẩm vào "${props.priceList.name}"`);
            router.replace({ url: `${listUrl.value}/prices`, preserveState: true, preserveScroll: true });
        }
    },
    { immediate: true, flush: 'post' },
);

async function loadPreview(): Promise<void> {
    if (rule.value === null || Number.isNaN(rule.value)) {
        ruleError.value = 'Nhập giá trị.';
        return;
    }
    previewing.value = true;
    ruleError.value = '';
    try {
        preview.value = (await postJson<{ data: NonNullable<typeof preview.value> }>(`${listUrl.value}/bulk/preview`, payload())).data;
    } catch (error) {
        ruleError.value = error instanceof HttpError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : 'Không xem trước được.';
    } finally {
        previewing.value = false;
    }
}

function applyBulk(): void {
    applying.value = true;
    router.post(`${listUrl.value}/bulk`, payload(), {
        preserveScroll: true,
        onSuccess: () => {
            bulkOpen.value = false;
            selected.value = [];
        },
        onFinish: () => (applying.value = false),
    });
}

// Thêm sản phẩm: tìm theo tên/mã/SKU, chọn nhiều → sang bước cách tính giá.
const addOpen = ref(false);
const addSearch = ref('');
const candidates = ref<Candidate[]>([]);
const chosen = ref<number[]>([]);
const searching = ref(false);
const searchedFor = ref('');
async function findCandidates(): Promise<void> {
    const text = addSearch.value.trim();
    if (!text || text === searchedFor.value) return;
    searching.value = true;
    try {
        candidates.value = (await getJson<{ data: Candidate[] }>(`${listUrl.value}/candidates`, { q: text })).data;
        searchedFor.value = text;
    } catch (error) {
        toast(error instanceof HttpError ? error.message : 'Không tìm được sản phẩm.', 'error');
    } finally {
        searching.value = false;
    }
}
// Tự tìm khi ngừng gõ (không cần bấm Tìm).
let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(addSearch, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(findCandidates, 300);
});
function toggleAllCandidates(): void {
    const ids = candidates.value.map((candidate) => candidate.style_id);
    chosen.value = ids.every((id) => chosen.value.includes(id)) ? chosen.value.filter((id) => !ids.includes(id)) : [...new Set([...chosen.value, ...ids])];
}
function continueToRule(): void {
    addOpen.value = false;
    openBulk({ scope: 'styles', style_ids: [...chosen.value] }, `Thêm ${chosen.value.length} sản phẩm vào "${props.priceList.name}"`);
    chosen.value = [];
}
const range = (candidate: Candidate): string | null => (candidate.list_min === null ? null : candidate.list_min === candidate.list_max ? `${candidate.list_min}` : `${candidate.list_min}–${candidate.list_max}`);

const columns = computed(() => [
    { key: 'name', label: 'Sản phẩm / SKU' },
    ...(isBase.value ? [] : [{ key: 'list_amount', label: 'Giá niêm yết', align: 'right' as const }]),
    { key: 'amount', label: isBase.value ? 'Giá niêm yết' : 'Giá trong bảng này' },
    ...(isBase.value ? [] : [{ key: 'percent', label: 'Giảm' }]),
    { key: 'compare_at_amount', label: 'Giá gạch (tuỳ chọn)' },
]);
const previewColumns = [
    { key: 'name', label: 'SKU' },
    { key: 'list_amount', label: 'Niêm yết', align: 'right' as const },
    { key: 'current_amount', label: 'Hiện tại', align: 'right' as const },
    { key: 'new_amount', label: 'Mới', align: 'right' as const },
    { key: 'warnings', label: '' },
];
const warningCount = computed(() => Object.values(props.summary.warnings).reduce((sum, count) => sum + count, 0));
const warningLabel = (warning: string): string => (warning === 'over_cap' ? `Giảm trên ${props.capPercent}%` : (props.labels.bulk.warnings[warning] ?? warning));
</script>

<template>
    <Head :title="`Bảng giá · ${priceList.name}`" />
    <p class="mb-2 text-sm text-slate-500"><Link :href="baseUrl" class="hover:underline">Bảng giá</Link></p>
    <PageHeader :title="priceList.name" :subtitle="`${labels.price_list_type[priceList.type] ?? priceList.type} · ${priceList.starts_at || priceList.ends_at ? `${priceList.starts_at ?? '—'} → ${priceList.ends_at ?? '—'}` : 'không giới hạn thời gian'}`">
        <StatusBadge :label="labels.price_list_state[priceList.state] ?? priceList.state" :tone="stateTone(priceList.state)" class="self-center" />
        <Link v-if="canManage" :href="`${listUrl}/edit`" :class="secondaryButton">Thông tin bảng giá</Link>
        <button v-if="editable" type="button" :class="primaryButton" data-add-products @click="addOpen = true">Thêm sản phẩm</button>
        <Link v-if="readOnly" :href="productsUrl" :class="primaryButton">Sửa giá ở danh sách sản phẩm</Link>
    </PageHeader>

    <div v-if="readOnly" class="mb-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600" data-read-only>
        Đây là giá bán thường của cửa hàng — chỉ xem ở đây. Sửa giá ở form sản phẩm, hoặc sửa trong ô / "Đổi giá" hàng loạt ở danh sách sản phẩm, để mỗi giá chỉ có một chỗ sửa.
    </div>
    <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600" data-summary>
        <span><strong>{{ summary.products }}</strong> sản phẩm · <strong>{{ summary.variants }}</strong> SKU</span>
        <span v-for="(count, warning) in summary.warnings" :key="warning"><StatusBadge :label="`${warningLabel(String(warning))}: ${count}`" :tone="warningTone(String(warning))" /></span>
        <span v-if="!isBase && warningCount === 0 && summary.variants" class="text-xs text-slate-400">Không có cảnh báo</span>
    </div>

    <form class="mb-3 flex max-w-lg gap-2" @submit.prevent="visit()">
        <input v-model="search" :class="inputClass" type="search" placeholder="Tìm theo tên, mã sản phẩm hoặc SKU trong bảng giá" aria-label="Tìm trong bảng giá" />
        <button type="submit" :class="secondaryButton">Tìm</button>
    </form>

    <DataTable
        v-model:selected="selected"
        :columns="columns"
        :rows="rows"
        :selectable="editable"
        :extension-id="(row) => row.variant_id"
        :row-key="(row) => row.variant_id"
        :row-label="(row) => row.sku"
        :row-class="(row) => (row.status !== 'active' ? 'align-top text-slate-400' : 'align-top')"
        :empty-text="q ? 'Không có SKU nào khớp trong bảng giá này.' : readOnly ? 'Chưa có giá niêm yết nào.' : 'Bảng giá chưa có sản phẩm. Bấm “Thêm sản phẩm”.'"
    >
        <template #bulk="{ selected: ids, clear }">
            <button type="button" :class="secondaryButton" class="!py-1" @click="openBulk({ scope: 'variants', variant_ids: [...ids] }, `Áp giá cho ${ids.length} SKU đã chọn`)">Áp giá…</button>
            <button type="button" :class="secondaryButton" class="!py-1" @click="removeSelected([...ids], clear)">Gỡ khỏi bảng giá</button>
            <button v-if="pagination.total > ids.length" type="button" class="text-indigo-700 hover:underline" @click="openBulk({ scope: 'all', q }, `Áp giá cho toàn bộ ${pagination.total} SKU${q ? ' khớp tìm kiếm' : ''}`)">
                Áp giá cho toàn bộ {{ pagination.total }} SKU{{ q ? ' khớp tìm kiếm' : '' }}
            </button>
        </template>
        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-slate-400">{{ row.sku }}<template v-if="row.variant_label"> · {{ row.variant_label }}</template></div>
        </template>
        <template #cell-list_amount="{ row }"><MoneyText v-if="row.list_amount !== null" :amount="row.list_amount" /><span v-else class="text-xs text-slate-400">chưa có</span></template>
        <template #cell-amount="{ row }">
            <input v-model.number="edits[row.variant_id].amount" :class="inputClass" class="max-w-36" type="number" min="0" step="1000" :disabled="!editable" :aria-label="`Giá ${row.sku}`" />
        </template>
        <template #cell-percent="{ row }">
            <span v-if="row.percent !== null" class="font-medium text-emerald-700">−{{ row.percent }}%</span>
            <div v-for="warning in row.warnings" :key="warning" class="mt-0.5"><StatusBadge :label="warningLabel(warning)" :tone="warningTone(warning)" /></div>
        </template>
        <template #cell-compare_at_amount="{ row }">
            <input v-model.number="edits[row.variant_id].compare_at_amount" :class="inputClass" class="max-w-36" type="number" min="0" step="1000" :disabled="!editable" :aria-label="`Giá gạch ${row.sku}`" />
        </template>
    </DataTable>
    <p v-for="(message, key) in saveForm.errors" :key="key" class="mt-2 text-sm text-red-600">{{ message }}</p>
    <div v-if="editable && dirtyRows.length" class="sticky bottom-0 mt-3 flex items-center gap-3 border-t border-slate-200 bg-white/95 py-3">
        <button type="button" :class="primaryButton" :disabled="saveForm.processing" data-save-edits @click="saveEdits">Lưu {{ dirtyRows.length }} giá đã sửa</button>
        <span class="text-xs text-slate-500">Để trống giá = gỡ SKU khỏi bảng giá. Mọi thay đổi được ghi vào lịch sử giá.</span>
    </div>
    <Pagination :current="pagination.page" :last="pagination.last_page" :from="pagination.from" :to="pagination.to" :total="pagination.total" :per-page="pagination.per_page" :per-page-options="perPageOptions" @change="(page) => visit(page)" @per-page-change="(perPage) => visit(undefined, perPage)" />

    <Dialog v-model:open="addOpen" title="Thêm sản phẩm vào bảng giá" size="large">
        <form class="mb-3" @submit.prevent="findCandidates">
            <input v-model="addSearch" :class="inputClass" type="search" placeholder="Gõ tên, mã sản phẩm hoặc SKU — vd. đầm, VS-DSC" aria-label="Tìm sản phẩm" />
        </form>
        <div v-if="candidates.length" class="max-h-96 overflow-y-auto rounded-md border border-slate-200">
            <label class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                <input type="checkbox" :checked="candidates.every((candidate) => chosen.includes(candidate.style_id))" @change="toggleAllCandidates" />
                Chọn tất cả {{ candidates.length }} kết quả
            </label>
            <label v-for="candidate in candidates" :key="candidate.style_id" class="flex items-center gap-3 border-b border-slate-100 px-3 py-2 text-sm last:border-0">
                <input v-model="chosen" type="checkbox" :value="candidate.style_id" :aria-label="`Chọn ${candidate.name}`" :data-candidate="candidate.style_code" />
                <span class="flex-1">
                    <span class="font-medium">{{ candidate.name }}</span>
                    <span class="ml-2 font-mono text-xs text-slate-400">{{ candidate.style_code }}</span>
                    <span class="block text-xs text-slate-500">
                        {{ candidate.variants }} SKU<template v-if="candidate.in_list"> · {{ candidate.in_list }} đã có trong bảng</template>
                        <template v-if="range(candidate)"> · niêm yết <MoneyText v-if="candidate.list_min === candidate.list_max" :amount="candidate.list_min!" /><template v-else><MoneyText :amount="candidate.list_min!" />–<MoneyText :amount="candidate.list_max!" /></template></template>
                    </span>
                </span>
            </label>
        </div>
        <p v-else-if="searching" class="text-sm text-slate-500">Đang tìm…</p>
        <p v-else-if="searchedFor" class="text-sm text-slate-500">Không tìm thấy sản phẩm nào khớp “{{ searchedFor }}”.</p>
        <p v-else class="text-sm text-slate-500">Gõ tên, mã sản phẩm hoặc SKU để tìm. Chọn được nhiều sản phẩm một lần.</p>
        <p v-if="chosen.length" class="mt-2 text-sm text-indigo-700">Đã chọn {{ chosen.length }} sản phẩm (giữ lựa chọn khi tìm tiếp).</p>
        <template #footer>
            <button type="button" :class="primaryButton" :disabled="!chosen.length" data-continue @click="continueToRule">Tiếp: cách tính giá ({{ chosen.length }} sản phẩm)</button>
        </template>
    </Dialog>

    <Dialog v-model:open="bulkOpen" :title="bulkTitle" size="large">
        <div class="space-y-4">
            <fieldset class="flex flex-wrap gap-2" aria-label="Cách tính">
                <label v-for="(label, mode) in labels.bulk.modes" :key="mode" class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm" :class="rule.mode === mode ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                    <input v-model="rule.mode" type="radio" :value="mode" /> {{ label }}
                </label>
            </fieldset>
            <div class="flex flex-wrap items-center gap-3">
                <input v-model.number="rule.value" :class="inputClass" class="max-w-44" type="number" min="0" :step="rule.mode === 'percent' ? 1 : 1000" :aria-label="labels.bulk.modes[rule.mode]" :placeholder="rule.mode === 'percent' ? 'vd. 30' : 'vd. 199000'" />
                <span class="text-sm text-slate-500">{{ rule.mode === 'percent' ? '% so với giá niêm yết' : rule.mode === 'amount' ? 'đ bớt khỏi giá niêm yết' : 'đ cho mọi SKU' }}</span>
                <label v-if="rule.mode !== 'fixed'" class="flex items-center gap-2 text-sm"><input v-model="rule.round" type="checkbox" /> Làm tròn tới 1.000đ</label>
            </div>
            <p v-if="ruleError" class="text-sm text-red-600">{{ ruleError }}</p>
            <button type="button" :class="secondaryButton" :disabled="previewing" data-preview @click="loadPreview">Xem trước</button>

            <div v-if="preview" class="space-y-2" data-preview-result>
                <p class="text-sm">
                    <strong>{{ preview.summary.changed }}</strong> SKU đổi giá / {{ preview.summary.total }} SKU
                    <template v-if="preview.summary.skipped"> · bỏ qua {{ preview.summary.skipped }} (chưa có giá niêm yết)</template>
                </p>
                <div class="flex flex-wrap gap-2">
                    <StatusBadge v-for="(count, warning) in preview.summary.warnings" :key="warning" :label="`${warningLabel(String(warning))}: ${count}`" :tone="warningTone(String(warning))" />
                </div>
                <p v-if="Object.keys(preview.summary.warnings).some((warning) => warning !== 'no_base')" class="text-xs text-slate-500">Cảnh báo chỉ để nhắc — vẫn áp dụng được (vd. giá 0đ cho quà tặng).</p>
                <div class="max-h-72 overflow-y-auto rounded-md border border-slate-200">
                    <DataTable :columns="previewColumns" :rows="preview.rows" :row-key="(row) => row.variant_id" bare>
                        <template #cell-name="{ row }"><div>{{ row.name }}</div><div class="font-mono text-xs text-slate-400">{{ row.sku }}</div></template>
                        <template #cell-list_amount="{ row }"><MoneyText v-if="row.list_amount !== null" :amount="row.list_amount" /><template v-else>—</template></template>
                        <template #cell-current_amount="{ row }"><MoneyText v-if="row.current_amount !== null" :amount="row.current_amount" class="text-slate-500" /><template v-else>—</template></template>
                        <template #cell-new_amount="{ row }">
                            <span class="font-medium"><MoneyText v-if="row.new_amount !== null" :amount="row.new_amount" /><template v-else>—</template></span>
                            <span v-if="row.percent !== null" class="ml-1 text-xs text-emerald-700">−{{ row.percent }}%</span>
                        </template>
                        <template #cell-warnings="{ row }"><StatusBadge v-for="warning in row.warnings" :key="warning" :label="warningLabel(warning)" :tone="warningTone(warning)" /></template>
                    </DataTable>
                </div>
                <p v-if="preview.summary.total > preview.rows.length" class="text-xs text-slate-500">Hiện {{ preview.rows.length }} dòng đầu; tóm tắt tính trên cả {{ preview.summary.total }} SKU.</p>
            </div>
        </div>
        <template #footer>
            <button type="button" :class="primaryButton" :disabled="!preview || applying || preview.summary.changed === 0" data-apply @click="applyBulk">Áp dụng cho {{ preview?.summary.changed ?? 0 }} SKU</button>
        </template>
    </Dialog>
</template>
