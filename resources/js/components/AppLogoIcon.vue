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
    <img src="/images/logo-prefeitura.png" alt="Lista da regulação Logo" :class="className" v-bind="$attrs" class="rounded-md object-contain" />
</template>
