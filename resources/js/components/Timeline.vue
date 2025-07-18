<script setup lang="ts">
import { useTimeline } from '@/composables/useTimeline';
import type { EntryTimeline } from '@/types';
import { computed } from 'vue';

interface Props {
    timeline: EntryTimeline[];
    maxHeight?: string;
}

const props = withDefaults(defineProps<Props>(), {
    maxHeight: '24rem', // 96 in Tailwind (24rem)
});

const {
    getTimelineIcon,
    getTimelineColor,
    getTimelineBackgroundColor,
    formatTimelineDate,
    formatTimelineMetadata,
    sortTimelineByDate,
    getActionDisplayName,
} = useTimeline();

const sortedTimeline = computed(() => {
    return sortTimelineByDate(props.timeline);
});
</script>

<template>
    <div v-if="sortedTimeline && sortedTimeline.length > 0" :class="['space-y-3 overflow-y-auto', `max-h-[${maxHeight}]`]">
        <div v-for="(timelineItem, index) in sortedTimeline" :key="timelineItem.id" class="relative flex items-start space-x-3">
            <!-- Timeline line -->
            <div v-if="index < sortedTimeline.length - 1" class="absolute top-8 left-4 h-full w-px bg-gray-200 dark:bg-gray-700"></div>

            <!-- Timeline icon -->
            <div
                :class="[
                    'flex h-8 w-8 items-center justify-center rounded-full ring-2 ring-white dark:ring-gray-800',
                    getTimelineBackgroundColor(timelineItem.action),
                ]"
            >
                <svg :class="['h-4 w-4', getTimelineColor(timelineItem.action)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        v-if="getTimelineIcon(timelineItem.action) === 'plus'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    />
                    <path
                        v-else-if="getTimelineIcon(timelineItem.action) === 'check'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                    <path
                        v-else-if="getTimelineIcon(timelineItem.action) === 'x'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                    <path
                        v-else-if="getTimelineIcon(timelineItem.action) === 'calendar'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                    <path
                        v-else-if="getTimelineIcon(timelineItem.action) === 'check-circle'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                    <path
                        v-else-if="getTimelineIcon(timelineItem.action) === 'edit'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                    <path
                        v-else-if="getTimelineIcon(timelineItem.action) === 'trash'"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>

            <!-- Timeline content -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ getActionDisplayName(timelineItem.action) }}
                    </p>
                    <time class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatTimelineDate(timelineItem.performed_at) }}
                    </time>
                </div>
                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">por {{ timelineItem.user?.name || 'Usuário Desconhecido' }}</div>

                <!-- Additional metadata -->
                <div v-if="formatTimelineMetadata(timelineItem.metadata)" class="mt-2">
                    <div class="inline-flex items-center rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ formatTimelineMetadata(timelineItem.metadata) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="py-4 text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum dado de timeline disponível</p>
    </div>
</template>
