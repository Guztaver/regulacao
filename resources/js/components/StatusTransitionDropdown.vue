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
const nextStatuses = ref<EntryStatus[]>([]);
const reasonText = ref('');
const selectedStatusId = ref<number | null>(null);
const showReasonInput = ref(false);

const entryApi = useEntryApi();
const { t } = useTranslations();

const dropdownClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'text-xs px-2 py-1';
        case 'lg':
            return 'text-sm px-4 py-2';
        case 'md':
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
        case 'md':
        default:
            return 'h-4 w-4';
    }
});

async function loadNextStatuses() {
    if (!props.entry.id) return;

    console.log('StatusTransitionDropdown: Loading next statuses for entry', {
        entryId: props.entry.id,
        currentStatus: props.entry.current_status?.slug,
        currentStatusIsFinal: props.entry.current_status?.is_final,
        currentStatusName: props.entry.current_status?.name,
    });

    try {
        isLoading.value = true;
        const response = await entryApi.getNextStatuses(props.entry.id);
        nextStatuses.value = response.next_statuses || [];

        console.log('StatusTransitionDropdown: Next statuses loaded', {
            count: nextStatuses.value.length,
            statuses: nextStatuses.value.map((s) => ({ id: s.id, slug: s.slug, name: s.name, is_final: s.is_final })),
            rawResponse: response,
        });

        if (nextStatuses.value.length === 0) {
            console.warn('StatusTransitionDropdown: No next statuses available', {
                currentStatus: props.entry.current_status,
                entryId: props.entry.id,
            });
        }
    } catch (error: any) {
        console.error('StatusTransitionDropdown: Failed to load next statuses:', error);
        console.error('StatusTransitionDropdown: Error details:', {
            status: error.response?.status,
            statusText: error.response?.statusText,
            data: error.response?.data,
            url: error.config?.url,
        });
        emit('error', handleApiError(error));
    } finally {
        isLoading.value = false;
    }
}

async function transitionToStatus(statusId: number, reason?: string) {
    if (!props.entry.id) return;

    console.log('StatusTransitionDropdown: Starting transition', {
        entryId: props.entry.id,
        currentStatus: props.entry.current_status?.slug,
        targetStatusId: statusId,
        targetStatus: nextStatuses.value.find((s) => s.id === statusId)?.slug,
        reason,
    });

    try {
        isLoading.value = true;
        const response = await entryApi.transitionStatus(props.entry.id, statusId, reason);

        console.log('StatusTransitionDropdown: Transition successful', response);

        // Update the entry object
        const updatedEntry = { ...props.entry };
        const newStatus = nextStatuses.value.find((s) => s.id === statusId);
        if (newStatus) {
            updatedEntry.current_status = newStatus;
            updatedEntry.current_status_id = statusId;
        }

        emit('status-changed', updatedEntry);
        closeDropdown();
    } catch (error: any) {
        console.error('StatusTransitionDropdown: Failed to transition status:', error);
        console.error('StatusTransitionDropdown: Error details:', {
            status: error.response?.status,
            data: error.response?.data,
            message: error.message,
        });

        const errorMessage = error.response?.data?.error || error.response?.data?.message || handleApiError(error);
        emit('error', errorMessage);
    } finally {
        isLoading.value = false;
    }
}

function openDropdown() {
    if (props.disabled) return;
    isOpen.value = true;
    loadNextStatuses();
}

function closeDropdown() {
    isOpen.value = false;
    showReasonInput.value = false;
    reasonText.value = '';
    selectedStatusId.value = null;
}

function selectStatus(statusId: number) {
    selectedStatusId.value = statusId;
    const status = nextStatuses.value.find((s) => s.id === statusId);

    // Only require reason for cancelled transitions
    if (status && status.slug === 'cancelled') {
        showReasonInput.value = true;
        // Focus on textarea after it becomes visible
        nextTick(() => {
            const textarea = document.querySelector('.status-transition-dropdown textarea');
            if (textarea) textarea.focus();
        });
    } else {
        transitionToStatus(statusId);
    }
}

function confirmTransition() {
    if (selectedStatusId.value) {
        transitionToStatus(selectedStatusId.value, reasonText.value || undefined);
    }
}

function getStatusColorClass(status: EntryStatus): string {
    switch (status.color.toLowerCase()) {
        case '#10b981': // Green
            return 'text-green-700 hover:bg-green-50';
        case '#3b82f6': // Blue
            return 'text-blue-700 hover:bg-blue-50';
        case '#8b5cf6': // Purple
            return 'text-purple-700 hover:bg-purple-50';
        case '#f59e0b': // Amber
            return 'text-amber-700 hover:bg-amber-50';
        case '#ef4444': // Red
            return 'text-red-700 hover:bg-red-50';
        default:
            return 'text-gray-700 hover:bg-gray-50';
    }
}

onMounted(() => {
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        if (!target.closest('.status-transition-dropdown')) {
            closeDropdown();
        }
    });
});
</script>

<template>
    <div class="status-transition-dropdown relative inline-block">
        <!-- Trigger Button -->
        <button
            @click="openDropdown"
            :disabled="disabled || isLoading"
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
            class="ring-opacity-5 absolute right-0 z-50 mt-2 w-64 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black focus:outline-none"
        >
            <div v-if="!showReasonInput" class="py-1">
                <div v-if="nextStatuses.length === 0 && !isLoading" class="px-4 py-2">
                    <div class="mb-2 text-sm text-gray-500">Nenhuma transição de status disponível</div>
                    <div v-if="props.entry.current_status" class="text-xs text-gray-400">
                        Status atual: {{ props.entry.current_status.name }}
                        <span v-if="props.entry.current_status.is_final" class="text-orange-500">(Final)</span>
                    </div>
                </div>

                <button
                    v-for="status in nextStatuses"
                    :key="status.id"
                    @click="selectStatus(status.id)"
                    :class="['group flex w-full items-center px-4 py-2 text-sm transition-colors duration-200', getStatusColorClass(status)]"
                >
                    <div class="mr-3 h-3 w-3 rounded-full" :style="{ backgroundColor: status.color }"></div>
                    <div class="flex-1 text-left">
                        <div class="font-medium">{{ status.name }}</div>
                        <div v-if="status.description" class="text-xs text-gray-500">
                            {{ status.description }}
                        </div>
                    </div>
                </button>
            </div>

            <!-- Reason Input -->
            <div v-else class="p-4">
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Motivo da mudança de status
                        <span v-if="nextStatuses.find((s) => s.id === selectedStatusId)?.slug === 'cancelled'" class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="reasonText"
                        rows="3"
                        :placeholder="`Motivo para alterar para ${nextStatuses.find((s) => s.id === selectedStatusId)?.name}...`"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                    <p v-if="nextStatuses.find((s) => s.id === selectedStatusId)?.slug === 'cancelled'" class="mt-2 text-sm text-gray-600">
                        <span class="font-medium">Atenção:</span> É necessário informar o motivo para cancelar.
                    </p>
                </div>

                <div class="flex justify-end space-x-2">
                    <button
                        @click="closeDropdown"
                        type="button"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        {{ t.cancel }}
                    </button>
                    <button
                        @click="confirmTransition"
                        :disabled="isLoading || (nextStatuses.find((s) => s.id === selectedStatusId)?.slug === 'cancelled' && !reasonText.trim())"
                        type="button"
                        :class="[
                            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            isLoading || (nextStatuses.find((s) => s.id === selectedStatusId)?.slug === 'cancelled' && !reasonText.trim())
                                ? 'cursor-not-allowed bg-gray-300 text-gray-500'
                                : 'bg-blue-600 text-white hover:bg-blue-700',
                        ]"
                        :title="
                            nextStatuses.find((s) => s.id === selectedStatusId)?.is_final && !reasonText.trim()
                                ? 'Digite um motivo para continuar'
                                : ''
                        "
                    >
                        <span v-if="isLoading">Processando...</span>
                        <span v-else>Confirmar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom styles if needed */
</style>
