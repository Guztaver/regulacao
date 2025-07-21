<template>
    <Dialog :open="isOpen" @update:open="handleClose">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <ClockIcon class="h-5 w-5" />
                    Histórico de Status
                </DialogTitle>
                <DialogDescription> Histórico completo de mudanças de status para esta entrada. </DialogDescription>
            </DialogHeader>

            <div class="max-h-96 overflow-y-auto">
                <div v-if="isLoading" class="flex items-center justify-center py-8">
                    <div class="flex items-center gap-2 text-gray-500">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Carregando histórico...
                    </div>
                </div>

                <div v-else-if="transitions.length === 0" class="py-8 text-center text-gray-500">Nenhum histórico de mudanças encontrado.</div>

                <div v-else class="space-y-4">
                    <div v-for="(transition, index) in transitions" :key="transition.id" class="relative">
                        <!-- Timeline line -->
                        <div v-if="index < transitions.length - 1" class="absolute top-8 left-4 h-full w-0.5 bg-gray-200"></div>

                        <div class="flex gap-4">
                            <!-- Status indicator -->
                            <div class="relative flex-shrink-0">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white shadow-sm"
                                    :style="{ backgroundColor: transition.to_status.color }"
                                >
                                    <CheckIcon v-if="transition.to_status.slug === 'completed'" class="h-4 w-4 text-white" />
                                    <XIcon v-else-if="transition.to_status.slug === 'cancelled'" class="h-4 w-4 text-white" />
                                    <CalendarIcon v-else-if="transition.to_status.slug === 'exam_scheduled'" class="h-4 w-4 text-white" />
                                    <FileTextIcon v-else-if="transition.to_status.slug === 'exam_ready'" class="h-4 w-4 text-white" />
                                    <ClockIcon v-else class="h-4 w-4 text-white" />
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1 pb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            {{ transition.from_status ? `${transition.from_status.name} → ` : '' }}{{ transition.to_status.name }}
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            {{ formatTransitionDate(transition.transitioned_at) }}
                                        </p>
                                    </div>
                                    <div class="text-xs text-gray-400">por {{ transition.user?.name || 'Sistema' }}</div>
                                </div>

                                <!-- Reason -->
                                <div v-if="transition.reason" class="mt-2">
                                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                        <div class="flex items-start gap-2">
                                            <MessageSquareIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-gray-400" />
                                            <div>
                                                <p class="mb-1 text-xs font-medium text-gray-700">
                                                    {{ getReasonLabel(transition.to_status.slug) }}
                                                </p>
                                                <p class="text-sm text-gray-900">{{ transition.reason }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Metadata -->
                                <div v-if="transition.metadata && Object.keys(transition.metadata).length > 0" class="mt-2">
                                    <div class="rounded-md border border-blue-200 bg-blue-50 p-3">
                                        <div class="flex items-start gap-2">
                                            <InfoIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-400" />
                                            <div class="space-y-1">
                                                <p class="mb-1 text-xs font-medium text-blue-700">Informações adicionais:</p>
                                                <div v-for="(value, key) in transition.metadata" :key="key" class="text-sm">
                                                    <span class="font-medium text-blue-900">{{ formatMetadataKey(key) }}:</span>
                                                    <span class="ml-1 text-blue-800">{{ formatMetadataValue(value) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button variant="outline" @click="handleClose"> Fechar </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { handleApiError, useEntryApi } from '@/composables/useApi';
import type { Entry } from '@/types';
import { CalendarIcon, CheckIcon, ClockIcon, FileTextIcon, InfoIcon, MessageSquareIcon, XIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface StatusTransition {
    id: number;
    entry_id: string;
    from_status: {
        id: number;
        name: string;
        slug: string;
        color: string;
    } | null;
    to_status: {
        id: number;
        name: string;
        slug: string;
        color: string;
    };
    user: {
        id: number;
        name: string;
    } | null;
    reason: string | null;
    metadata: Record<string, any> | null;
    transitioned_at: string;
}

interface Props {
    open: boolean;
    entry: Entry | null;
}

interface Emits {
    'update:open': [value: boolean];
    error: [message: string];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isOpen = ref(false);
const isLoading = ref(false);
const transitions = ref<StatusTransition[]>([]);

const entryApi = useEntryApi();

// Watch for prop changes
watch(
    () => props.open,
    (newValue) => {
        isOpen.value = newValue;
        if (newValue && props.entry) {
            loadStatusHistory();
        }
    },
);

// Watch for internal changes and emit
watch(isOpen, (newValue) => {
    if (!newValue) {
        emit('update:open', false);
    }
});

function handleClose() {
    isOpen.value = false;
}

async function loadStatusHistory() {
    if (!props.entry?.id) return;

    isLoading.value = true;
    try {
        // This would need to be implemented in the API
        const response = await entryApi.getStatusHistory(props.entry.id);
        transitions.value = response.transitions || [];
    } catch (err: any) {
        emit('error', handleApiError(err));
        transitions.value = [];
    } finally {
        isLoading.value = false;
    }
}

function formatTransitionDate(date: string): string {
    const d = new Date(date);
    return d.toLocaleString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getReasonLabel(statusSlug: string): string {
    switch (statusSlug) {
        case 'cancelled':
            return 'Motivo do cancelamento:';
        case 'completed':
            return 'Observações:';
        case 'exam_scheduled':
            return 'Detalhes do agendamento:';
        case 'exam_ready':
            return 'Observações:';
        default:
            return 'Motivo:';
    }
}

function formatMetadataKey(key: string): string {
    const keyMappings: Record<string, string> = {
        exam_scheduled_date: 'Data do exame',
        scheduled_time: 'Horário',
        location: 'Local',
        notes: 'Observações',
        priority: 'Prioridade',
    };

    return keyMappings[key] || key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
}

function formatMetadataValue(value: any): string {
    if (value === null || value === undefined) return '-';
    if (typeof value === 'boolean') return value ? 'Sim' : 'Não';
    if (typeof value === 'object') return JSON.stringify(value);

    // Try to format as date if it looks like one
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
        try {
            const date = new Date(value);
            return date.toLocaleDateString('pt-BR');
        } catch {
            return String(value);
        }
    }

    return String(value);
}
</script>
