<script setup lang="ts">
import { useMoney } from '@admin/money';

/** Số tiền (minor unit) theo định dạng của cửa hàng; không ghi cứng ngôn ngữ hay ký hiệu tiền tệ. */
defineProps<{ amount: number }>();

const money = useMoney();
</script>

<template>
    <span class="tabular-nums">{{ money(amount) }}</span>
</template>
