<template>
    <div class="status-transition-dropdown relative inline-block">
        <!-- Trigger Button -->
        <button
            @click="openDropdown"
            :disabled="props.disabled || isLoading"
            :class="[
                'inline-flex items-center rounded-md border border-gray-300 bg-white shadow-sm transition-colors duration-200',
                'hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none',
                'disabled:cursor-not-allowed disabled:opacity-50',
                dropdownClasses,
            ]"
        >
            <svg v-if="isLoading" :class="['mr-2 animate-spin', iconSize]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
            <svg v-else :class="['mr-2', iconSize]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            {{ t.status }}
            <svg :class="['ml-2', iconSize]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="ring-opacity-5 absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black focus:outline-none"
        >
            <div v-if="nextStatuses.length === 0 && !isLoading" class="px-4 py-2 text-sm text-gray-500">Nenhuma transição de status disponível</div>
            <ul v-else class="py-1">
                <li v-for="status in nextStatuses" :key="status.id">
                    <button
                        @click="selectStatus(status.id)"
                        :class="['group flex w-full items-center px-4 py-2 text-sm transition-colors duration-200', getStatusColorClass(status)]"
                    >
                        <span class="mr-3 h-3 w-3 flex-shrink-0 rounded-full" :style="{ backgroundColor: status.color }"></span>
                        <span class="flex-1 text-left">{{ status.name }}</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { handleApiError, useEntryApi } from '@/composables/useApi';
import { useTranslations } from '@/composables/useTranslations';
import type { Entry, EntryStatus } from '@/types';
import { computed, onMounted, ref } from 'vue';

interface Props {
    entry: Entry;
    disabled?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

interface Emits {
    'status-changed': [entry: Entry];
    error: [message: string];
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    size: 'md',
});
const emit = defineEmits<Emits>();

const isOpen = ref(false);
const isLoading = ref(false);
const nextStatuses = ref<EntryStatus[]>([]);

const entryApi = useEntryApi();
const { t } = useTranslations();

const dropdownClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'text-xs px-2 py-1';
        case 'lg':
            return 'text-sm px-4 py-2';
        default:
            return 'text-sm px-3 py-1.5';
    }
});

const iconSize = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-3 w-3';
        case 'lg':
            return 'h-5 w-5';
        default:
            return 'h-4 w-4';
    }
});

async function loadNextStatuses() {
    if (!props.entry.id) return;
    isLoading.value = true;
    try {
        const response = await entryApi.getNextStatuses(props.entry.id);
        nextStatuses.value = response.next_statuses || [];
    } catch (err: any) {
        emit('error', handleApiError(err));
    } finally {
        isLoading.value = false;
    }
}

async function transitionToStatus(statusId: number) {
    if (!props.entry.id) return;
    isLoading.value = true;
    try {
        const target = nextStatuses.value.find((s) => s.id === statusId);
        await entryApi.transitionStatus(props.entry.id, statusId);
        const updated = { ...props.entry };
        if (target) {
            updated.current_status = target;
            updated.current_status_id = statusId;
        }
        emit('status-changed', updated);
    } catch (err: any) {
        emit('error', err.response?.data?.error || handleApiError(err));
    } finally {
        isLoading.value = false;
        closeDropdown();
    }
}

function openDropdown() {
    if (props.disabled) return;
    isOpen.value = true;
    loadNextStatuses();
}

function closeDropdown() {
    isOpen.value = false;
}

function selectStatus(id: number) {
    transitionToStatus(id);
}

onMounted(() => {
    document.addEventListener('click', (e: MouseEvent) => {
        const el = e.target as HTMLElement;
        if (!el.closest('.status-transition-dropdown')) {
            closeDropdown();
        }
    });
});

function getStatusColorClass(status: EntryStatus): string {
    switch (status.color.toLowerCase()) {
        case '#10b981':
            return 'text-green-700 hover:bg-green-50';
        case '#3b82f6':
            return 'text-blue-700 hover:bg-blue-50';
        case '#8b5cf6':
            return 'text-purple-700 hover:bg-purple-50';
        case '#f59e0b':
            return 'text-amber-700 hover:bg-amber-50';
        case '#ef4444':
            return 'text-red-700 hover:bg-red-50';
        default:
            return 'text-gray-700 hover:bg-gray-50';
    }
}
</script>

<style scoped>
/* Optional custom styles */
</style>
