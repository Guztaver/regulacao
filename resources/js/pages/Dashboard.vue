<script setup lang="ts">
import EntryInfoModal from '@/components/EntryInfoModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Patient {
    id: string;
    name: string;
    email: string;
    phone: string;
    sus_number?: string;
    created_by?: User;
}

interface Entry {
    id: string;
    patient_id: string;
    title: string;
    patient?: Patient;
    created_at?: string;
    completed: boolean;
    created_by?: User;
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
];

const patient: Patient = reactive({
    id: '',
    name: '',
    email: '',
    phone: '',
    sus_number: '',
});

const entry = reactive({
    patient_id: '',
    title: '',
});

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

// State management
const message = ref('');
const error = ref('');
const loading = ref(false);
const entryMessage = ref('');
const entryError = ref('');
const entryLoading = ref(false);
const isPatientModalOpen = ref(false);
const isEntryModalOpen = ref(false);
const isFilterDialogOpen = ref(false);
const isEntryInfoModalOpen = ref(false);
const selectedEntry = ref<Entry | null>(null);

const patients = ref<Patient[]>([]);
const entries = ref<Entry[]>([]);
const limitOptions = [5, 10, 25, 50, 100];

// Patient functions
function createPatient() {
    loading.value = true;
    error.value = '';
    message.value = '';

    // Validate SUS number
    if (patient.sus_number && patient.sus_number.length < 15) {
        alert('SUS number must be exactly 15 digits long');
        loading.value = false;
        return;
    }

    const patientData = {
        name: patient.name,
        email: patient.email,
        phone: patient.phone,
        sus_number: patient.sus_number,
    };

    axios
        .post('/api/patients', patientData)
        .then((response) => {
            message.value = response.data.message;
            patient.name = '';
            patient.email = '';
            patient.phone = '';
            patient.sus_number = '';
            isPatientModalOpen.value = false;
            loadPatients();
        })
        .catch((err) => {
            console.error(err);
            error.value = 'Failed to create patient';
        })
        .finally(() => {
            loading.value = false;
        });
}

function loadPatients() {
    axios
        .get('/api/patients')
        .then((response) => {
            patients.value = response.data;
        })
        .catch((err) => {
            console.error('Error loading patients:', err);
        });
}

// Entry functions
function loadEntries() {
    entryLoading.value = true;
    entryError.value = '';

    const params = new URLSearchParams();

    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);
    if (filters.patient_name) params.append('patient_name', filters.patient_name);
    if (filters.entry_id) params.append('entry_id', filters.entry_id);
    params.append('limit', filters.limit.toString());

    axios
        .get(`/api/entries?${params.toString()}`)
        .then((response) => {
            entries.value = response.data;
        })
        .catch((err) => {
            console.error('Error loading entries:', err);
            entryError.value = 'Failed to load entries';
        })
        .finally(() => {
            entryLoading.value = false;
        });
}

function createEntry() {
    entryLoading.value = true;
    entryError.value = '';
    entryMessage.value = '';

    axios
        .post('/api/entries', entry)
        .then((response) => {
            entryMessage.value = response.data.message;
            entry.patient_id = '';
            entry.title = '';
            isEntryModalOpen.value = false;
            loadEntries();
        })
        .catch((err) => {
            console.error(err);
            entryError.value = 'Failed to create entry';
        })
        .finally(() => {
            entryLoading.value = false;
        });
}

function completeEntry(id: string) {
    entryLoading.value = true;

    axios
        .put(`/api/entries/${id}/complete`)
        .then((response) => {
            entryMessage.value = response.data.message;
            loadEntries();
        })
        .catch((err) => {
            console.error(err);
            entryError.value = 'Failed to complete entry';
        })
        .finally(() => {
            entryLoading.value = false;
        });
}

function deleteEntry(id: string) {
    if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
        return;
    }

    entryLoading.value = true;

    axios
        .delete(`/api/entries/${id}`)
        .then(() => {
            entryMessage.value = 'Entry deleted successfully';
            loadEntries();
        })
        .catch((err) => {
            console.error(err);
            entryError.value = 'Failed to delete entry';
        })
        .finally(() => {
            entryLoading.value = false;
        });
}

// Filter functions
function applyFilters() {
    Object.assign(filters, tempFilters);
    isFilterDialogOpen.value = false;
    loadEntries();
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
    loadEntries();
}

function openFilterDialog() {
    Object.assign(tempFilters, filters);
    isFilterDialogOpen.value = true;
}

function hasActiveFilters(): boolean {
    return !!(filters.date_from || filters.date_to || filters.patient_name || filters.entry_id);
}

function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function showEntryInfo(entry: Entry): void {
    selectedEntry.value = entry;
    isEntryInfoModalOpen.value = true;
}

// Initialize data
onMounted(() => {
    loadPatients();
    loadEntries();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Manage patients and entries efficiently</p>
                </div>

                <div class="flex gap-2">
                    <!-- Create Patient Modal -->
                    <Dialog v-model:open="isPatientModalOpen">
                        <DialogTrigger as-child>
                            <Button>
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add Patient
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Create New Patient</DialogTitle>
                            </DialogHeader>

                            <form @submit.prevent="createPatient" class="grid gap-4 py-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                    <Input type="text" v-model="patient.name" placeholder="Enter patient name" class="mt-1" required />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                    <Input type="email" v-model="patient.email" placeholder="Enter email address" class="mt-1" required />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                    <Input type="text" v-model="patient.phone" placeholder="Enter phone number" class="mt-1" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">SUS Number</label>
                                    <Input
                                        type="text"
                                        v-model="patient.sus_number"
                                        placeholder="Enter SUS number (15 digits)"
                                        class="mt-1"
                                        maxlength="15"
                                    />
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="isPatientModalOpen = false"> Cancel </Button>
                                    <Button type="submit" :disabled="loading">
                                        <span v-if="loading">Creating...</span>
                                        <span v-else>Create Patient</span>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <!-- Create Entry Modal -->
                    <Dialog v-model:open="isEntryModalOpen">
                        <DialogTrigger as-child>
                            <Button variant="outline">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Add Entry
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Create New Entry</DialogTitle>
                            </DialogHeader>

                            <form @submit.prevent="createEntry" class="grid gap-4 py-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Patient</label>
                                    <select
                                        v-model="entry.patient_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required
                                    >
                                        <option value="" v-if="patients.length > 0">Select a patient</option>
                                        <option value="" disabled v-else>No patients available - Please create a patient first</option>
                                        <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                            {{ patient.name }} ({{ patient.email }})
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                    <Input type="text" v-model="entry.title" placeholder="Enter entry title" class="mt-1" required />
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="isEntryModalOpen = false"> Cancel </Button>
                                    <Button type="submit" :disabled="entryLoading">
                                        <span v-if="entryLoading">Creating...</span>
                                        <span v-else>Create Entry</span>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Messages -->
            <div
                v-if="message"
                class="rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700 dark:border-green-600 dark:bg-green-900 dark:text-green-200"
            >
                {{ message }}
            </div>
            <div
                v-if="error"
                class="rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700 dark:border-red-600 dark:bg-red-900 dark:text-red-200"
            >
                {{ error }}
            </div>
            <div
                v-if="entryMessage"
                class="rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700 dark:border-green-600 dark:bg-green-900 dark:text-green-200"
            >
                {{ entryMessage }}
            </div>
            <div
                v-if="entryError"
                class="rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700 dark:border-red-600 dark:bg-red-900 dark:text-red-200"
            >
                {{ entryError }}
            </div>

            <!-- Entries Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Active Entries ({{ entries.length }})</h2>
                </div>

                <div class="flex gap-2">
                    <Dialog v-model:open="isFilterDialogOpen">
                        <DialogTrigger as-child>
                            <Button @click="openFilterDialog" variant="outline">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"
                                    />
                                </svg>
                                Filters
                                <span v-if="hasActiveFilters()" class="ml-1 h-2 w-2 rounded-full bg-blue-500"></span>
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Filter Entries</DialogTitle>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Date From</label>
                                        <Input type="date" v-model="tempFilters.date_from" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Date To</label>
                                        <Input type="date" v-model="tempFilters.date_to" class="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Patient Name</label>
                                    <Input type="text" v-model="tempFilters.patient_name" placeholder="Search by patient name..." class="mt-1" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Entry ID</label>
                                    <Input type="text" v-model="tempFilters.entry_id" placeholder="Enter specific entry ID..." class="mt-1" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Limit Results</label>
                                    <select
                                        v-model="tempFilters.limit"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option v-for="option in limitOptions" :key="option" :value="option">{{ option }} entries</option>
                                    </select>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button variant="outline" @click="clearFilters"> Clear All </Button>
                                <Button @click="applyFilters"> Apply Filters </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Button @click="loadEntries" variant="outline" :disabled="entryLoading">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Refresh
                    </Button>
                </div>
            </div>

            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
                <span
                    v-if="filters.date_from"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    From: {{ filters.date_from }}
                </span>
                <span
                    v-if="filters.date_to"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    To: {{ filters.date_to }}
                </span>
                <span
                    v-if="filters.patient_name"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    Patient: {{ filters.patient_name }}
                </span>
                <span
                    v-if="filters.entry_id"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    ID: {{ filters.entry_id }}
                </span>
                <span
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                >
                    Limit: {{ filters.limit }}
                </span>
            </div>

            <!-- Entries Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Active Entries</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="entryLoading" class="flex items-center justify-center py-12">
                        <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900 dark:border-white"></div>
                    </div>

                    <div v-else-if="entries.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No entries found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create your first entry or adjust your filters.</p>
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
                                        Created At
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Added By
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-600 dark:bg-gray-800">
                                <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <button
                                            @click="showEntryInfo(entry)"
                                            class="cursor-pointer font-mono text-xs text-blue-600 underline hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            :title="'Click to view entry details'"
                                        >
                                            {{ entry.id }}
                                        </button>
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
                                        <p class="max-w-xs truncate text-left">
                                            {{ entry.title }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        {{ formatDate(entry.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        {{ entry.created_by?.name || 'Unknown' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <div class="flex gap-2">
                                            <Button size="sm" @click="completeEntry(entry.id)" :disabled="entryLoading">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Complete
                                            </Button>
                                            <Button size="sm" variant="destructive" @click="deleteEntry(entry.id)" :disabled="entryLoading">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
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

        <!-- Entry Info Modal -->
        <EntryInfoModal v-model:open="isEntryInfoModalOpen" :entry="selectedEntry" />
    </AppLayout>
</template>
