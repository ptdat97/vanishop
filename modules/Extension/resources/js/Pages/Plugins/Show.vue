<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PluginIcon from '../../Components/PluginIcon.vue';
import { kindLabels, statusClasses, statusLabels } from '../../pluginLabels';

const props = defineProps<{
    baseUrl: string;
    canManage: boolean;
    canConfigure: boolean;
    plugin: {
        id: string;
        name: string;
        description: string | null;
        author: string | null;
        version: string;
        installed_version: string | null;
        installed_at: string | null;
        kind: string;
        bundled: boolean;
        status: string;
        error: string | null;
        health: { status: 'ok' | 'warning' | 'error'; message: string; checked_at: string } | null;
        has_icon: boolean;
        has_settings: boolean;
        path: string;
        requires_core: string;
        requires_plugins: Array<{ id: string; constraint: string; name: string | null; status: string }>;
        dependents: string[];
        conflicts: string[];
        capabilities: string[];
        permissions: string[];
        data: { owned: string[]; references: Record<string, string>; retained: string[] };
        problems: string[];
        in_use: string[];
        hooks: string[];
        admin_pages: Array<{ label: string; url: string }>;
        actions: string[];
    };
}>();

const errors = computed(() => usePage().props.errors as Record<string, string>);
const url = (action: string): string => `${props.baseUrl}/${props.plugin.id}/${action}`;
const can = (action: string): boolean => props.canManage && props.plugin.actions.includes(action);
const busy = ref(false);

function post(action: string, data: Record<string, string | boolean> = {}): void {
    router.post(url(action), data, { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) });
}

// Tắt: còn việc dở dang → chọn ngừng an toàn (drain) hoặc tắt ngay (force, phải tick xác nhận).
const confirmForce = ref(false);
// Gỡ: xoá dữ liệu phải gõ lại id.
const purge = ref(false);
const dropRetained = ref(false);
const confirmId = ref('');

function uninstall(): void {
    if (!purge.value && !window.confirm(`Gỡ ${props.plugin.name}? Dữ liệu của plugin được giữ lại.`)) return;
    post('uninstall', { purge: purge.value, drop_retained: purge.value && dropRetained.value, confirm_id: confirmId.value });
}

const formatDate = (value: string | null): string => (value ? new Date(value).toLocaleString('vi-VN') : '—');
</script>

<template>
    <Head :title="`Plugin: ${plugin.name}`" />
    <PageHeader :title="plugin.name" :subtitle="plugin.id">
        <Link :href="baseUrl" :class="secondaryButton">Danh sách plugin</Link>
        <Link v-if="plugin.has_settings && canConfigure" :href="url('settings')" :class="primaryButton">Cấu hình</Link>
    </PageHeader>

    <p v-if="errors.business" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm whitespace-pre-line text-red-700">{{ errors.business }}</p>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="space-y-4 lg:col-span-2">
            <div class="flex gap-4 rounded-lg border border-slate-200 bg-white p-4">
                <PluginIcon :src="plugin.has_icon ? url('icon') : null" :name="plugin.name" size="lg" />
                <div class="text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="[statusClasses[plugin.status], 'rounded px-2 py-0.5 text-xs']">{{ statusLabels[plugin.status] ?? plugin.status }}</span>
                        <span class="text-slate-500">{{ kindLabels[plugin.kind] ?? plugin.kind }}</span>
                        <span v-if="plugin.bundled" class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">Hệ thống</span>
                    </div>
                    <p v-if="plugin.description" class="mt-2 text-slate-700">{{ plugin.description }}</p>
                    <p v-if="plugin.error" class="mt-2 text-red-600">{{ plugin.error }}</p>
                    <p v-if="plugin.health" class="mt-2" :class="{ 'text-red-600': plugin.health.status === 'error', 'text-amber-700': plugin.health.status === 'warning', 'text-emerald-700': plugin.health.status === 'ok' }">
                        Sức khoẻ: {{ plugin.health.message || 'Ổn định' }} <span class="text-xs text-slate-400">({{ formatDate(plugin.health.checked_at) }})</span>
                    </p>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-2 rounded-lg border border-slate-200 bg-white p-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Phiên bản</dt><dd>{{ plugin.installed_version ?? plugin.version }}<span v-if="plugin.installed_version && plugin.installed_version !== plugin.version" class="text-indigo-600"> → {{ plugin.version }}</span></dd></div>
                <div><dt class="text-slate-500">Tác giả</dt><dd>{{ plugin.author ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Cài lúc</dt><dd>{{ formatDate(plugin.installed_at) }}</dd></div>
                <div><dt class="text-slate-500">Yêu cầu Core</dt><dd class="font-mono">{{ plugin.requires_core }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Thư mục</dt><dd class="font-mono text-xs">{{ plugin.path }}</dd></div>
            </dl>

            <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="font-semibold">Phụ thuộc</h2>
                <div>
                    <div class="text-slate-500">Cần plugin</div>
                    <ul v-if="plugin.requires_plugins.length" class="list-inside list-disc">
                        <li v-for="dependency in plugin.requires_plugins" :key="dependency.id">
                            <Link :href="`${baseUrl}/${dependency.id}`" class="text-indigo-600 hover:underline">{{ dependency.name ?? dependency.id }}</Link>
                            <span class="font-mono text-xs"> {{ dependency.constraint }}</span> · {{ statusLabels[dependency.status] ?? dependency.status }}
                        </li>
                    </ul>
                    <p v-else>—</p>
                </div>
                <div v-if="plugin.capabilities.length">
                    <div class="text-slate-500">Cần có ít nhất một implementation đang bật</div>
                    <p class="font-mono text-xs">{{ plugin.capabilities.join(', ') }}</p>
                </div>
                <div>
                    <div class="text-slate-500">Plugin phụ thuộc vào plugin này</div>
                    <p>
                        <template v-for="(id, index) in plugin.dependents" :key="id"><span v-if="index">, </span><Link :href="`${baseUrl}/${id}`" class="text-indigo-600 hover:underline">{{ id }}</Link></template>
                        <span v-if="!plugin.dependents.length">—</span>
                    </p>
                </div>
                <div v-if="plugin.conflicts.length"><div class="text-slate-500">Xung đột với</div><p class="font-mono text-xs">{{ plugin.conflicts.join(', ') }}</p></div>
                <ul v-if="plugin.problems.length" class="list-inside list-disc text-red-600">
                    <li v-for="problem in plugin.problems" :key="problem">{{ problem }}</li>
                </ul>
            </div>

            <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <h2 class="font-semibold">Plugin dùng gì</h2>
                <div><div class="text-slate-500">Quyền khai báo</div><p class="font-mono text-xs">{{ plugin.permissions.join(', ') || '—' }}</p></div>
                <div>
                    <div class="text-slate-500">Hook đang nghe</div>
                    <p v-if="plugin.hooks.length" class="font-mono text-xs">{{ plugin.hooks.join(', ') }}</p>
                    <p v-else>{{ ['enabled', 'draining'].includes(plugin.status) ? '—' : 'Chỉ biết khi plugin đang bật.' }}</p>
                </div>
                <div>
                    <div class="text-slate-500">Bảng dữ liệu</div>
                    <p v-if="plugin.data.owned.length" class="font-mono text-xs">
                        {{ plugin.data.owned.map((table) => (plugin.data.retained.includes(table) ? `${table} (lưu giữ)` : table)).join(', ') }}
                    </p>
                    <p v-else>—</p>
                </div>
                <div v-if="plugin.admin_pages.length">
                    <div class="text-slate-500">Màn hình Admin</div>
                    <Link v-for="page in plugin.admin_pages" :key="page.url" :href="page.url" class="mr-3 text-indigo-600 hover:underline">{{ page.label }}</Link>
                </div>
            </div>
        </section>

        <aside v-if="canManage" class="space-y-3 text-sm">
            <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="font-semibold">Thao tác</h2>
                <button v-if="can('install')" :class="[primaryButton, 'w-full']" :disabled="busy || plugin.problems.length > 0" @click="post('install')">Cài đặt</button>
                <button v-if="can('upgrade')" :class="[primaryButton, 'w-full']" :disabled="busy" @click="post('upgrade')">Nâng cấp lên {{ plugin.version }}</button>
                <button v-if="can('enable')" :class="[primaryButton, 'w-full']" :disabled="busy" @click="post('enable')">{{ plugin.status === 'draining' ? 'Huỷ ngừng (bật lại)' : 'Bật' }}</button>

                <template v-if="can('disable')">
                    <template v-if="plugin.in_use.length">
                        <div class="rounded-md bg-amber-50 p-3 text-amber-800">
                            <p class="font-medium">Còn việc dở dang:</p>
                            <ul class="list-inside list-disc">
                                <li v-for="reason in plugin.in_use" :key="reason">{{ reason }}</li>
                            </ul>
                        </div>
                        <button v-if="plugin.status === 'enabled'" :class="[secondaryButton, 'w-full']" :disabled="busy" @click="post('disable', { mode: 'drain' })">Ngừng an toàn (tự tắt khi xong)</button>
                        <label class="flex items-start gap-2 text-xs text-red-700">
                            <input v-model="confirmForce" type="checkbox" class="mt-0.5" />
                            Tôi hiểu: tắt ngay thì callback/webhook của việc dở dang sẽ không được xử lý tự động.
                        </label>
                        <button :class="[dangerButton, 'w-full']" :disabled="busy || !confirmForce" @click="post('disable', { mode: 'force', confirm_force: true })">Tắt ngay</button>
                    </template>
                    <button v-else :class="[secondaryButton, 'w-full']" :disabled="busy" @click="post('disable', { mode: 'normal' })">Tắt</button>
                </template>

                <template v-if="can('uninstall')">
                    <div class="space-y-2 border-t border-slate-100 pt-3">
                        <label class="flex items-start gap-2"><input v-model="purge" type="checkbox" class="mt-0.5" /> Xoá cả dữ liệu của plugin</label>
                        <template v-if="purge">
                            <label v-if="plugin.data.retained.length" class="flex items-start gap-2 text-xs"><input v-model="dropRetained" type="checkbox" class="mt-0.5" /> Xoá cả bảng lưu giữ ({{ plugin.data.retained.join(', ') }}) — chỉ khi đã xuất/lưu trữ</label>
                            <input v-model="confirmId" :class="inputClass" :placeholder="`Gõ ${plugin.id} để xác nhận`" />
                            <p v-if="errors.confirm_id" class="text-xs text-red-600">{{ errors.confirm_id }}</p>
                        </template>
                        <button :class="[dangerButton, 'w-full']" :disabled="busy || (purge && confirmId !== plugin.id)" @click="uninstall">Gỡ plugin</button>
                    </div>
                </template>
                <p v-if="!plugin.actions.length" class="text-slate-500">Không có thao tác.</p>
            </div>
            <p class="text-xs text-slate-500">Cùng kiểm tra và audit với lệnh <code>vani:plugin:*</code>. Cài và nâng cấp chạy migration của plugin.</p>
        </aside>
    </div>
</template>
