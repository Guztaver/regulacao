<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface Props {
  modelValue?: string | Date
  placeholder?: string | Date
  disabled?: boolean
  readonly?: boolean
  minValue?: string | Date
  maxValue?: string | Date
}

interface Emits {
  'update:modelValue': [value: string]
}

const props = withDefaults(defineProps<Props>(), {})
const emit = defineEmits<Emits>()

const currentDate = ref(new Date())
const selectedDate = ref<Date | null>(null)

// Convert string dates to Date objects
const minDate = computed(() => {
  if (!props.minValue) return null
  return props.minValue instanceof Date ? props.minValue : new Date(props.minValue)
})

const maxDate = computed(() => {
  if (!props.maxValue) return null
  return props.maxValue instanceof Date ? props.maxValue : new Date(props.maxValue)
})

// Initialize selected date from modelValue
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    selectedDate.value = newValue instanceof Date ? newValue : new Date(newValue)
  } else {
    selectedDate.value = null
  }
}, { immediate: true })

const monthYear = computed(() => {
  return currentDate.value.toLocaleDateString('pt-BR', {
    month: 'long',
    year: 'numeric'
  })
})

const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()

  // First day of the month
  const firstDay = new Date(year, month, 1)
  // Last day of the month
  const lastDay = new Date(year, month + 1, 0)

  // Days from previous month to fill the first week
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())

  // Days from next month to fill the last week
  const endDate = new Date(lastDay)
  endDate.setDate(endDate.getDate() + (6 - lastDay.getDay()))

  const days = []
  const current = new Date(startDate)

  while (current <= endDate) {
    days.push(new Date(current))
    current.setDate(current.getDate() + 1)
  }

  return days
})

function previousMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}

function nextMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

function selectDate(date: Date) {
  if (props.disabled || props.readonly) return

  // Check if date is within allowed range
  if (minDate.value && date < minDate.value) return
  if (maxDate.value && date > maxDate.value) return

  selectedDate.value = date

  // Emit as ISO date string (YYYY-MM-DD)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  emit('update:modelValue', `${year}-${month}-${day}`)
}

function isCurrentMonth(date: Date) {
  return date.getMonth() === currentDate.value.getMonth()
}

function isSelected(date: Date) {
  if (!selectedDate.value) return false
  return date.toDateString() === selectedDate.value.toDateString()
}

function isToday(date: Date) {
  const today = new Date()
  return date.toDateString() === today.toDateString()
}

function isDisabled(date: Date) {
  if (props.disabled) return true
  if (minDate.value && date < minDate.value) return true
  if (maxDate.value && date > maxDate.value) return true
  return false
}
</script>

<template>
  <div class="p-3 select-none">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <button
        @click="previousMonth"
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100"
      >
        <ChevronLeft class="h-4 w-4" />
      </button>

      <div class="text-sm font-medium capitalize">
        {{ monthYear }}
      </div>

      <button
        @click="nextMonth"
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100"
      >
        <ChevronRight class="h-4 w-4" />
      </button>
    </div>

    <!-- Calendar Grid -->
    <div class="w-full">
      <!-- Week days header -->
      <div class="grid grid-cols-7 mb-2">
        <div
          v-for="day in weekDays"
          :key="day"
          class="flex items-center justify-center h-9 w-9 text-muted-foreground rounded-md font-normal text-[0.8rem]"
        >
          {{ day }}
        </div>
      </div>

      <!-- Calendar days -->
      <div class="grid grid-cols-7 gap-0">
        <button
          v-for="date in calendarDays"
          :key="date.toISOString()"
          @click="selectDate(date)"
          :disabled="isDisabled(date)"
          :class="cn(
            'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9 p-0 font-normal',
            // Today
            isToday(date) && !isSelected(date) && 'bg-accent text-accent-foreground',
            // Selected
            isSelected(date) && 'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground focus:bg-primary focus:text-primary-foreground',
            // Outside current month
            !isCurrentMonth(date) && 'text-muted-foreground opacity-50',
            // Disabled
            isDisabled(date) && 'text-muted-foreground opacity-30 cursor-not-allowed'
          )"
        >
          {{ date.getDate() }}
        </button>
      </div>
    </div>
  </div>
</template>
