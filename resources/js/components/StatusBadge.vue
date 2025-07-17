<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
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

const { t } = useTranslations();

const statusInfo = computed(() => {
    const status = props.entry.current_status;

    if (!status) {
        return {
            text: t.unknown,
            color: '#6B7280',
            icon: 'question',
            bgClass: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
    }

    // Generate background classes based on status color
    const colorClasses = getColorClasses(status.color);

    return {
        text: getStatusName(status.slug),
        color: status.color,
        icon: getIconForStatus(status.slug),
        bgClass: colorClasses,
    };
});

function getColorClasses(hexColor: string): string {
    // Convert hex to appropriate Tailwind classes based on common colors
    switch (hexColor.toLowerCase()) {
        case '#10b981': // Green - completed
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case '#3b82f6': // Blue - exam scheduled
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case '#8b5cf6': // Purple - exam ready
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        case '#f59e0b': // Amber - pending
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
        case '#ef4444': // Red - cancelled
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}

function getIconForStatus(statusSlug: string): string {
    switch (statusSlug) {
        case 'completed':
            return 'check';
        case 'exam_scheduled':
            return 'calendar';
        case 'exam_ready':
            return 'ready';
        case 'cancelled':
            return 'x';
        case 'pending':
        default:
            return 'clock';
    }
}

function getStatusName(statusSlug: string): string {
    switch (statusSlug) {
        case 'completed':
            return t.completed;
        case 'exam_scheduled':
            return t.examScheduled;
        case 'exam_ready':
            return t.examReady;
        case 'cancelled':
            return t.cancelled;
        case 'pending':
            return t.pending;
        default:
            return t.unknown;
    }
}

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
    <span :class="['inline-flex items-center rounded-full font-semibold', statusInfo.bgClass, sizeClasses]">
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
            <!-- X icon for cancelled -->
            <path
                v-else-if="statusInfo.icon === 'x'"
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
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
