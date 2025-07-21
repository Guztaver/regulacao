<template>
    <div class="status-transition-dropdown relative inline-block">
        <!-- Trigger Button -->
        <button
            ref="dropdownButton"
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
        <Teleport to="body">
            <div
                v-if="isOpen"
                :style="dropdownStyle"
                class="fixed z-[9999] w-56 rounded-md border border-gray-200 bg-white shadow-lg focus:outline-none"
            >
                <div v-if="allStatuses.length === 0 && !isLoading" class="px-4 py-2 text-sm text-gray-500">Nenhum status disponível</div>
                <ul v-else class="py-1" data-dropdown-menu>
                    <li v-for="status in allStatuses" :key="status.id">
                        <button
                            @click="selectStatus(status.id)"
                            :disabled="status.id === props.entry.current_status_id"
                            :class="[
                                'group flex w-full items-center px-4 py-2 text-sm transition-colors duration-200',
                                status.id === props.entry.current_status_id
                                    ? 'cursor-not-allowed bg-gray-100 text-gray-400'
                                    : getStatusColorClass(status),
                            ]"
                        >
                            <span class="mr-3 h-3 w-3 flex-shrink-0 rounded-full" :style="{ backgroundColor: status.color }"></span>
                            <span class="flex-1 text-left">{{ status.name }}</span>
                            <span v-if="status.id === props.entry.current_status_id" class="ml-2 text-xs text-gray-400">(atual)</span>
                        </button>
                    </li>
                </ul>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { handleApiError, useEntryApi } from '@/composables/useApi';
import { useTranslations } from '@/composables/useTranslations';
import type { Entry, EntryStatus } from '@/types';
import { computed, nextTick, onMounted, ref } from 'vue';

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
const allStatuses = ref<EntryStatus[]>([]);
const dropdownButton = ref<HTMLElement>();
const dropdownStyle = ref({});

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

async function loadAllStatuses() {
    isLoading.value = true;
    try {
        const response = await entryApi.getStatuses();
        allStatuses.value = response.statuses || [];
    } catch (err: any) {
        emit('error', handleApiError(err));
    } finally {
        isLoading.value = false;
    }
}

async function transitionToStatus(statusId: number) {
    if (!props.entry.id || statusId === props.entry.current_status_id) return;
    isLoading.value = true;
    try {
        const target = allStatuses.value.find((s) => s.id === statusId);
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

async function openDropdown() {
    if (props.disabled) return;
    isOpen.value = true;
    if (allStatuses.value.length === 0) {
        loadAllStatuses();
    }

    await nextTick();
    calculateDropdownPosition();
}

function calculateDropdownPosition() {
    if (!dropdownButton.value) return;

    const rect = dropdownButton.value.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const dropdownHeight = 300; // Approximate max height

    // Determine if dropdown should appear above or below
    const spaceBelow = viewportHeight - rect.bottom;
    const spaceAbove = rect.top;
    const shouldShowAbove = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;

    let top: number;
    if (shouldShowAbove) {
        top = rect.top - Math.min(dropdownHeight, spaceAbove) + window.scrollY;
    } else {
        top = rect.bottom + 8 + window.scrollY; // 8px gap
    }

    const left = rect.right - 224 + window.scrollX; // 224px = 14rem (w-56)

    dropdownStyle.value = {
        position: 'absolute',
        top: `${top}px`,
        left: `${left}px`,
        maxHeight: shouldShowAbove ? `${Math.min(dropdownHeight, spaceAbove - 8)}px` : `${Math.min(dropdownHeight, spaceBelow - 8)}px`,
        overflowY: 'auto',
    };
}

function closeDropdown() {
    isOpen.value = false;
}

function selectStatus(id: number) {
    if (id !== props.entry.current_status_id) {
        transitionToStatus(id);
    }
}

onMounted(() => {
    // Load all statuses on mount for better UX
    loadAllStatuses();

    document.addEventListener('click', (e: MouseEvent) => {
        const el = e.target as HTMLElement;
        if (!el.closest('.status-transition-dropdown') && !el.closest('[data-dropdown-menu]')) {
            closeDropdown();
        }
    });

    // Recalculate position on window resize/scroll
    window.addEventListener('resize', () => {
        if (isOpen.value) {
            calculateDropdownPosition();
        }
    });

    window.addEventListener(
        'scroll',
        () => {
            if (isOpen.value) {
                calculateDropdownPosition();
            }
        },
        true,
    );
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
