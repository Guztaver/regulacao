<script setup lang="ts">
import { ref, computed } from 'vue'
import { Calendar } from 'lucide-vue-next'
import { Calendar as CalendarComponent } from '@/components/ui/calendar'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog'
import { cn } from '@/lib/utils'

interface Props {
  modelValue?: string
  placeholder?: string
  disabled?: boolean
  minDate?: string
  maxDate?: string
  class?: string
}

interface Emits {
  'update:modelValue': [value: string]
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Selecione uma data'
})

const emit = defineEmits<Emits>()

const isOpen = ref(false)

const formattedDate = computed(() => {
  if (!props.modelValue) return props.placeholder

  try {
    const date = new Date(props.modelValue + 'T00:00:00')
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return props.placeholder
  }
})

function handleDateSelect(value: string) {
  emit('update:modelValue', value)
  isOpen.value = false
}

function clearDate() {
  emit('update:modelValue', '')
  isOpen.value = false
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !props.modelValue && 'text-muted-foreground',
          props.class
        )"
        :disabled="props.disabled"
      >
        <Calendar class="mr-2 h-4 w-4" />
        {{ formattedDate }}
      </Button>
    </DialogTrigger>

    <DialogContent class="w-auto max-w-fit">
      <div class="flex flex-col">
        <CalendarComponent
          :model-value="props.modelValue"
          @update:model-value="handleDateSelect"
          :min-value="props.minDate"
          :max-value="props.maxDate"
        />

        <div v-if="props.modelValue" class="border-t p-3">
          <Button
            variant="outline"
            size="sm"
            class="w-full"
            @click="clearDate"
          >
            Limpar
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
