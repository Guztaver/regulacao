<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
  placeholder?: string
  disabled?: boolean
  required?: boolean
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <select
    v-model="modelValue"
    data-slot="select"
    :disabled="disabled"
    :required="required"
    :class="cn(
      'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
      // Select-specific styling
      'bg-white dark:bg-gray-700 appearance-none cursor-pointer',
      // Add a custom dropdown arrow
      "bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 20 20\"%3E%3Cpath stroke=\"%236b7280\" stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"m6 8 4 4 4-4\"/%3E%3C/svg%3E')] bg-[length:1.5em_1.5em] bg-[right_0.5rem_center] bg-no-repeat pr-10",
      // Dark mode arrow
      "dark:bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 20 20\"%3E%3Cpath stroke=\"%239ca3af\" stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"m6 8 4 4 4-4\"/%3E%3C/svg%3E')]",
      props.class,
    )"
  >
    <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
    <slot />
  </select>
</template>
