<script setup lang="ts">
import type { Entry } from '@/types';
import { onMounted, onUnmounted } from 'vue';
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
        return 'Invalid Date';
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

onMounted(() => {
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscapeKey);
});
</script>

<template>
    <!-- Modal Backdrop -->
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center" @click="closeModal">
        <!-- Overlay -->
        <div class="bg-opacity-50 fixed inset-0 bg-black"></div>

        <!-- Modal Content -->
        <div class="relative z-10 w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800" @click.stop>
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    Entry Details
                </h3>
                <button
                    @click="closeModal"
                    class="cursor-pointer rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div v-if="entry" class="space-y-4">
                <!-- Entry Status -->
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</span>
                    <StatusBadge :entry="entry" />
                </div>

                <!-- Exam Schedule Date (if scheduled) -->
                <div v-if="entry.exam_scheduled && entry.exam_scheduled_date" class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Exam Date:</span>
                    <span
                        class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-800 dark:bg-purple-900 dark:text-purple-200"
                    >
                        <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        {{ formatDate(entry.exam_scheduled_date) }}
                    </span>
                </div>

                <!-- Entry ID -->
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ID:</span>
                    <code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs dark:bg-gray-700">
                        {{ entry.id }}
                    </code>
                </div>

                <!-- Entry Title -->
                <div>
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title:</span>
                    <p class="rounded-md border bg-gray-50 p-3 text-sm text-gray-900 dark:bg-gray-700 dark:text-gray-100">
                        {{ entry.title }}
                    </p>
                </div>

                <div>
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Created By:</span>
                    <p class="rounded-md border p-3 text-sm text-gray-900 dark:bg-gray-700 dark:text-gray-100">
                        {{ entry?.createdBy?.name || 'Unknown' }}
                    </p>
                </div>

                <!-- Patient Information -->
                <div class="border-t pt-4">
                    <h4 class="mb-3 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        Patient Information
                    </h4>

                    <div v-if="entry.patient" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Name:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                <a :href="`/patients/${entry.patient?.id}`" class="text-blue-500 hover:underline">
                                    {{ entry.patient?.name || 'Unknown Patient' }}
                                </a>
                            </span>
                        </div>

                        <div v-if="entry.patient.email" class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Email:</span>
                            <span class="text-sm text-gray-900 dark:text-gray-100">
                                {{ entry.patient.email }}
                            </span>
                        </div>

                        <div v-if="entry.patient.sus_number" class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">SUS Number:</span>
                            <code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs dark:bg-gray-700">
                                {{ entry.patient.sus_number }}
                            </code>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Patient ID:</span>
                            <code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs dark:bg-gray-700">
                                {{ entry.patient.id }}
                            </code>
                        </div>
                    </div>

                    <div v-else class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Patient ID:</span>
                            <code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs dark:bg-gray-700">
                                {{ entry.patient_id }}
                            </code>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Patient details not available in this view</p>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="border-t pt-4">
                    <h4 class="mb-3 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                        Activity Timeline
                    </h4>

                    <Timeline :timeline="entry.timeline || []" max-height="24rem" />
                </div>
            </div>

            <div v-else class="py-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">No entry selected</p>
            </div>
        </div>
    </div>
</template>
