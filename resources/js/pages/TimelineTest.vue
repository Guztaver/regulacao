<template>
    <AppLayout>
        <div class="container mx-auto p-6">
            <h1 class="mb-6 text-2xl font-bold">Timeline Test Page</h1>

            <div class="mb-6">
                <h2 class="mb-4 text-lg font-semibold">Test Entry Actions</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Select Entry:</label>
                        <Select v-model="selectedEntryId" class="w-full max-w-md" placeholder="Select an entry...">
                            <option v-for="entry in entries" :key="entry.id" :value="entry.id">
                                {{ entry.title }} ({{ entry.id.substring(0, 8) }}...)
                            </option>
                        </Select>
                    </div>

                    <div v-if="selectedEntryId" class="flex space-x-4">
                        <button
                            @click="toggleComplete"
                            :disabled="loading"
                            class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50"
                        >
                            Toggle Complete
                        </button>
                        <button
                            @click="scheduleExam"
                            :disabled="loading"
                            class="rounded bg-purple-500 px-4 py-2 text-white hover:bg-purple-600 disabled:opacity-50"
                        >
                            Schedule Exam
                        </button>
                        <button
                            @click="markExamReady"
                            :disabled="loading"
                            class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50"
                        >
                            Mark Exam Ready
                        </button>
                        <button
                            @click="refreshEntry"
                            :disabled="loading"
                            class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600 disabled:opacity-50"
                        >
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="selectedEntry" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Entry Details -->
                <div class="rounded-lg border bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold">Entry Details</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="font-medium">ID:</span>
                            <code class="ml-2 rounded bg-gray-100 px-2 py-1 text-sm dark:bg-gray-700">
                                {{ selectedEntry.id }}
                            </code>
                        </div>
                        <div>
                            <span class="font-medium">Title:</span>
                            <span class="ml-2">{{ selectedEntry.title }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Status:</span>
                            <StatusBadge :entry="selectedEntry" class="ml-2" />
                        </div>
                        <div v-if="selectedEntry.exam_scheduled_date">
                            <span class="font-medium">Exam Date:</span>
                            <span class="ml-2">{{ formatDate(selectedEntry.exam_scheduled_date) }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Created:</span>
                            <span class="ml-2">{{ formatDate(selectedEntry.created_at) }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Updated:</span>
                            <span class="ml-2">{{ formatDate(selectedEntry.updated_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="rounded-lg border bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold">Activity Timeline</h3>
                    <Timeline :timeline="selectedEntry.timeline || []" max-height="32rem" />
                </div>
            </div>

            <!-- Messages -->
            <div v-if="message" class="mt-6">
                <div
                    :class="[
                        'rounded-lg p-4',
                        messageType === 'success'
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    ]"
                >
                    {{ message }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import Timeline from '@/components/Timeline.vue';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Entry } from '@/types';
import { computed, onMounted, ref } from 'vue';

const entries = ref<Entry[]>([]);
const selectedEntryId = ref<string>('');
const selectedEntry = ref<Entry | null>(null);
const loading = ref(false);
const message = ref('');
const messageType = ref<'success' | 'error'>('success');

const selectedEntryComputed = computed(() => {
    return entries.value.find((entry) => entry.id === selectedEntryId.value) || null;
});

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

function showMessage(msg: string, type: 'success' | 'error' = 'success') {
    message.value = msg;
    messageType.value = type;
    setTimeout(() => {
        message.value = '';
    }, 5000);
}

async function loadEntries() {
    try {
        const response = await fetch('/api/entries');
        if (response.ok) {
            entries.value = await response.json();
        } else {
            showMessage('Failed to load entries', 'error');
        }
    } catch (error) {
        showMessage('Error loading entries: ' + error, 'error');
    }
}

async function refreshEntry() {
    if (!selectedEntryId.value) return;

    loading.value = true;
    try {
        const response = await fetch(`/api/entries/${selectedEntryId.value}`);
        if (response.ok) {
            const data = await response.json();
            selectedEntry.value = data.entry;

            // Update the entry in the entries array
            const index = entries.value.findIndex((e) => e.id === selectedEntryId.value);
            if (index !== -1) {
                entries.value[index] = data.entry;
            }

            showMessage('Entry refreshed successfully');
        } else {
            showMessage('Failed to refresh entry', 'error');
        }
    } catch (error) {
        showMessage('Error refreshing entry: ' + error, 'error');
    } finally {
        loading.value = false;
    }
}

async function toggleComplete() {
    if (!selectedEntryId.value) return;

    loading.value = true;
    try {
        const response = await fetch(`/api/entries/${selectedEntryId.value}/complete`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            showMessage('Entry status toggled successfully');
            await refreshEntry();
        } else {
            const errorData = await response.json();
            showMessage('Failed to toggle entry: ' + (errorData.message || 'Unknown error'), 'error');
        }
    } catch (error) {
        showMessage('Error toggling entry: ' + error, 'error');
    } finally {
        loading.value = false;
    }
}

async function scheduleExam() {
    if (!selectedEntryId.value) return;

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const examDate = tomorrow.toISOString().split('T')[0];

    loading.value = true;
    try {
        const response = await fetch(`/api/entries/${selectedEntryId.value}/schedule-exam`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                exam_scheduled_date: examDate,
            }),
        });

        if (response.ok) {
            showMessage('Exam scheduled successfully for tomorrow');
            await refreshEntry();
        } else {
            const errorData = await response.json();
            showMessage('Failed to schedule exam: ' + (errorData.message || 'Unknown error'), 'error');
        }
    } catch (error) {
        showMessage('Error scheduling exam: ' + error, 'error');
    } finally {
        loading.value = false;
    }
}

async function markExamReady() {
    if (!selectedEntryId.value) return;

    loading.value = true;
    try {
        const response = await fetch(`/api/entries/${selectedEntryId.value}/mark-exam-ready`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            showMessage('Exam marked as ready successfully');
            await refreshEntry();
        } else {
            const errorData = await response.json();
            showMessage('Failed to mark exam ready: ' + (errorData.message || 'Unknown error'), 'error');
        }
    } catch (error) {
        showMessage('Error marking exam ready: ' + error, 'error');
    } finally {
        loading.value = false;
    }
}

// Watch for selectedEntryId changes
import { watch } from 'vue';
watch(selectedEntryId, async (newId) => {
    if (newId) {
        await refreshEntry();
    } else {
        selectedEntry.value = null;
    }
});

onMounted(() => {
    loadEntries();
});
</script>
