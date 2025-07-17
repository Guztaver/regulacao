<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { Entry } from '@/types';
import { onMounted, onUnmounted, watch } from 'vue';
import StatusBadge from './StatusBadge.vue';
import Timeline from './Timeline.vue';

interface Props {
    open: boolean;
    entry: Entry | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();

function formatDate(dateString?: string): string {
    if (!dateString) return 'N/A';

    try {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    } catch {
        return 'Data Inválida';
    }
}

function closeModal() {
    emit('update:open', false);
}

function handleEscapeKey(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.open) {
        closeModal();
    }
}

function handleBodyScroll(open: boolean) {
    if (open) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Watch for modal open/close changes to handle body scroll
watch(
    () => props.open,
    (newOpen) => {
        handleBodyScroll(newOpen);
    },
    { immediate: true },
);

onMounted(() => {
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscapeKey);
    // Ensure body scroll is restored when component unmounts
    document.body.style.overflow = '';
});
</script>

<template>
    <!-- Modal Backdrop -->
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click="closeModal">
        <!-- Lighter Overlay -->
        <div class="bg-opacity-80 bg-opacity-20 bg-gray-30 fixed inset-0 bg-black"></div>

        <!-- Modal Content - Larger and Scrollable -->
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-4xl flex-col rounded-lg bg-white shadow-xl dark:bg-gray-800" @click.stop>
            <!-- Fixed Header -->
            <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 p-6 dark:border-gray-700">
                <h3 class="flex items-center gap-2 text-xl font-semibold text-gray-900 dark:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    {{ t.entryDetails }}
                </h3>
                <button
                    @click="closeModal"
                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <div v-if="entry" class="space-y-6 p-6">
                    <!-- Main Info Grid -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Entry Status -->
                            <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Status Atual</h4>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ t.status }}:</span>
                                    <StatusBadge :entry="entry" />
                                </div>
                            </div>

                            <!-- Exam Schedule Date -->
                            <div v-if="entry.scheduled_exam_date" class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Agendamento</h4>
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span class="text-sm font-medium text-purple-800 dark:text-purple-200">
                                        {{ formatDate(entry.scheduled_exam_date) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Entry Basic Info -->
                            <div class="space-y-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Informações Básicas</h4>

                                <div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.id }}:</span>
                                    <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                        {{ entry.id }}
                                    </code>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.addedBy }}:</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ entry?.createdBy?.name || t.unknown }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Entry Title -->
                            <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.title }}</h4>
                                <p class="text-sm leading-relaxed text-gray-900 dark:text-gray-100">
                                    {{ entry.title }}
                                </p>
                            </div>

                            <!-- Status Description -->
                            <div v-if="entry.current_status?.description" class="rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Descrição do Status</h4>
                                <p class="text-sm leading-relaxed text-gray-900 dark:text-gray-100">
                                    {{ entry.current_status.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Information -->
                    <div class="rounded-lg bg-green-50 p-6 dark:bg-green-900/20">
                        <h4 class="mb-4 flex items-center gap-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            Informações do Paciente
                        </h4>

                        <div v-if="entry.patient" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.name }}:</span>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <a
                                        :href="`/patients/${entry.patient?.id}`"
                                        class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-200"
                                    >
                                        {{ entry.patient?.name || t.unknownPatient }}
                                    </a>
                                </p>
                            </div>

                            <div v-if="entry.patient.email">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.email }}:</span>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ entry.patient.email }}
                                </p>
                            </div>

                            <div v-if="entry.patient.sus_number">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.susNumber }}:</span>
                                <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                    {{ entry.patient.sus_number }}
                                </code>
                            </div>

                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">ID do Paciente:</span>
                                <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                    {{ entry.patient.id }}
                                </code>
                            </div>
                        </div>

                        <div v-else class="space-y-2">
                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">ID do Paciente:</span>
                                <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                    {{ entry.patient_id }}
                                </code>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Detalhes do paciente não disponíveis nesta visualização</p>
                        </div>
                    </div>

                    <!-- Status Transitions -->
                    <div class="rounded-lg bg-gray-50 p-6 dark:bg-gray-700">
                        <h4 class="mb-4 flex items-center gap-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                />
                            </svg>
                            Histórico de Status
                        </h4>

                        <div v-if="entry.status_transitions && entry.status_transitions.length > 0" class="space-y-3">
                            <div
                                v-for="transition in entry.status_transitions"
                                :key="transition.id"
                                class="flex items-start space-x-3 rounded-lg border bg-white p-4 shadow-sm dark:bg-gray-800"
                            >
                                <div
                                    class="mt-1 h-3 w-3 flex-shrink-0 rounded-full"
                                    :style="{ backgroundColor: transition.to_status?.color || '#6B7280' }"
                                ></div>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{
                                                transition.from_status
                                                    ? `${transition.from_status.name} → ${transition.to_status?.name}`
                                                    : `Set to ${transition.to_status?.name}`
                                            }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(transition.transitioned_at) }}
                                        </p>
                                    </div>
                                    <p v-if="transition.reason" class="mb-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ transition.reason }}
                                    </p>
                                    <p v-if="transition.scheduled_date" class="mb-1 text-xs text-purple-600 dark:text-purple-400">
                                        📅 {{ t.scheduled }}: {{ formatDate(transition.scheduled_date) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">por {{ transition.user?.name || t.unknown }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            <p class="mt-2">Nenhum histórico de status disponível</p>
                        </div>
                    </div>

                    <!-- Legacy Activity Timeline (for backward compatibility) -->
                    <div v-if="entry.timeline && entry.timeline.length > 0" class="rounded-lg bg-indigo-50 p-6 dark:bg-indigo-900/20">
                        <h4 class="mb-4 flex items-center gap-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            Linha do Tempo de Atividades
                        </h4>

                        <Timeline :timeline="entry.timeline || []" max-height="32rem" />
                    </div>
                </div>

                <div v-else class="p-6 py-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">Nenhuma entrada selecionada</p>
                </div>
            </div>
        </div>
    </div>
</template>
