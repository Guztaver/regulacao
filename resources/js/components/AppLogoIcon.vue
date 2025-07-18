<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img src="/images/logo.png" alt="Regulação PB Logo" :class="className" v-bind="$attrs" class="object-contain" />
</template>
