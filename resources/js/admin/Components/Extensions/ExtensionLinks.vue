<script setup lang="ts">
import { secondaryButton } from '@admin/styles';
import type { ExtensionLink } from '@admin/types';
import { Link } from '@inertiajs/vue3';

/** Nút liên kết của plugin ở đầu trang sửa (`AdminScreen::links`), mở màn hình riêng của plugin cho bản ghi. */
defineProps<{ links: ExtensionLink[] }>();
</script>

<template>
    <Link v-for="link in links" :key="link.key" :href="link.url" :class="secondaryButton" :data-extension-link="link.key">{{ link.label }}</Link>
</template>
