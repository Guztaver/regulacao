<script setup lang="ts">
import type { Entry } from '@/types';
import { computed } from 'vue';

interface Props {
    entry: Entry;
    size?: 'sm' | 'md' | 'lg';
    showIcon?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showIcon: true,
});

const statusInfo = computed(() => {
    if (props.entry.completed) {
        return {
            text: 'Completed',
            color: 'green',
            icon: 'check',
            bgClass: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        };
    } else if (props.entry.exam_ready) {
        return {
            text: 'Exam Ready',
            color: 'indigo',
            icon: 'ready',
            bgClass: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        };
    } else if (props.entry.exam_scheduled) {
        return {
            text: 'Exam Scheduled',
            color: 'purple',
            icon: 'calendar',
            bgClass: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        };
    } else {
        return {
            text: 'Pending',
            color: 'gray',
            icon: 'clock',
            bgClass: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-2 py-0.5 text-xs';
        case 'lg':
            return 'px-3 py-1 text-sm';
        case 'md':
        default:
            return 'px-2.5 py-0.5 text-xs';
    }
});

const iconSize = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-3 w-3';
        case 'lg':
            return 'h-4 w-4';
        case 'md':
        default:
            return 'h-3 w-3';
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full font-semibold',
            statusInfo.bgClass,
            sizeClasses,
        ]"
    >
        <svg v-if="showIcon" :class="['mr-1', iconSize]" fill="currentColor" viewBox="0 0 20 20">
            <!-- Completed check icon -->
            <path
                v-if="statusInfo.icon === 'check'"
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
            />
            <!-- Calendar icon -->
            <path
                v-else-if="statusInfo.icon === 'calendar'"
                fill-rule="evenodd"
                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                clip-rule="evenodd"
            />
            <!-- Ready check-circle icon -->
            <path
                v-else-if="statusInfo.icon === 'ready'"
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414-1.414L9 7.586 7.707 6.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414z"
                clip-rule="evenodd"
            />
            <!-- Clock icon for pending -->
            <path
                v-else
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                clip-rule="evenodd"
            />
        </svg>
        {{ statusInfo.text }}
    </span>
</template>
