<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useEntryApi } from '@/composables/useApi';
import type { Entry } from '@/types';
import { Calendar, Clock } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    entry: Entry;
    disabled?: boolean;
}

interface Emits {
    'exam-scheduled': [entry: Entry];
    error: [message: string];
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const isLoading = ref(false);
const selectedDate = ref('');
const selectedTime = ref('09:00');
const reason = ref('');

const entryApi = useEntryApi();

// Get today's date as minimum date (ISO format YYYY-MM-DD)
const minDate = computed(() => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
});

// Get max date (6 months from now)
const maxDate = computed(() => {
    const sixMonthsFromNow = new Date();
    sixMonthsFromNow.setMonth(sixMonthsFromNow.getMonth() + 6);
    const year = sixMonthsFromNow.getFullYear();
    const month = String(sixMonthsFromNow.getMonth() + 1).padStart(2, '0');
    const day = String(sixMonthsFromNow.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
});

const canSchedule = computed(() => {
    return selectedDate.value && selectedTime.value && !isLoading.value;
});

const formattedDateTime = computed(() => {
    if (!selectedDate.value || !selectedTime.value) return '';

    try {
        const date = new Date(`${selectedDate.value}T${selectedTime.value}:00`);
        return date.toLocaleString('pt-BR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
});

function openModal() {
    if (props.disabled) return;

    // Reset form
    selectedDate.value = '';
    selectedTime.value = '09:00';
    reason.value = '';

    // If entry already has a scheduled date, pre-fill it
    if (props.entry.scheduled_exam_date) {
        try {
            const existingDate = new Date(props.entry.scheduled_exam_date);
            const year = existingDate.getFullYear();
            const month = String(existingDate.getMonth() + 1).padStart(2, '0');
            const day = String(existingDate.getDate()).padStart(2, '0');
            selectedDate.value = `${year}-${month}-${day}`;

            const hours = String(existingDate.getHours()).padStart(2, '0');
            const minutes = String(existingDate.getMinutes()).padStart(2, '0');
            selectedTime.value = `${hours}:${minutes}`;
        } catch (error) {
            console.warn('Failed to parse existing scheduled date:', error);
        }
    }

    isOpen.value = true;
}

function closeModal() {
    isOpen.value = false;
}

async function scheduleExam() {
    if (!canSchedule.value) return;

    try {
        isLoading.value = true;

        // Combine date and time
        const scheduledDateTime = `${selectedDate.value}T${selectedTime.value}:00`;

        console.log('Scheduling exam:', {
            entryId: props.entry.id,
            scheduledDateTime,
            reason: reason.value,
        });

        // Call the API to schedule the exam
        const response = await entryApi.scheduleExam(
            props.entry.id,
            scheduledDateTime,
            reason.value || `Exame agendado para ${formattedDateTime.value}`,
        );

        console.log('Exam scheduled successfully:', response);

        // Update the entry object
        const updatedEntry = { ...props.entry };
        updatedEntry.scheduled_exam_date = scheduledDateTime;

        // Update status if needed
        if (response.entry) {
            updatedEntry.current_status = response.entry.current_status;
            updatedEntry.current_status_id = response.entry.current_status_id;
        }

        emit('exam-scheduled', updatedEntry);
        closeModal();
    } catch (error: any) {
        console.error('Failed to schedule exam:', error);
        const errorMessage = error.response?.data?.message || error.response?.data?.error || 'Erro ao agendar exame';
        emit('error', errorMessage);
    } finally {
        isLoading.value = false;
    }
}

// Generate time options (every 30 minutes from 7:00 to 18:00)
const timeOptions = computed(() => {
    const options = [];
    for (let hour = 7; hour <= 18; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const timeString = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
            const displayTime = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
            options.push({ value: timeString, label: displayTime });
        }
    }
    return options;
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button @click="openModal" :disabled="disabled" variant="outline" size="sm" class="gap-2">
                <Calendar class="h-4 w-4" />
                {{ entry.scheduled_exam_date ? 'Reagendar' : 'Agendar' }} Exame
            </Button>
        </DialogTrigger>

        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Calendar class="h-5 w-5" />
                    {{ entry.scheduled_exam_date ? 'Reagendar' : 'Agendar' }} Exame
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Patient Info -->
                <div class="rounded-lg bg-muted/50 p-3">
                    <div class="text-sm font-medium">{{ entry.patient?.name }}</div>
                    <div class="text-xs text-muted-foreground">Agendamento de exame</div>
                </div>

                <!-- Current scheduled date if exists -->
                <div v-if="entry.scheduled_exam_date" class="rounded-lg border border-border bg-muted/50 p-3">
                    <div class="text-sm font-medium text-foreground">Agendamento atual:</div>
                    <div class="text-sm text-muted-foreground">
                        {{
                            new Date(entry.scheduled_exam_date).toLocaleString('pt-BR', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                            })
                        }}
                    </div>
                </div>

                <!-- Date Selection -->
                <div class="space-y-2">
                    <Label for="exam-date">Data do Exame</Label>
                    <DatePicker
                        id="exam-date"
                        v-model="selectedDate"
                        :min-date="minDate"
                        :max-date="maxDate"
                        placeholder="Selecione a data do exame"
                        :disabled="isLoading"
                    />
                </div>

                <!-- Time Selection -->
                <div class="space-y-2">
                    <Label for="exam-time" class="flex items-center gap-2">
                        <Clock class="h-4 w-4" />
                        Horário do Exame
                    </Label>
                    <select
                        id="exam-time"
                        v-model="selectedTime"
                        :disabled="isLoading"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option v-for="time in timeOptions" :key="time.value" :value="time.value">
                            {{ time.label }}
                        </option>
                    </select>
                </div>

                <!-- Preview -->
                <div v-if="formattedDateTime" class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/20">
                    <div class="text-sm font-medium text-green-900 dark:text-green-100">Agendamento:</div>
                    <div class="text-sm text-green-700 capitalize dark:text-green-300">
                        {{ formattedDateTime }}
                    </div>
                </div>

                <!-- Reason (optional) -->
                <div class="space-y-2">
                    <Label for="reason">Observações (opcional)</Label>
                    <Input id="reason" v-model="reason" placeholder="Observações sobre o agendamento..." :disabled="isLoading" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeModal" :disabled="isLoading"> Cancelar </Button>
                <Button @click="scheduleExam" :disabled="!canSchedule" :loading="isLoading">
                    {{ isLoading ? 'Agendando...' : entry.scheduled_exam_date ? 'Reagendar' : 'Agendar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
