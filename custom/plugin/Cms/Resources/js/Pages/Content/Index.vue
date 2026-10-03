<script setup lang="ts">
import FlashMessage from "@admin/Components/FlashMessage.vue";
import PageHeader from "@admin/Components/PageHeader.vue";
import { inputClass, primaryButton, secondaryButton } from "@admin/styles";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { kindLabels, statusLabels, type Kind } from "./labels";

const props = defineProps<{
    kind: Kind;
    search: string;
    items: {
        data: Array<{
            id: number;
            title: string;
            slug: string;
            status: string;
            published_at: string | null;
            updated_at: string | null;
            url: string | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    can: { manage: boolean };
    urls: { pages: string; posts: string; index: string; create: string };
}>();

const q = ref(props.search);

function submitSearch(): void {
    router.get(props.urls.index, q.value ? { q: q.value } : {}, {
        preserveState: true,
    });
}
</script>

<template>
    <Head :title="`Nội dung — ${kindLabels[kind].plural}`" />
    <PageHeader
        title="Nội dung"
        subtitle="Trang (giới thiệu, chính sách…) và tin tức của cửa hàng. Soạn bằng Markdown."
    >
        <Link v-if="can.manage" :href="urls.create" :class="primaryButton"
            >Thêm {{ kindLabels[kind].singular }}</Link
        >
    </PageHeader>
    <FlashMessage />

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <Link
            v-for="(labels, key) in kindLabels"
            :key="key"
            :href="urls[key]"
            :class="[
                secondaryButton,
                kind === key ? 'border-indigo-500 text-indigo-700' : '',
            ]"
        >
            {{ labels.plural }}
        </Link>
        <form class="ml-auto flex gap-2" @submit.prevent="submitSearch">
            <input
                v-model="q"
                type="search"
                placeholder="Tìm theo tiêu đề"
                :class="[inputClass, 'w-56']"
            />
            <button type="submit" :class="secondaryButton">Tìm</button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="px-4 py-2 font-medium">Tiêu đề</th>
                    <th class="px-4 py-2 font-medium">Trạng thái</th>
                    <th class="px-4 py-2 font-medium">
                        {{ kind === "posts" ? "Ngày đăng" : "Cập nhật" }}
                    </th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="item in items.data"
                    :key="item.id"
                    class="border-b border-slate-100"
                >
                    <td class="px-4 py-2">
                        <Link
                            :href="`${urls.index}/${item.id}`"
                            class="font-medium text-indigo-700 hover:underline"
                            >{{ item.title }}</Link
                        >
                        <div class="text-xs text-slate-400">
                            /{{ kind === "pages" ? "trang" : "tin-tuc" }}/{{
                                item.slug
                            }}
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        <span
                            class="rounded px-2 py-0.5 text-xs"
                            :class="statusLabels[item.status]?.class"
                            >{{
                                statusLabels[item.status]?.label ?? item.status
                            }}</span
                        >
                    </td>
                    <td class="px-4 py-2 text-slate-600">
                        {{
                            (kind === "posts"
                                ? item.published_at
                                : item.updated_at) ?? "—"
                        }}
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a
                            v-if="item.url"
                            :href="item.url"
                            target="_blank"
                            rel="noopener"
                            class="text-xs text-indigo-600 hover:underline"
                            >Xem</a
                        >
                    </td>
                </tr>
                <tr v-if="!items.data.length">
                    <td
                        colspan="4"
                        class="px-4 py-6 text-center text-slate-400"
                    >
                        Chưa có {{ kindLabels[kind].singular }} nào.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <nav
        v-if="items.links.length > 3"
        class="mt-4 flex flex-wrap gap-1 text-sm"
    >
        <Link
            v-for="(link, index) in items.links"
            :key="index"
            :href="link.url ?? ''"
            class="rounded border px-3 py-1"
            :class="[
                link.active
                    ? 'border-indigo-500 text-indigo-700'
                    : 'border-slate-200',
                link.url ? '' : 'pointer-events-none opacity-40',
            ]"
            v-html="link.label"
        />
    </nav>
</template>
