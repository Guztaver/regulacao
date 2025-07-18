<script setup lang="ts">
import { ref } from 'vue'
import { Calendar } from '@/components/ui/calendar'
import { DatePicker } from '@/components/ui/date-picker'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const selectedDate = ref('')
const selectedDatePicker = ref('')

function handleDateChange(date: string) {
  console.log('Date selected:', date)
  selectedDate.value = date
}

function handleDatePickerChange(date: string) {
  console.log('DatePicker selected:', date)
  selectedDatePicker.value = date
}

function clearDates() {
  selectedDate.value = ''
  selectedDatePicker.value = ''
}

// Get today's date as minimum date
const today = new Date()
const minDate = today.toISOString().split('T')[0]

// Get max date (3 months from now)
const maxDate = new Date()
maxDate.setMonth(maxDate.getMonth() + 3)
const maxDateStr = maxDate.toISOString().split('T')[0]
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-2xl font-bold">Calendar Components Test</h1>

    <!-- Calendar Component Test -->
    <Card>
      <CardHeader>
        <CardTitle>Calendar Component</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <Calendar
            v-model="selectedDate"
            :min-value="minDate"
            :max-value="maxDateStr"
            @update:model-value="handleDateChange"
          />
          <div v-if="selectedDate" class="text-sm text-muted-foreground">
            Selected: {{ selectedDate }}
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- DatePicker Component Test -->
    <Card>
      <CardHeader>
        <CardTitle>DatePicker Component</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <DatePicker
            v-model="selectedDatePicker"
            :min-date="minDate"
            :max-date="maxDateStr"
            placeholder="Select a date"
            @update:model-value="handleDatePickerChange"
          />
          <div v-if="selectedDatePicker" class="text-sm text-muted-foreground">
            Selected: {{ selectedDatePicker }}
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Actions -->
    <div class="flex gap-2">
      <Button @click="clearDates" variant="outline">
        Clear All
      </Button>
    </div>

    <!-- Debug Info -->
    <Card>
      <CardHeader>
        <CardTitle>Debug Info</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-2 text-sm">
          <div><strong>Calendar value:</strong> {{ selectedDate || 'None' }}</div>
          <div><strong>DatePicker value:</strong> {{ selectedDatePicker || 'None' }}</div>
          <div><strong>Min date:</strong> {{ minDate }}</div>
          <div><strong>Max date:</strong> {{ maxDateStr }}</div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
