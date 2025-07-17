<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { reactive, ref, onMounted } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Patient {
    id: string;
    name: string;
    email: string;
    phone: string;
}

interface Entry {
    id: string;
    patient_id: string;
    title: string;
    patient?: Patient;
    created_at?: string;
    completed: boolean;
}

interface Filters {
    date_from: string;
    date_to: string;
    patient_name: string;
    entry_id: string;
    limit: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Completed Entries',
        href: '/entries/completed',
    },
];

const entries = ref<Entry[]>([]);
const loading = ref(false);
const message = ref('');
const error = ref('');
const isFilterDialogOpen = ref(false);

const filters: Filters = reactive({
    date_from: '',
    date_to: '',
    patient_name: '',
    entry_id: '',
    limit: 10,
});

const tempFilters: Filters = reactive({
    date_from: '',
    date_to: '',
    patient_name: '',
    entry_id: '',
    limit: 10,
});

const limitOptions = [5, 10, 25, 50, 100];

function loadCompletedEntries() {
    loading.value = true;
    error.value = '';

    const params = new URLSearchParams();

    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);
    if (filters.patient_name) params.append('patient_name', filters.patient_name);
    if (filters.entry_id) params.append('entry_id', filters.entry_id);
    params.append('limit', filters.limit.toString());

    axios
        .get(`/api/entries/completed?${params.toString()}`)
        .then((response) => {
            entries.value = response.data.entries;
            message.value = `Found ${response.data.count} completed entries`;
        })
        .catch((err) => {
            console.error('Error loading completed entries:', err);
            error.value = 'Failed to load completed entries';
        })
        .finally(() => {
            loading.value = false;
        });
}

function applyFilters() {
    Object.assign(filters, tempFilters);
    isFilterDialogOpen.value = false;
    loadCompletedEntries();
}

function clearFilters() {
    Object.assign(tempFilters, {
        date_from: '',
        date_to: '',
        patient_name: '',
        entry_id: '',
        limit: 10,
    });
    Object.assign(filters, tempFilters);
    loadCompletedEntries();
}

function openFilterDialog() {
    Object.assign(tempFilters, filters);
    isFilterDialogOpen.value = true;
}

function uncompleteEntry(id: string) {
    loading.value = true;

    axios
        .put(`/api/entries/${id}/complete`)
        .then((response) => {
            message.value = 'Entry marked as incomplete successfully';
            loadCompletedEntries();
        })
        .catch((err) => {
            console.error('Error uncompleting entry:', err);
            error.value = 'Failed to mark entry as incomplete';
        })
        .finally(() => {
            loading.value = false;
        });
}

function deleteEntry(id: string) {
    if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
        return;
    }

    loading.value = true;

    axios
        .delete(`/api/entries/${id}`)
        .then((response) => {
            message.value = 'Entry deleted successfully';
            loadCompletedEntries();
        })
        .catch((err) => {
            console.error('Error deleting entry:', err);
            error.value = 'Failed to delete entry';
        })
        .finally(() => {
            loading.value = false;
        });
}

function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function hasActiveFilters(): boolean {
    return !!(filters.date_from || filters.date_to || filters.patient_name || filters.entry_id);
}

onMounted(() => {
    loadCompletedEntries();
});
</script>

<template>
    <Head title="Completed Entries" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header with Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Completed Entries</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Manage your completed entries with filters and actions
                    </p>
                </div>

                <div class="flex gap-2">
                    <Dialog v-model:open="isFilterDialogOpen">
                        <DialogTrigger as-child>
                            <Button @click="openFilterDialog" variant="outline">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                                </svg>
                                Filters
                                <span v-if="hasActiveFilters()" class="ml-1 h-2 w-2 rounded-full bg-blue-500"></span>
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Filter Completed Entries</DialogTitle>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Date From</label>
                                        <Input
                                            type="date"
                                            v-model="tempFilters.date_from"
                                            class="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Date To</label>
                                        <Input
                                            type="date"
                                            v-model="tempFilters.date_to"
                                            class="mt-1"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Patient Name</label>
                                    <Input
                                        type="text"
                                        v-model="tempFilters.patient_name"
                                        placeholder="Search by patient name..."
                                        class="mt-1"
                                    />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Entry ID</label>
                                    <Input
                                        type="text"
                                        v-model="tempFilters.entry_id"
                                        placeholder="Enter specific entry ID..."
                                        class="mt-1"
                                    />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Limit Results</label>
                                    <select
                                        v-model="tempFilters.limit"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option v-for="option in limitOptions" :key="option" :value="option">
                                            {{ option }} entries
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button variant="outline" @click="clearFilters">
                                    Clear All
                                </Button>
                                <Button @click="applyFilters">
                                    Apply Filters
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Button @click="loadCompletedEntries" variant="outline" :disabled="loading">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </Button>
                </div>
            </div>

            <!-- Messages -->
            <div v-if="message" class="rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700 dark:border-green-600 dark:bg-green-900 dark:text-green-200">
                {{ message }}
            </div>
            <div v-if="error" class="rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700 dark:border-red-600 dark:bg-red-900 dark:text-red-200">
                {{ error }}
            </div>

            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
                <span v-if="filters.date_from" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    From: {{ filters.date_from }}
                </span>
                <span v-if="filters.date_to" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    To: {{ filters.date_to }}
                </span>
                <span v-if="filters.patient_name" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    Patient: {{ filters.patient_name }}
                </span>
                <span v-if="filters.entry_id" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    ID: {{ filters.entry_id }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                    Limit: {{ filters.limit }}
                </span>
            </div>

            <!-- Entries Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Completed Entries ({{ entries.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900 dark:border-white"></div>
                    </div>

                    <div v-else-if="entries.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No completed entries found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or check back later.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Patient
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Title
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Completed At
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-600 dark:bg-gray-800">
                                <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <span class="font-mono text-xs">{{ entry.id }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ entry.patient?.name || 'Unknown Patient' }}</span>
                                            <span v-if="entry.patient?.email" class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ entry.patient.email }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="max-w-xs truncate" :title="entry.title">
                                            {{ entry.title }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        {{ formatDate(entry.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <div class="flex gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                @click="uncompleteEntry(entry.id)"
                                                :disabled="loading"
                                            >
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                </svg>
                                                Uncomplete
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                @click="deleteEntry(entry.id)"
                                                :disabled="loading"
                                            >
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
