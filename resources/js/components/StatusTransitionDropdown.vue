<template>
    <div class="status-transition-dropdown relative inline-block">
        <!-- Trigger Button -->
        <button
            ref="dropdownButton"
            @click="openDropdown"
            :disabled="props.disabled || isLoading"
            :class="[
                'inline-flex items-center justify-center rounded-md border border-input bg-background transition-colors duration-200',
                'hover:bg-accent hover:text-accent-foreground focus:border-ring focus:ring-1 focus:ring-ring focus:outline-none',
                'font-medium whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50',
                dropdownClasses,
            ]"
        >
            <svg v-if="isLoading" :class="['animate-spin', iconSize]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
            <svg v-else :class="[iconSize]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            {{ t.status }}
            <svg :class="[iconSize]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                :style="dropdownStyle"
                class="fixed z-[9999] w-56 rounded-md border border-gray-200 bg-white shadow-lg focus:outline-none dark:border-gray-600 dark:bg-gray-800"
            >
                <div v-if="allStatuses.length === 0 && !isLoading" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                    Nenhuma transição disponível
                </div>
                <ul v-else class="py-1" data-dropdown-menu>
                    <li v-for="status in allStatuses" :key="status.id">
                        <button
                            @click="selectStatus(status.id)"
                            :disabled="status.id === props.entry.current_status_id"
                            :class="[
                                'group flex w-full items-center px-4 py-2 text-sm transition-colors duration-200',
                                status.id === props.entry.current_status_id
                                    ? 'cursor-not-allowed bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500'
                                    : getStatusColorClass(status),
                            ]"
                        >
                            <span class="mr-3 h-3 w-3 flex-shrink-0 rounded-full" :style="{ backgroundColor: status.color }"></span>
                            <span class="flex-1 text-left">{{ status.name }}</span>
                            <span v-if="status.id === props.entry.current_status_id" class="ml-2 text-xs text-gray-400 dark:text-gray-500"
                                >(atual)</span
                            >
                        </button>
                    </li>
                </ul>
            </div>
        </Teleport>

        <!-- Cancellation Reason Modal -->
        <CancellationReasonModal v-model:open="showCancellationModal" :entry="props.entry" @confirm="handleCancellationConfirm" />
    </div>
</template>

<script setup lang="ts">
import CancellationReasonModal from '@/components/CancellationReasonModal.vue';
import { handleApiError, useEntryApi } from '@/composables/useApi';
import { useTranslations } from '@/composables/useTranslations';
import type { Entry, EntryStatus } from '@/types';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

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
const showCancellationModal = ref(false);
const pendingCancelStatusId = ref<number | null>(null);

const entryApi = useEntryApi();
const { t } = useTranslations();

const dropdownClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-8 text-sm px-3 py-1.5 gap-1.5';
        case 'lg':
            return 'h-10 text-sm px-6 py-2 gap-2';
        default:
            return 'h-9 text-sm px-4 py-2 gap-2';
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

async function loadValidStatuses() {
    isLoading.value = true;
    try {
        const response = await entryApi.getNextStatuses(props.entry.id);
        allStatuses.value = response.next_statuses || [];
    } catch (err: any) {
        emit('error', handleApiError(err));
    } finally {
        isLoading.value = false;
    }
}

async function transitionToStatus(statusId: number, reason?: string) {
    if (!props.entry.id || statusId === props.entry.current_status_id) return;
    isLoading.value = true;
    try {
        const target = allStatuses.value.find((s) => s.id === statusId);
        await entryApi.transitionStatus(props.entry.id, statusId, reason);
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
        loadValidStatuses();
    }

    await nextTick();
    // Add small delay to ensure DOM is fully rendered
    setTimeout(() => {
        calculateDropdownPosition();
    }, 10);
}

function calculateDropdownPosition() {
    if (!dropdownButton.value) return;

    const rect = dropdownButton.value.getBoundingClientRect();

    // Fallback if rect is invalid (button not visible)
    if (rect.width === 0 && rect.height === 0) {
        console.warn('StatusTransitionDropdown: Button rect is invalid, skipping positioning');
        return;
    }

    // Get actual viewport dimensions, accounting for mobile browsers
    const viewportHeight = Math.max(document.documentElement.clientHeight, window.innerHeight);
    const viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth);
    const dropdownWidth = 224; // 14rem (w-56)
    const dropdownHeight = 300; // Approximate max height
    const gap = 8; // Gap between button and dropdown

    // Determine if dropdown should appear above or below
    const spaceBelow = viewportHeight - rect.bottom;
    const spaceAbove = rect.top;
    const shouldShowAbove = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;

    // Calculate top position with better bounds checking
    let top: number;
    if (shouldShowAbove) {
        const availableSpace = Math.max(0, spaceAbove - gap);
        const actualHeight = Math.min(dropdownHeight, availableSpace);
        top = Math.max(0, rect.top - actualHeight - gap + window.scrollY);
    } else {
        top = rect.bottom + gap + window.scrollY;
    }

    // Ensure top position accounts for any fixed headers or navigation
    const minTop = window.scrollY + 10; // 10px margin from top of viewport
    top = Math.max(minTop, top);

    // Calculate left position - prefer right-aligned but ensure it stays within viewport
    let left: number;
    const preferredLeft = rect.right - dropdownWidth + window.scrollX;

    if (preferredLeft < 0) {
        // If dropdown would go off left edge, align with left edge of button
        left = Math.max(0, rect.left + window.scrollX);
    } else if (preferredLeft + dropdownWidth > viewportWidth) {
        // If dropdown would go off right edge, align with right edge of viewport
        left = Math.max(0, viewportWidth - dropdownWidth + window.scrollX - 16); // 16px margin from edge
    } else {
        // Use preferred position (right-aligned with button)
        left = preferredLeft;
    }

    // Additional bounds checking with viewport constraints
    const maxLeft = window.scrollX + viewportWidth - dropdownWidth - 10; // 10px margin from right
    left = Math.max(window.scrollX + 10, Math.min(left, maxLeft)); // 10px margin from left

    const maxTop = window.scrollY + viewportHeight - 50; // Minimum 50px for dropdown visibility
    top = Math.min(top, maxTop);

    const maxHeight = shouldShowAbove
        ? Math.min(dropdownHeight, Math.max(50, spaceAbove - gap))
        : Math.min(dropdownHeight, Math.max(50, spaceBelow - gap));

    dropdownStyle.value = {
        position: 'absolute',
        top: `${top}px`,
        left: `${left}px`,
        maxHeight: `${maxHeight}px`,
        overflowY: 'auto',
    };
}

function closeDropdown() {
    isOpen.value = false;
}

function selectStatus(id: number) {
    if (id === props.entry.current_status_id) return;

    // Check if this is a transition to cancelled status
    const targetStatus = allStatuses.value.find((s) => s.id === id);
    if (targetStatus?.slug === 'cancelled') {
        pendingCancelStatusId.value = id;
        showCancellationModal.value = true;
        closeDropdown();
    } else {
        transitionToStatus(id);
    }
}

function handleCancellationConfirm(reason: string) {
    if (pendingCancelStatusId.value) {
        transitionToStatus(pendingCancelStatusId.value, reason);
        pendingCancelStatusId.value = null;
    }
    showCancellationModal.value = false;
}

// Watch for entry status changes and reload valid statuses
watch(
    () => props.entry.current_status_id,
    () => {
        // Clear existing statuses and reload when status changes
        allStatuses.value = [];
        loadValidStatuses();
    },
);

onMounted(() => {
    // Load valid statuses on mount for better UX
    loadValidStatuses();

    document.addEventListener('click', (e: MouseEvent) => {
        const el = e.target as HTMLElement;
        if (!el.closest('.status-transition-dropdown') && !el.closest('[data-dropdown-menu]')) {
            closeDropdown();
        }
    });

    // Use IntersectionObserver to close dropdown when button goes out of view
    if (dropdownButton.value) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting && isOpen.value) {
                        closeDropdown();
                    }
                });
            },
            { threshold: 0.1 },
        );
        observer.observe(dropdownButton.value);
    }

    // Recalculate position on window resize/scroll with debouncing
    let resizeTimeout: number;
    window.addEventListener('resize', () => {
        if (isOpen.value) {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                calculateDropdownPosition();
            }, 50);
        }
    });

    let scrollTimeout: number;
    window.addEventListener(
        'scroll',
        () => {
            if (isOpen.value) {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    calculateDropdownPosition();
                }, 10);
            }
        },
        true,
    );
});

function getStatusColorClass(status: EntryStatus): string {
    switch (status.color.toLowerCase()) {
        case '#10b981':
            return 'text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20';
        case '#3b82f6':
            return 'text-primary hover:bg-accent hover:text-accent-foreground';
        case '#8b5cf6':
            return 'text-purple-700 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-900/20';
        case '#f59e0b':
            return 'text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20';
        case '#ef4444':
            return 'text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20';
        default:
            return 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700';
    }
}
</script>

<style scoped>
/* Optional custom styles */
</style>
